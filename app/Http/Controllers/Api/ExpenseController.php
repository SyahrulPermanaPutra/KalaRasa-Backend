<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->expenses();

        // Filter berdasarkan tanggal
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Filter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $expenses = $query->orderBy('tanggal_transaksi', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $expenses
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal_transaksi' => 'required|date',
            'nama_item' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'satuan' => 'required|string|max:50',
            'harga_satuan' => 'required|numeric|min:0',
            'kategori' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
        ]);

        $totalHarga = $request->harga_satuan * $request->jumlah;

        $expense = $request->user()->expenses()->create([
            'tanggal_transaksi' => $request->tanggal_transaksi,
            'nama_item' => $request->nama_item,
            'jumlah' => $request->jumlah,
            'satuan' => $request->satuan,
            'harga_satuan' => $request->harga_satuan,
            'total_harga' => $totalHarga,
            'kategori' => $request->kategori,
            'catatan' => $request->catatan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil ditambahkan',
            'data' => $expense
        ], 201);
    }

    public function show($id)
    {
        $expense = Expense::where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $expense
        ]);
    }

    public function destroy($id)
    {
        $expense = Expense::where('user_id', auth()->id())
            ->findOrFail($id);

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dihapus'
        ]);
    }

    // Rekap Harian
    public function rekapHarian(Request $request)
    {
        $date = $request->input('date', today());
        
        $expenses = $request->user()
            ->expenses()
            ->whereDate('tanggal_transaksi', $date)
            ->get();

        $total = $expenses->sum('total_harga');
        $byKategori = $expenses->groupBy('kategori')->map(function ($items) {
            return [
                'jumlah_item' => $items->count(),
                'total' => $items->sum('total_harga')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tanggal' => $date,
                'total_pengeluaran' => $total,
                'jumlah_transaksi' => $expenses->count(),
                'by_kategori' => $byKategori,
                'detail' => $expenses
            ]
        ]);
    }

    // Rekap Mingguan
    public function rekapMingguan(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfWeek());
        $endDate = $request->input('end_date', now()->endOfWeek());
        
        $expenses = $request->user()
            ->expenses()
            ->byDateRange($startDate, $endDate)
            ->get();

        $total = $expenses->sum('total_harga');
        $byKategori = $expenses->groupBy('kategori')->map(function ($items) {
            return [
                'jumlah_item' => $items->count(),
                'total' => $items->sum('total_harga')
            ];
        });

        // Group by tanggal
        $byTanggal = $expenses->groupBy(function($item) {
            return $item->tanggal_transaksi->format('Y-m-d');
        })->map(function ($items) {
            return [
                'jumlah_transaksi' => $items->count(),
                'total' => $items->sum('total_harga')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'periode' => [
                    'start' => $startDate,
                    'end' => $endDate
                ],
                'total_pengeluaran' => $total,
                'jumlah_transaksi' => $expenses->count(),
                'by_kategori' => $byKategori,
                'by_tanggal' => $byTanggal,
            ]
        ]);
    }

    // Rekap Bulanan
    public function rekapBulanan(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        
        $expenses = $request->user()
            ->expenses()
            ->whereMonth('tanggal_transaksi', $month)
            ->whereYear('tanggal_transaksi', $year)
            ->get();

        $total = $expenses->sum('total_harga');
        $byKategori = $expenses->groupBy('kategori')->map(function ($items) {
            return [
                'jumlah_item' => $items->count(),
                'total' => $items->sum('total_harga')
            ];
        });

        // Group by minggu
        $byMinggu = $expenses->groupBy(function($item) {
            return 'Minggu ' . $item->tanggal_transaksi->weekOfMonth;
        })->map(function ($items) {
            return [
                'jumlah_transaksi' => $items->count(),
                'total' => $items->sum('total_harga')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'periode' => [
                    'month' => $month,
                    'year' => $year,
                    'nama_bulan' => Carbon::create($year, $month)->locale('id')->monthName
                ],
                'total_pengeluaran' => $total,
                'jumlah_transaksi' => $expenses->count(),
                'rata_rata_harian' => $expenses->count() > 0 ? $total / now()->daysInMonth : 0,
                'by_kategori' => $byKategori,
                'by_minggu' => $byMinggu,
            ]
        ]);
    }
}
