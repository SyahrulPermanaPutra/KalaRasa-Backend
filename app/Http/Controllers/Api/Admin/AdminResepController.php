<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resep;
use Illuminate\Http\Request;

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

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_resep', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        $reseps = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reseps
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_resep' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'bahan_makanan' => 'required|array',
            'bahan_makanan.*.nama' => 'required|string',
            'bahan_makanan.*.jumlah' => 'required',
            'bahan_makanan.*.satuan' => 'required|string',
            'cara_memasak' => 'required|string',
            'porsi' => 'required|integer|min:1',
            'waktu_memasak' => 'nullable|integer|min:1',
            'tingkat_kesulitan' => 'required|in:mudah,sedang,sulit',
            'kategori' => 'nullable|string|max:100',
        ]);

        $data = $request->all();
        $data['created_by'] = auth()->id();
        $data['status'] = 'approved'; // Admin langsung approved
        $data['approved_by'] = auth()->id();
        $data['approved_at'] = now();

        if ($request->hasFile('gambar')) {
            $gambarName = time() . '.' . $request->gambar->extension();
            $request->gambar->move(public_path('reseps'), $gambarName);
            $data['gambar'] = 'reseps/' . $gambarName;
        }

        $resep = Resep::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil ditambahkan',
            'data' => $resep
        ], 201);
    }

    public function show($id)
    {
        $resep = Resep::with(['creator', 'approver', 'favoritedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $resep
        ]);
    }

    public function update(Request $request, $id)
    {
        $resep = Resep::findOrFail($id);

        $request->validate([
            'nama_resep' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'bahan_makanan' => 'required|array',
            'bahan_makanan.*.nama' => 'required|string',
            'bahan_makanan.*.jumlah' => 'required',
            'bahan_makanan.*.satuan' => 'required|string',
            'cara_memasak' => 'required|string',
            'porsi' => 'required|integer|min:1',
            'waktu_memasak' => 'nullable|integer|min:1',
            'tingkat_kesulitan' => 'required|in:mudah,sedang,sulit',
            'kategori' => 'nullable|string|max:100',
        ]);

        $data = $request->except('gambar');

        if ($request->hasFile('gambar')) {
            // Delete old image
            if ($resep->gambar && file_exists(public_path($resep->gambar))) {
                unlink(public_path($resep->gambar));
            }

            $gambarName = time() . '.' . $request->gambar->extension();
            $request->gambar->move(public_path('reseps'), $gambarName);
            $data['gambar'] = 'reseps/' . $gambarName;
        }

        $resep->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil diupdate',
            'data' => $resep
        ]);
    }

    public function destroy($id)
    {
        $resep = Resep::findOrFail($id);

        // Delete image if exists
        if ($resep->gambar && file_exists(public_path($resep->gambar))) {
            unlink(public_path($resep->gambar));
        }

        $resep->delete();

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil dihapus'
        ]);
    }

    public function approve($id)
    {
        $resep = Resep::findOrFail($id);

        if ($resep->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Resep sudah diproses sebelumnya'
            ], 400);
        }

        $resep->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil disetujui',
            'data' => $resep
        ]);
    }

    public function reject(Request $request, $id)
    {
        $resep = Resep::findOrFail($id);

        if ($resep->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Resep sudah diproses sebelumnya'
            ], 400);
        }

        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $resep->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resep berhasil ditolak',
            'data' => $resep
        ]);
    }

    public function statistics()
    {
        $totalReseps = Resep::count();
        $approvedReseps = Resep::approved()->count();
        $pendingReseps = Resep::pending()->count();
        $rejectedReseps = Resep::rejected()->count();

        $byKategori = Resep::approved()
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->get();

        $byTingkatKesulitan = Resep::approved()
            ->selectRaw('tingkat_kesulitan, COUNT(*) as total')
            ->groupBy('tingkat_kesulitan')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_reseps' => $totalReseps,
                'approved' => $approvedReseps,
                'pending' => $pendingReseps,
                'rejected' => $rejectedReseps,
                'by_kategori' => $byKategori,
                'by_tingkat_kesulitan' => $byTingkatKesulitan,
            ]
        ]);
    }
}
