<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class AdminRecipeController extends Controller
{
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
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama'               => 'required|string|max:150|min:3',
            'deskripsi'          => 'nullable|string|max:2000',
            'gambar'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2120',
            'waktu_masak'        => 'nullable|integer|min:1|max:600',
            'region'             => 'nullable|string|max:100',
            'kategori'           => 'nullable|string|max:100',
            
            // Validasi ingredients
            'ingredients'        => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.is_main'       => 'required|boolean',
            'ingredients.*.jumlah'        => 'required|string|max:100',
            'ingredients.*.satuan'        => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->only([
                'nama',
                'deskripsi',
                'waktu_masak',
                'region',
                'kategori',
            ]);

            // Sanitasi
            $data = array_map(fn($v) => is_string($v) ? strip_tags($v) : $v, $data);

            // Set data admin
            $data['created_by']  = $request->user()->id;
            $data['status']      = 'approved'; // Admin langsung approved
            $data['approved_by'] = $request->user()->id;
            $data['approved_at'] = now();

            // Upload gambar jika ada
            if ($request->hasFile('gambar')) {
                $path = $request->file('gambar')
                    ->store('recipes', 'public');

                $data['gambar'] = $path;
            }

            // Buat recipe
            $recipe = Recipe::create($data);

            // Tambahkan ingredients jika ada
            if ($request->has('ingredients') && is_array($request->ingredients)) {
                foreach ($request->ingredients as $ingredientData) {
                    RecipeIngredient::create([
                        'recipe_id'     => $recipe->id,
                        'ingredient_id' => $ingredientData['ingredient_id'],
                        'is_main'       => $ingredientData['is_main'],
                        'jumlah'        => strip_tags($ingredientData['jumlah']),
                        'satuan'        => isset($ingredientData['satuan']) ? strip_tags($ingredientData['satuan']) : null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil ditambahkan',
                'data'    => $recipe->load([
                    'creator', 
                    'approver',
                    'recipeIngredients.ingredient'
                ])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Hapus gambar jika sudah terupload
            if (isset($data['gambar']) && Storage::disk('public')->exists($data['gambar'])) {
                Storage::disk('public')->delete($data['gambar']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan resep',
                'error'   => $e->getMessage()
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
        // Handle method spoofing untuk form-data
        if ($request->has('_method') && $request->_method === 'PUT') {
            $request->setMethod('PUT');
        }
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $recipe = Recipe::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama'               => 'required|string|max:150|min:3',
            'deskripsi'          => 'nullable|string|max:2000',
            'gambar'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2120',
            'waktu_masak'        => 'nullable|integer|min:1|max:600',
            'region'             => 'nullable|string|max:100',
            'kategori'           => 'nullable|string|max:100',
            
            // Validasi ingredients
            'ingredients'        => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.is_main'       => 'required|boolean',
            'ingredients.*.jumlah'        => 'required|string|max:100',
            'ingredients.*.satuan'        => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->only([
                'nama',
                'deskripsi',
                'waktu_masak',
                'region',
                'kategori',
            ]);

            // Sanitasi
            $data = array_map(fn($v) => is_string($v) ? strip_tags($v) : $v, $data);

            // Upload gambar baru jika ada
            if ($request->hasFile('gambar')) {
                // Hapus gambar lama dari storage
                if ($recipe->gambar && Storage::disk('public')->exists($recipe->gambar)) {
                    Storage::disk('public')->delete($recipe->gambar);
                }

                $path = $request->file('gambar')->store('recipes', 'public');
                $data['gambar'] = $path;
            }

            // Update recipe
            $recipe->update($data);

            // Update ingredients jika ada
            if ($request->has('ingredients')) {
                // Hapus semua ingredients lama
                RecipeIngredient::where('recipe_id', $recipe->id)->delete();

                // Tambahkan ingredients baru
                if (is_array($request->ingredients) && count($request->ingredients) > 0) {
                    foreach ($request->ingredients as $ingredientData) {
                        RecipeIngredient::create([
                            'recipe_id'     => $recipe->id,
                            'ingredient_id' => $ingredientData['ingredient_id'],
                            'is_main'       => $ingredientData['is_main'],
                            'jumlah'        => strip_tags($ingredientData['jumlah']),
                            'satuan'        => isset($ingredientData['satuan']) ? strip_tags($ingredientData['satuan']) : null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Resep berhasil diupdate',
                'data'    => $recipe->fresh()->load([
                    'creator', 
                    'approver',
                    'recipeIngredients.ingredient'
                ])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Hapus gambar baru jika sudah terupload
            if (isset($data['gambar']) && Storage::disk('public')->exists($data['gambar'])) {
                Storage::disk('public')->delete($data['gambar']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupdate resep',
                'error'   => $e->getMessage()
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
        $recipe = Recipe::findOrFail($id);

        if ($recipe->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Resep sudah diproses sebelumnya (status: ' . $recipe->status . ')'
            ], 400);
        }

        $recipe->update([
            'status'           => 'approved',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil disetujui',
            'data'    => $recipe->fresh()->load(['creator', 'approver'])
        ]);
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