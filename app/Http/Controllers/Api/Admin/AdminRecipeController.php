<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\RecipeClassificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
class AdminRecipeController extends Controller
{
    protected RecipeClassificationService $classificationService;

    public function __construct(RecipeClassificationService $classificationService)
    {
        $this->classificationService = $classificationService;
    }

    public function index(Request $request)
    {
        $query = Recipe::with(['creator:id,name,email', 'approver:id,name,email']);

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Filter berdasarkan region
        if ($request->has('region')) {
            $query->where('region', $request->region);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%")
                  ->orWhere('region', 'like', "%{$search}%");
            });
        }

        $recipes = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar resep berhasil diambil',
            'data'    => $recipes
        ]);
    }

    public function store(Request $request)
    {
        // 1. Cek Role Admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Admin only'
            ], 403);
        }

        // 2. Validasi Input (Sesuaikan dengan struktur RecipeController)
        $validator = Validator::make($request->all(), [
            'nama'          => 'required|string|max:150|min:3',
            'deskripsi'     => 'nullable|string|max:5000',
            'gambar'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:1024', // 1MB like user controller
            'waktu_masak'   => 'nullable|integer|min:1|max:1440',
            'region'        => 'nullable|string|max:100',
            'kategori'      => 'nullable|string|max:100',
            
            // Bahan-bahan: array of { nama, jumlah, satuan }
            'bahan_bahan'   => 'required|array|min:1',
            'bahan_bahan.*.nama'    => 'required|string|max:100',
            'bahan_bahan.*.jumlah'  => 'required|string|max:100',
            'bahan_bahan.*.satuan'  => 'nullable|string|max:50',
            
            // Langkah-langkah (opsional)
            'langkah_langkah' => 'nullable|array',
            'langkah_langkah.*' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request) {
                
                // 3. Handle Upload Gambar (Sama seperti RecipeController)
                $gambarPath = null;
                if ($request->hasFile('gambar')) {
                    $file = $request->file('gambar');
                    
                    // Validasi ukuran file (1MB)
                    if ($file->getSize() > 1024 * 1024) {
                        throw new \Exception('Ukuran gambar tidak boleh lebih dari 1MB');
                    }
                    
                    // Generate unique filename
                    $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $gambarPath = $file->storeAs('recipes', $filename, 'public');
                }

                // 4. Simpan Resep Dasar
                $recipe = Recipe::create([
                    'nama' => $request->nama,
                    'waktu_masak' => $request->waktu_masak,
                    'region' => $request->region,
                    'deskripsi' => $request->deskripsi,
                    'gambar' => $gambarPath,
                    'kategori' => $request->kategori,
                    
                    // Admin-specific: langsung approved
                    'status' => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    
                    // Data default
                    'created_by' => $request->user()->id,
                    'avg_rating' => 0.00,
                    'total_ratings' => 0,
                    'view_count' => 0,
                ]);

                // 5. Proses Bahan-bahan dengan Classification Service
                $ingredientIds = [];
                $fallbackIngredients = [];

                foreach ($request->bahan_bahan as $bahan) {
                    // Normalisasi nama bahan
                    $normalizedNama = $this->classificationService->normalize($bahan['nama']);
                    
                    // Cari atau buat ingredient dengan klasifikasi otomatis
                    $ingredient = $this->classificationService->findOrCreateIngredient($normalizedNama);
                    
                    // Track ingredients yang menggunakan fallback classification
                    if ($ingredient->sub_kategori === 'umum' || $ingredient->sub_kategori === null) {
                        $fallbackIngredients[] = $ingredient->nama;
                    }

                    // Tentukan apakah bahan utama (bisa di-override oleh admin via input jika perlu)
                    $isMain = $bahan['is_main'] ?? $this->classificationService->isMainIngredient($normalizedNama);

                    // Attach ke recipe_ingredients pivot table
                    $recipe->ingredients()->attach($ingredient->id, [
                        'is_main' => $isMain,
                        'jumlah' => $bahan['jumlah'],
                        'satuan' => $bahan['satuan'] ?? null,
                    ]);

                    $ingredientIds[] = $ingredient->id;
                }

                // 6. Log warning untuk ingredients yang menggunakan fallback
                if (!empty($fallbackIngredients)) {
                    Log::warning('Admin Recipe created with fallback ingredient classifications', [
                        'recipe_id' => $recipe->id,
                        'recipe_name' => $recipe->nama,
                        'admin_id' => $request->user()->id,
                        'fallback_ingredients' => $fallbackIngredients,
                        'count' => count($fallbackIngredients),
                    ]);
                }

                // 7. Generate Recipe Suitability (Kesehatan)
                if (!empty($ingredientIds)) {
                    $this->classificationService->generateRecipeSuitability($recipe->id, $ingredientIds);
                }

                // 8. Simpan Langkah-langkah (jika ada)
                if ($request->filled('langkah_langkah') && !empty($request->langkah_langkah)) {
                    $stepsText = implode("\n", array_map(
                        fn($index, $step) => ($index + 1) . ". " . trim($step),
                        array_keys($request->langkah_langkah),
                        $request->langkah_langkah
                    ));
                    
                    $recipe->deskripsi .= "\n\nLangkah-langkah:\n" . $stepsText;
                    $recipe->save();
                }

                return [
                    'recipe' => $recipe->fresh(['ingredients']),
                    'image_url' => $gambarPath ? Storage::url($gambarPath) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil ditambahkan oleh Admin (langsung approved)',
                'data' => [
                    'recipe' => $result['recipe'],
                    'image_url' => $result['image_url'],
                    'fallback_count' => count($fallbackIngredients ?? []),
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Admin failed to store recipe', [
                'error' => $e->getMessage(),
                'admin_id' => $request->user()?->id,
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan resep: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $recipe = Recipe::select(
        'id', 
        'nama',
        'waktu_masak',
        'region',
        'deskripsi',
        'gambar',
        'kategori',
        'status',
        'total_ratings',
        'created_by',
        'approved_by')
            ->with(['creator:id,name,email', 'approver:id,name,email'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail resep berhasil diambil',
            'data'    => $recipe
        ]);
    }

    public function update(Request $request, $id)
    {
        // Handle method spoofing untuk form-data (jika perlu)
        if ($request->has('_method') && $request->_method === 'PUT') {
            $request->setMethod('PUT');
        }

        // 1. Cek Role Admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Admin only'
            ], 403);
        }

        // 2. Cari Recipe
        $recipe = Recipe::findOrFail($id);

        // 3. Validasi Input (Sesuai struktur RecipeController)
        $validator = Validator::make($request->all(), [
            'nama'          => 'required|string|max:150|min:3',
            'deskripsi'     => 'nullable|string|max:5000',
            'gambar'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:1024', // 1MB
            'waktu_masak'   => 'nullable|integer|min:1|max:1440',
            'region'        => 'nullable|string|max:100',
            'kategori'      => 'nullable|string|max:100',
            
            // Bahan-bahan: array of { nama, jumlah, satuan, is_main? }
            'bahan_bahan'   => 'nullable|array',
            'bahan_bahan.*.nama'    => 'required|string|max:100',
            'bahan_bahan.*.jumlah'  => 'required|string|max:100',
            'bahan_bahan.*.satuan'  => 'nullable|string|max:50',
            'bahan_bahan.*.is_main' => 'nullable|boolean',
            
            // Langkah-langkah (opsional)
            'langkah_langkah' => 'nullable|array',
            'langkah_langkah.*' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $recipe) {
                
                $gambarPath = $recipe->gambar; // Default: pakai gambar lama
                $gambarBaru = false;

                // 4. Handle Upload Gambar Baru (jika ada)
                if ($request->hasFile('gambar')) {
                    $file = $request->file('gambar');
                    
                    // Validasi ukuran file (1MB)
                    if ($file->getSize() > 1024 * 1024) {
                        throw new \Exception('Ukuran gambar tidak boleh lebih dari 1MB');
                    }
                    
                    // Hapus gambar lama dari storage
                    if ($gambarPath && Storage::disk('public')->exists($gambarPath)) {
                        Storage::disk('public')->delete($gambarPath);
                    }
                    
                    // Generate unique filename & upload
                    $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $gambarPath = $file->storeAs('recipes', $filename, 'public');
                    $gambarBaru = true;
                }

                // 5. Update Data Dasar Recipe
                $recipe->update([
                    'nama' => $request->nama,
                    'waktu_masak' => $request->waktu_masak,
                    'region' => $request->region,
                    'deskripsi' => $request->deskripsi, // Akan di-append steps jika ada
                    'gambar' => $gambarPath,
                    'kategori' => $request->kategori,
                    // Admin update: tetap approved, update approved_at
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);

                // 6. Update Bahan-bahan (jika dikirim)
                $ingredientIds = [];
                $fallbackIngredients = [];

                if ($request->filled('bahan_bahan') && is_array($request->bahan_bahan)) {
                    // Hapus semua ingredients lama dari pivot table
                    $recipe->ingredients()->detach();

                    foreach ($request->bahan_bahan as $bahan) {
                        // Normalisasi nama bahan
                        $normalizedNama = $this->classificationService->normalize($bahan['nama']);
                        
                        // Cari atau buat ingredient dengan klasifikasi otomatis
                        $ingredient = $this->classificationService->findOrCreateIngredient($normalizedNama);
                        
                        // Track ingredients yang menggunakan fallback classification
                        if ($ingredient->sub_kategori === 'umum' || $ingredient->sub_kategori === null) {
                            $fallbackIngredients[] = $ingredient->nama;
                        }

                        // Tentukan apakah bahan utama
                        $isMain = $bahan['is_main'] ?? $this->classificationService->isMainIngredient($normalizedNama);

                        // Attach ke pivot table
                        $recipe->ingredients()->attach($ingredient->id, [
                            'is_main' => $isMain,
                            'jumlah' => $bahan['jumlah'],
                            'satuan' => $bahan['satuan'] ?? null,
                        ]);

                        $ingredientIds[] = $ingredient->id;
                    }
                }

                // 7. Log warning untuk fallback ingredients
                if (!empty($fallbackIngredients)) {
                    Log::warning('Admin Recipe updated with fallback ingredient classifications', [
                        'recipe_id' => $recipe->id,
                        'recipe_name' => $recipe->nama,
                        'admin_id' => $request->user()->id,
                        'fallback_ingredients' => $fallbackIngredients,
                        'count' => count($fallbackIngredients),
                    ]);
                }

                // 8. Re-generate Recipe Suitability (jika ada ingredient)
                if (!empty($ingredientIds)) {
                    $this->classificationService->generateRecipeSuitability($recipe->id, $ingredientIds);
                }

                // 9. Update Langkah-langkah (jika ada)
                if ($request->filled('langkah_langkah') && is_array($request->langkah_langkah)) {
                    // Ambil deskripsi dasar (tanpa steps lama)
                    $baseDescription = preg_replace('/\n\nLangkah-langkah:.*$/s', '', $recipe->getOriginal('deskripsi'));
                    
                    // Format steps baru
                    $stepsText = implode("\n", array_map(
                        fn($index, $step) => ($index + 1) . ". " . trim($step),
                        array_keys($request->langkah_langkah),
                        $request->langkah_langkah
                    ));
                    
                    // Update deskripsi dengan steps baru
                    $recipe->deskripsi = $baseDescription . "\n\nLangkah-langkah:\n" . $stepsText;
                    $recipe->save();
                }

                return [
                    'recipe' => $recipe->fresh(['ingredients']),
                    'image_url' => $gambarPath ? Storage::url($gambarPath) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil diupdate oleh Admin',
                'data' => [
                    'recipe' => $result['recipe'],
                    'image_url' => $result['image_url'],
                    'fallback_count' => count($fallbackIngredients ?? []),
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Admin failed to update recipe', [
                'error' => $e->getMessage(),
                'recipe_id' => $id,
                'admin_id' => $request->user()?->id,
                'payload' => $request->all(),
            ]);

            // Rollback gambar jika baru diupload tapi transaction gagal
            if (isset($gambarPath) && $gambarBaru && Storage::disk('public')->exists($gambarPath)) {
                Storage::disk('public')->delete($gambarPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate resep: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $recipe = Recipe::findOrFail($id);

        try {
            DB::beginTransaction();

            // Hapus semua ingredients terkait
            RecipeIngredient::where('recipe_id', $recipe->id)->delete();

            // Hapus gambar dari storage jika ada
            if ($recipe->gambar && Storage::disk('public')->exists($recipe->gambar)) {
                Storage::disk('public')->delete($recipe->gambar);
            }

            // Hapus recipe
            $recipe->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus resep',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $recipe = Recipe::findOrFail($id);

        if ($recipe->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Resep sudah diproses sebelumnya (status: ' . $recipe->status . ')'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Update status recipe
            $recipe->update([
                'status'           => 'approved',
                'approved_by'      => $request->user()->id,
                'approved_at'      => now(),
                'rejection_reason' => null,
            ]);

            // ✨ AUTO ADD POINTS UNTUK USER YANG BUAT RESEP
            $creator = $recipe->creator;
            $pointsAwarded = 0;
            
            if ($creator) {
                $pointsAwarded = config('points.recipe_approved', 10);
                $creator->addPoints($pointsAwarded, "Recipe '{$recipe->nama}' approved");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil disetujui',
                'data'    => [
                    'recipe' => $recipe->fresh()->load(['creator', 'approver']),
                    'points_awarded' => $pointsAwarded,
                    'creator' => [
                        'id' => $creator->id,
                        'name' => $creator->name,
                        'total_points' => $creator->fresh()->points
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $recipe = Recipe::findOrFail($id);

        if ($recipe->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Resep sudah diproses sebelumnya (status: ' . $recipe->status . ')'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:10|max:500',
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi',
            'rejection_reason.min'      => 'Alasan penolakan minimal 10 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $recipe->update([
            'status'           => 'rejected',
            'approved_by'      => null,  // ← UBAH: tidak perlu set approver untuk reject
            'approved_at'      => null,  // ← UBAH: tidak perlu set approved_at untuk reject
            'rejection_reason' => strip_tags($request->rejection_reason),  // ← TAMBAH: sanitasi
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil ditolak',
            'data'    => $recipe->fresh()->load(['creator', 'approver'])
        ]);
    }

    public function statistics()
    {
        $totalRecipes    = Recipe::count();
        $approvedRecipes = Recipe::approved()->count();
        $pendingRecipes  = Recipe::pending()->count();
        $rejectedRecipes = Recipe::rejected()->count();

        // Statistik berdasarkan kategori (hanya yang approved)
        $byKategori = Recipe::approved()
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn($item) => [
                'kategori' => $item->kategori ?? 'Tidak Berkategori',
                'total'    => $item->total,
            ]);

        // Statistik berdasarkan region
        $byRegion = Recipe::approved()
            ->selectRaw('region, COUNT(*) as total')
            ->groupBy('region')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'region' => $item->region ?? 'Tidak Diketahui',
                'total'  => $item->total,
            ]);

        // Top rated recipes
        $topRated = Recipe::approved()
            ->orderBy('avg_rating', 'desc')
            ->orderBy('total_ratings', 'desc')
            ->limit(5)
            ->get(['id', 'nama', 'avg_rating', 'total_ratings', 'view_count'])
            ->map(fn($item) => [
                'id'            => $item->id,
                'nama'          => $item->nama,
                'avg_rating'    => (float) $item->avg_rating,
                'total_ratings' => $item->total_ratings,
                'view_count'    => $item->view_count,
            ]);

        // Most viewed recipes
        $mostViewed = Recipe::approved()
            ->orderBy('view_count', 'desc')
            ->limit(5)
            ->get(['id', 'nama', 'view_count', 'avg_rating'])
            ->map(fn($item) => [
                'id'         => $item->id,
                'nama'       => $item->nama,
                'view_count' => $item->view_count,
                'avg_rating' => (float) $item->avg_rating,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Statistik resep berhasil diambil',
            'data'    => [
                'summary' => [
                    'total_recipes' => $totalRecipes,
                    'approved'     => $approvedRecipes,
                    'pending'      => $pendingRecipes,
                    'rejected'     => $rejectedRecipes,
                ],
                'by_kategori'          => $byKategori,
                'by_region'            => $byRegion,
                'top_rated'            => $topRated,
                'most_viewed'          => $mostViewed,
            ]
        ]);
    }
}