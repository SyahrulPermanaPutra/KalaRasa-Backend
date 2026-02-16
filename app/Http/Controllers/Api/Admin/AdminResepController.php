<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminResepController extends Controller
{
    public function index(Request $request)
    {
        $query = Resep::with(['creator', 'approver']);

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

        $reseps = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Daftar resep berhasil diambil',
            'data'    => $reseps
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'               => 'required|string|max:255',
            'deskripsi'          => 'nullable|string',
            'gambar'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'tingkat_kesulitan'  => 'required|in:mudah,sedang,sulit',
            'waktu_masak'        => 'nullable|integer|min:1',
            'kalori_per_porsi'   => 'nullable|integer|min:0',
            'region'             => 'nullable|string|max:100',
            'kategori'           => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'nama',
            'deskripsi',
            'tingkat_kesulitan',
            'waktu_masak',
            'kalori_per_porsi',
            'region',
            'kategori',
        ]);

        // Set data admin
        $data['created_by']  = $request->user()->id;
        $data['status']      = 'approved'; // Admin langsung approved
        $data['approved_by'] = $request->user()->id;
        $data['approved_at'] = now();

        // Upload gambar jika ada
        if ($request->hasFile('gambar')) {
            $gambarName = time() . '_' . $request->file('gambar')->getClientOriginalName();
            $path = $request->file('gambar')->move(public_path('reseps'), $gambarName);
            $data['gambar'] = 'reseps/' . $gambarName;
        }

        $resep = Resep::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil ditambahkan',
            'data'    => $resep->load(['creator', 'approver'])
        ], 201);
    }

    public function show($id)
    {
        $resep = Resep::with(['creator', 'approver', 'favoritedBy', 'ratings'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail resep berhasil diambil',
            'data'    => $resep
        ]);
    }

    public function update(Request $request, $id)
    {
        $resep = Resep::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama'               => 'sometimes|required|string|max:255',
            'deskripsi'          => 'nullable|string',
            'gambar'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'tingkat_kesulitan'  => 'sometimes|required|in:mudah,sedang,sulit',
            'waktu_masak'        => 'nullable|integer|min:1',
            'kalori_per_porsi'   => 'nullable|integer|min:0',
            'region'             => 'nullable|string|max:100',
            'kategori'           => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'nama',
            'deskripsi',
            'tingkat_kesulitan',
            'waktu_masak',
            'kalori_per_porsi',
            'region',
            'kategori',
        ]);

        // Upload gambar baru jika ada
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama
            if ($resep->gambar && file_exists(public_path($resep->gambar))) {
                unlink(public_path($resep->gambar));
            }

            $gambarName = time() . '_' . $request->file('gambar')->getClientOriginalName();
            $request->file('gambar')->move(public_path('reseps'), $gambarName);
            $data['gambar'] = 'reseps/' . $gambarName;
        }

        $resep->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil diupdate',
            'data'    => $resep->fresh()->load(['creator', 'approver'])
        ]);
    }

    public function destroy($id)
    {
        $resep = Resep::findOrFail($id);

        // Hapus gambar jika ada
        if ($resep->gambar && file_exists(public_path($resep->gambar))) {
            unlink(public_path($resep->gambar));
        }

        $resep->delete();

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil dihapus'
        ]);
    }

    public function approve(Request $request, $id)
    {
        $resep = Resep::findOrFail($id);

        if ($resep->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Resep sudah diproses sebelumnya (status: ' . $resep->status . ')'
            ], 400);
        }

        $resep->update([
            'status'           => 'approved',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil disetujui',
            'data'    => $resep->fresh()->load(['creator', 'approver'])
        ]);
    }

    public function reject(Request $request, $id)
    {
        $resep = Resep::findOrFail($id);

        if ($resep->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Resep sudah diproses sebelumnya (status: ' . $resep->status . ')'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:10',
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

        $resep->update([
            'status'           => 'rejected',
            'approved_by'      => $request->user()->id,
            'approved_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil ditolak',
            'data'    => $resep->fresh()->load(['creator', 'approver'])
        ]);
    }

    public function statistics()
    {
        $totalReseps    = Resep::count();
        $approvedReseps = Resep::approved()->count();
        $pendingReseps  = Resep::pending()->count();
        $rejectedReseps = Resep::rejected()->count();

        // Statistik berdasarkan kategori (hanya yang approved)
        $byKategori = Resep::approved()
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->orderBy('total', 'desc')
            ->get()
            ->map(fn($item) => [
                'kategori' => $item->kategori ?? 'Tidak Berkategori',
                'total'    => $item->total,
            ]);

        // Statistik berdasarkan tingkat kesulitan
        $byTingkatKesulitan = Resep::approved()
            ->selectRaw('tingkat_kesulitan, COUNT(*) as total')
            ->groupBy('tingkat_kesulitan')
            ->orderByRaw("FIELD(tingkat_kesulitan, 'mudah', 'sedang', 'sulit')")
            ->get()
            ->map(fn($item) => [
                'tingkat_kesulitan' => $item->tingkat_kesulitan,
                'total'             => $item->total,
            ]);

        // Statistik berdasarkan region
        $byRegion = Resep::approved()
            ->selectRaw('region, COUNT(*) as total')
            ->groupBy('region')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($item) => [
                'region' => $item->region ?? 'Tidak Diketahui',
                'total'  => $item->total,
            ]);

        // Top rated reseps
        $topRated = Resep::approved()
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

        // Most viewed reseps
        $mostViewed = Resep::approved()
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
                    'total_reseps' => $totalReseps,
                    'approved'     => $approvedReseps,
                    'pending'      => $pendingReseps,
                    'rejected'     => $rejectedReseps,
                ],
                'by_kategori'          => $byKategori,
                'by_tingkat_kesulitan' => $byTingkatKesulitan,
                'by_region'            => $byRegion,
                'top_rated'            => $topRated,
                'most_viewed'          => $mostViewed,
            ]
        ]);
    }
}