<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShoppingListController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GRAFIK PENGELUARAN
    // ──────────────────────────────────────────────────────────

    /**
     * GET /api/shopping-lists/grafik/harian
     * Data pengeluaran 7 hari terakhir
     */
    public function grafikHarian(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today();

        // Ambil data 7 hari terakhir
        $data = collect(range(6, 0))->map(function ($daysAgo) use ($user, $today) {
            $date = $today->copy()->subDays($daysAgo);

            $total = Expense::where('user_id', $user->id)
                ->whereDate('purchase_date', $date)
                ->sum('actual_price');

            return [
                'label'  => $date->translatedFormat('D, d M'),   // Mis: "Sen, 03 Feb"
                'date'   => $date->format('Y-m-d'),
                'total'  => (float) $total,
            ];
        });

        $avg = $data->avg('total');

        return response()->json([
            'success' => true,
            'message' => 'Grafik harian berhasil diambil',
            'data'    => [
                'periode'        => '7 hari terakhir',
                'rata_rata'      => round($avg, 2),
                'total_periode'  => $data->sum('total'),
                'grafik'         => $data->values(),
            ]
        ]);
    }

    /**
     * GET /api/shopping-lists/grafik/mingguan
     * Data pengeluaran 4 minggu terakhir
     */
    public function grafikMingguan(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today();

        $data = collect(range(3, 0))->map(function ($weeksAgo) use ($user, $today) {
            $startOfWeek = $today->copy()->subWeeks($weeksAgo)->startOfWeek();
            $endOfWeek   = $startOfWeek->copy()->endOfWeek();

            $total = Expense::where('user_id', $user->id)
                ->whereBetween('purchase_date', [$startOfWeek, $endOfWeek])
                ->sum('actual_price');

            return [
                'label'       => 'Minggu ' . $startOfWeek->format('d M'),
                'start_date'  => $startOfWeek->format('Y-m-d'),
                'end_date'    => $endOfWeek->format('Y-m-d'),
                'total'       => (float) $total,
            ];
        });

        $avg = $data->avg('total');

        return response()->json([
            'success' => true,
            'message' => 'Grafik mingguan berhasil diambil',
            'data'    => [
                'periode'       => '4 minggu terakhir',
                'rata_rata'     => round($avg, 2),
                'total_periode' => $data->sum('total'),
                'grafik'        => $data->values(),
            ]
        ]);
    }

    /**
     * GET /api/shopping-lists/grafik/bulanan
     * Data pengeluaran 12 bulan terakhir
     */
    public function grafikBulanan(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today();

        $data = collect(range(11, 0))->map(function ($monthsAgo) use ($user, $today) {
            $month = $today->copy()->subMonths($monthsAgo);

            $total = Expense::where('user_id', $user->id)
                ->whereYear('purchase_date', $month->year)
                ->whereMonth('purchase_date', $month->month)
                ->sum('actual_price');

            return [
                'label'  => $month->translatedFormat('M Y'),   // Mis: "Jan 2025"
                'month'  => $month->format('Y-m'),
                'total'  => (float) $total,
            ];
        });

        $avg = $data->avg('total');

        return response()->json([
            'success' => true,
            'message' => 'Grafik bulanan berhasil diambil',
            'data'    => [
                'periode'       => '12 bulan terakhir',
                'rata_rata'     => round($avg, 2),
                'total_periode' => $data->sum('total'),
                'grafik'        => $data->values(),
            ]
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // MEMO / SHOPPING LIST CRUD
    // ──────────────────────────────────────────────────────────

    /**
     * GET /api/shopping-lists
     * History semua memo milik user
     */
    public function index(Request $request)
    {
        $query = ShoppingList::where('user_id', $request->user()->id)
            ->with([
                'recipe:id,nama_recipe,gambar',
                'items',
            ])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by tanggal
        if ($request->has('bulan') && $request->has('tahun')) {
            $query->whereMonth('shopping_date', $request->bulan)
                  ->whereYear('shopping_date', $request->tahun);
        }

        $lists = $query->paginate($request->input('per_page', 10));

        // Tambahkan summary di setiap item
        $lists->getCollection()->transform(function ($list) {
            $list->total_items     = $list->items->count();
            $list->purchased_items = $list->items->where('is_purchased', true)->count();
            $list->progress        = $list->total_items > 0
                ? round(($list->purchased_items / $list->total_items) * 100)
                : 0;
            unset($list->items); // Hapus items dari list (hanya summary)
            return $list;
        });

        return response()->json([
            'success' => true,
            'message' => 'History memo berhasil diambil',
            'data'    => $lists,
        ]);
    }

    /**
     * POST /api/shopping-lists
     * Buat memo baru secara manual
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_list'     => 'required|string|max:255',
            'shopping_date' => 'nullable|date',
            'catatan'       => 'nullable|string',
            'items'         => 'nullable|array',
            'items.*.nama_item'       => 'required_with:items|string|max:255',
            'items.*.jumlah'          => 'required_with:items|numeric|min:0',
            'items.*.satuan'          => 'required_with:items|string|max:50',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'items.*.catatan'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Buat shopping list / memo
            $list = ShoppingList::create([
                'user_id'       => $request->user()->id,
                'nama_list'     => $request->nama_list,
                'shopping_date' => $request->shopping_date,
                'catatan'       => $request->catatan,
                'status'        => 'pending',
            ]);

            // Tambahkan items jika ada
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    ShoppingListItem::create([
                        'shopping_list_id' => $list->id,
                        'nama_item'        => $item['nama_item'],
                        'jumlah'           => $item['jumlah'],
                        'satuan'           => $item['satuan'],
                        'estimated_price'  => $item['estimated_price'] ?? 0,
                        'catatan'          => $item['catatan'] ?? null,
                    ]);
                }
            }

            // Hitung total estimasi
            $list->recalculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Memo berhasil dibuat',
                'data'    => $list->load('items'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat memo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/shopping-lists/from-recipe/{id}
     * Buat memo dari recipe (auto-fill bahan)
     */
    public function storeFromRecipe(Request $request, $recipeId)
    {
        $validator = Validator::make($request->all(), [
            'nama_list'     => 'nullable|string|max:255',
            'shopping_date' => 'nullable|date',
            'catatan'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $recipe = Recipe::approved()
            ->with('ingredients')
            ->findOrFail($recipeId);

        DB::beginTransaction();
        try {
            // Buat shopping list dari recipe
            $list = ShoppingList::create([
                'user_id'       => $request->user()->id,
                'recipe_id'     => $recipe->id,
                'nama_list'     => $request->nama_list ?? 'Belanja - ' . $recipe->nama_recipe,
                'shopping_date' => $request->shopping_date ?? now()->format('Y-m-d'),
                'catatan'       => $request->catatan,
                'status'        => 'pending',
            ]);

            // Auto-fill items dari bahan recipe
            if ($recipe->ingredients->count() > 0) {
                // Jika relasi ingredients ada
                foreach ($recipe->ingredients as $ingredient) {
                    ShoppingListItem::create([
                        'shopping_list_id' => $list->id,
                        'ingredient_id'    => $ingredient->id,
                        'nama_item'        => $ingredient->nama,
                        'jumlah'           => $ingredient->pivot->jumlah,
                        'satuan'           => $ingredient->pivot->satuan,
                        'estimated_price'  => $ingredient->avg_price ?? 0,
                        'catatan'          => $ingredient->pivot->keterangan,
                    ]);
                }
            } elseif (!empty($recipe->bahan_makanan)) {
                // Fallback: Jika pakai JSON bahan_makanan
                foreach ($recipe->bahan_makanan as $bahan) {
                    $namaItem = is_array($bahan) ? ($bahan['nama'] ?? 'Unknown') : $bahan;
                    $jumlah   = is_array($bahan) ? ($bahan['jumlah'] ?? 1) : 1;
                    $satuan   = is_array($bahan) ? ($bahan['satuan'] ?? 'pcs') : 'pcs';

                    ShoppingListItem::create([
                        'shopping_list_id' => $list->id,
                        'nama_item'        => $namaItem,
                        'jumlah'           => $jumlah,
                        'satuan'           => $satuan,
                        'estimated_price'  => 0,
                    ]);
                }
            }

            $list->recalculateTotals();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Memo berhasil dibuat dari resep ' . $recipe->nama_recipe,
                'data'    => $list->load(['items', 'recipe:id,nama_recipe,gambar']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat memo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/shopping-lists/{id}
     * Detail memo + semua item
     */
    public function show(Request $request, $id)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)
            ->with([
                'recipe:id,nama_recipe,gambar,kategori',
                'items.ingredient:id,nama,satuan',
            ])
            ->findOrFail($id);

        $items         = $list->items;
        $totalItems    = $items->count();
        $purchasedItems = $items->where('is_purchased', true)->count();

        return response()->json([
            'success' => true,
            'message' => 'Detail memo berhasil diambil',
            'data'    => [
                'id'             => $list->id,
                'nama_list'      => $list->nama_list,
                'shopping_date'  => $list->shopping_date,
                'status'         => $list->status,
                'catatan'        => $list->catatan,
                'recipe'         => $list->recipe,

                // Progres belanja
                'progres' => [
                    'total_items'     => $totalItems,
                    'purchased_items' => $purchasedItems,
                    'remaining_items' => $totalItems - $purchasedItems,
                    'percentage'      => $totalItems > 0
                        ? round(($purchasedItems / $totalItems) * 100)
                        : 0,
                ],

                // Ringkasan harga
                'ringkasan_harga' => [
                    'total_estimasi'    => (float) $list->total_estimated_price,
                    'total_sudah_beli'  => (float) $items->whereNotNull('actual_price')->sum('actual_price'),
                    'total_belum_beli'  => (float) $items->where('is_purchased', false)->sum('estimated_price'),
                    'total_aktual'      => (float) $list->total_actual_price,
                    'selisih'           => (float) ($list->total_actual_price - $list->total_estimated_price),
                ],

                // Item belanja
                'items' => $items->map(fn($item) => [
                    'id'              => $item->id,
                    'nama_item'       => $item->nama_item,
                    'jumlah'          => (float) $item->jumlah,
                    'satuan'          => $item->satuan,
                    'estimated_price' => (float) $item->estimated_price,
                    'actual_price'    => $item->actual_price ? (float) $item->actual_price : null,
                    'is_purchased'    => $item->is_purchased,
                    'purchased_at'    => $item->purchased_at,
                    'catatan'         => $item->catatan,
                    'ingredient'      => $item->ingredient,
                ]),

                'created_at' => $list->created_at,
                'updated_at' => $list->updated_at,
            ]
        ]);
    }

    /**
     * PUT /api/shopping-lists/{id}
     * Update memo (nama, tanggal, catatan)
     */
    public function update(Request $request, $id)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama_list'     => 'sometimes|string|max:255',
            'shopping_date' => 'sometimes|date',
            'catatan'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $list->update($request->only(['nama_list', 'shopping_date', 'catatan']));

        return response()->json([
            'success' => true,
            'message' => 'Memo berhasil diupdate',
            'data'    => $list->load('items'),
        ]);
    }

    /**
     * DELETE /api/shopping-lists/{id}
     * Hapus memo beserta items dan expenses-nya
     */
    public function destroy(Request $request, $id)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)->findOrFail($id);
        $list->delete();

        return response()->json([
            'success' => true,
            'message' => 'Memo berhasil dihapus',
        ]);
    }
}
