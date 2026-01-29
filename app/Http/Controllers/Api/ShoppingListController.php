<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Models\Expense;
use Illuminate\Http\Request;

class ShoppingListController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->shoppingLists();

        // Filter berdasarkan status
        if ($request->has('status')) {
            if ($request->status === 'belum_dibeli') {
                $query->belumDibeli();
            } elseif ($request->status === 'sudah_dibeli') {
                $query->sudahDibeli();
            }
        }

        // Filter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $shoppingLists = $query->orderBy('created_at', 'desc')->get();

        // Hitung total
        $totalHarga = $shoppingLists->sum('harga');
        $totalItem = $shoppingLists->count();
        $sudahDibeli = $shoppingLists->where('sudah_dibeli', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $shoppingLists,
                'summary' => [
                    'total_items' => $totalItem,
                    'items_dibeli' => $sudahDibeli,
                    'items_belum_dibeli' => $totalItem - $sudahDibeli,
                    'total_harga' => $totalHarga,
                ]
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_item' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'satuan' => 'required|string|max:50',
            'harga' => 'nullable|numeric|min:0',
            'kategori' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
        ]);

        $shoppingList = $request->user()->shoppingLists()->create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil ditambahkan ke daftar belanja',
            'data' => $shoppingList
        ], 201);
    }

    public function show($id)
    {
        $shoppingList = ShoppingList::where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $shoppingList
        ]);
    }

    public function update(Request $request, $id)
    {
        $shoppingList = ShoppingList::where('user_id', auth()->id())
            ->findOrFail($id);

        $request->validate([
            'nama_item' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'satuan' => 'required|string|max:50',
            'harga' => 'nullable|numeric|min:0',
            'kategori' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
        ]);

        $shoppingList->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil diupdate',
            'data' => $shoppingList
        ]);
    }

    public function destroy($id)
    {
        $shoppingList = ShoppingList::where('user_id', auth()->id())
            ->findOrFail($id);

        $shoppingList->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus'
        ]);
    }

    public function updateHarga(Request $request, $id)
    {
        $shoppingList = ShoppingList::where('user_id', auth()->id())
            ->findOrFail($id);

        $request->validate([
            'harga' => 'required|numeric|min:0',
        ]);

        $shoppingList->update(['harga' => $request->harga]);

        return response()->json([
            'success' => true,
            'message' => 'Harga berhasil diupdate',
            'data' => $shoppingList
        ]);
    }

    public function markAsBought(Request $request, $id)
    {
        $shoppingList = ShoppingList::where('user_id', auth()->id())
            ->findOrFail($id);

        $request->validate([
            'harga' => 'required|numeric|min:0',
        ]);

        $shoppingList->update([
            'harga' => $request->harga,
            'sudah_dibeli' => true,
            'tanggal_dibeli' => now(),
        ]);

        // Pindahkan ke riwayat pengeluaran
        Expense::create([
            'user_id' => auth()->id(),
            'tanggal_transaksi' => now(),
            'nama_item' => $shoppingList->nama_item,
            'jumlah' => $shoppingList->jumlah,
            'satuan' => $shoppingList->satuan,
            'harga_satuan' => $request->harga,
            'total_harga' => $request->harga * $shoppingList->jumlah,
            'kategori' => $shoppingList->kategori,
            'catatan' => $shoppingList->catatan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item ditandai sudah dibeli dan ditambahkan ke riwayat pengeluaran',
            'data' => $shoppingList
        ]);
    }

    public function calculateTotal(Request $request)
    {
        $total = $request->user()
            ->shoppingLists()
            ->whereNotNull('harga')
            ->sum('harga');

        return response()->json([
            'success' => true,
            'data' => [
                'total_pengeluaran' => $total
            ]
        ]);
    }
}
