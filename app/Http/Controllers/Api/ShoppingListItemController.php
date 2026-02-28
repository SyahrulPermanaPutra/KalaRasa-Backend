<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use App\Models\Expense;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ShoppingListItemController extends Controller
{
    /**
     * GET /api/shopping-lists/{listId}/items
     * List semua item dalam shopping list
     */
    public function index(Request $request, $listId)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)
            ->findOrFail($listId);

            $items = $list->items()
                ->with('ingredient:id,nama')
                ->orderBy('is_purchased', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            // Ambil mapping jumlah dan satuan dari recipe_ingredient
            $recipeIngredients = [];
            if ($list->recipe_id) {
                $recipeIngredients = \App\Models\RecipeIngredient::where('recipe_id', $list->recipe_id)
                    ->get()
                    ->keyBy('ingredient_id');
            }

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil diambil',
                'data'    => $items->map(function($item) use ($recipeIngredients) {
                    $jumlah = $item->jumlah;
                    $satuan = $item->satuan;
                    if ($item->ingredient_id && isset($recipeIngredients[$item->ingredient_id])) {
                        $jumlah = $recipeIngredients[$item->ingredient_id]->jumlah;
                        $satuan = $recipeIngredients[$item->ingredient_id]->satuan;
                    }
                    return [
                        'id'              => $item->id,
                        'nama_item'       => $item->nama_item,
                        'jumlah'          => (float) $jumlah,
                        'satuan'          => $satuan,
                        'estimated_price' => (float) $item->estimated_price,
                        'actual_price'    => $item->actual_price ? (float) $item->actual_price : null,
                        'is_purchased'    => $item->is_purchased,
                        'ingredient'      => $item->ingredient,
                        'catatan'         => $item->catatan,
                        'purchased_at'    => $item->purchased_at,
                    ];
                }),
            ]);
    }

    /**
     * POST /api/shopping-lists/{listId}/items
     * Tambah item baru ke shopping list
     */
    public function store(Request $request, $listId)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)
            ->findOrFail($listId);

        $validator = Validator::make($request->all(), [
            'ingredient_id'   => 'nullable|exists:ingredients,id',
            'nama_item'       => 'required|string|max:255',
            'jumlah'          => 'required|numeric|min:0',
            'satuan'          => 'required|string|max:50',
            'estimated_price' => 'nullable|numeric|min:0',
            'catatan'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $item = ShoppingListItem::create([
            'shopping_list_id' => $list->id,
            'ingredient_id'    => $request->ingredient_id,
            'nama_item'        => $request->nama_item,
            'jumlah'           => $request->jumlah,
            'satuan'           => $request->satuan,
            'estimated_price'  => $request->estimated_price ?? 0,
            'catatan'          => $request->catatan,
        ]);

        // Update total estimasi
        $list->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil ditambahkan',
            'data'    => $item->load('ingredient'),
        ], 201);
    }

    /**
     * GET /api/shopping-lists/{listId}/items/{itemId}
     * Detail item
     */
    public function show(Request $request, $listId, $itemId)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)
            ->findOrFail($listId);

        $item = ShoppingListItem::where('shopping_list_id', $listId)
            ->with('ingredient:id,nama')
            ->findOrFail($itemId);

        // Ambil mapping jumlah dan satuan dari recipe_ingredient
        $list = ShoppingList::find($listId);
        $jumlah = $item->jumlah;
        $satuan = $item->satuan;
        if ($list && $list->recipe_id && $item->ingredient_id) {
            $recipeIngredient = \App\Models\RecipeIngredient::where('recipe_id', $list->recipe_id)
                ->where('ingredient_id', $item->ingredient_id)
                ->first();
            if ($recipeIngredient) {
                $jumlah = $recipeIngredient->jumlah;
                $satuan = $recipeIngredient->satuan;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail item berhasil diambil',
            'data'    => [
                'id'              => $item->id,
                'nama_item'       => $item->nama_item,
                'jumlah'          => (float) $jumlah,
                'satuan'          => $satuan,
                'estimated_price' => (float) $item->estimated_price,
                'actual_price'    => $item->actual_price ? (float) $item->actual_price : null,
                'is_purchased'    => $item->is_purchased,
                'purchased_at'    => $item->purchased_at,
                'catatan'         => $item->catatan,
                'ingredient'      => $item->ingredient,
                'created_at'      => $item->created_at,
                'updated_at'      => $item->updated_at,
            ]
        ]);
    }

    /**
     * PUT /api/shopping-lists/{listId}/items/{itemId}
     * Update item (nama, jumlah, harga, catatan)
     */
    public function update(Request $request, $listId, $itemId)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)
            ->findOrFail($listId);

        $item = ShoppingListItem::where('shopping_list_id', $list->id)
            ->findOrFail($itemId);

        $validator = Validator::make($request->all(), [
            'ingredient_id'   => 'nullable|exists:ingredients,id',
            'nama_item'       => 'sometimes|string|max:255',
            'jumlah'          => 'sometimes|numeric|min:0',
            'satuan'          => 'sometimes|string|max:50',
            'estimated_price' => 'nullable|numeric|min:0',
            'actual_price'    => 'nullable|numeric|min:0',
            'catatan'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $item->update($request->only([
            'ingredient_id',
            'nama_item',
            'jumlah',
            'satuan',
            'estimated_price',
            'actual_price',
            'catatan'
        ]));

        // Update total
        $list->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil diupdate',
            'data'    => $item->load('ingredient'),
        ]);
    }

    /**
     * PATCH /api/shopping-lists/{listId}/items/{itemId}/toggle-purchased
     * Toggle status purchased item
     */
    public function togglePurchased(Request $request, $listId, $itemId)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)
            ->findOrFail($listId);

        $item = ShoppingListItem::where('shopping_list_id', $list->id)
            ->findOrFail($itemId);

        $validator = Validator::make($request->all(), [
            'actual_price' => 'nullable|numeric|min:0',
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
            // Toggle purchased status
            $newStatus = !$item->is_purchased;
            
            if ($newStatus) {
                // Tandai sudah dibeli
                $item->update([
                    'is_purchased' => true,
                    'purchased_at' => now(),
                    'actual_price' => $request->actual_price ?? $item->estimated_price,
                ]);

                // Buat expense record
                Expense::create([
                    'user_id'           => $request->user()->id,
                    'shopping_list_id'  => $list->id,
                    'shopping_list_item_id' => $item->id,
                    'ingredient_id'     => $item->ingredient_id,
                    'nama_item'         => $item->nama_item,
                    'jumlah'            => $item->jumlah,
                    'satuan'            => $item->satuan,
                    'estimated_price'   => $item->estimated_price,
                    'actual_price'      => $item->actual_price,
                    'purchase_date'     => now(),
                    'catatan'           => $item->catatan,
                ]);

                $message = 'Item ditandai sudah dibeli';
            } else {
                // Tandai belum dibeli
                $item->update([
                    'is_purchased' => false,
                    'purchased_at' => null,
                    'actual_price' => null,
                ]);

                // Hapus expense record
                Expense::where('shopping_list_item_id', $item->id)->delete();

                $message = 'Item ditandai belum dibeli';
            }

            // Update total
            $list->recalculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => $item->load('ingredient'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/shopping-lists/{listId}/items/bulk-toggle-purchased
     * Toggle purchased untuk multiple items sekaligus
     */
    public function bulkTogglePurchased(Request $request, $listId)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)
            ->findOrFail($listId);

        $validator = Validator::make($request->all(), [
            'item_ids'     => 'required|array',
            'item_ids.*'   => 'exists:shopping_list_items,id',
            'is_purchased' => 'required|boolean',
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
            $items = ShoppingListItem::whereIn('id', $request->item_ids)
                ->where('shopping_list_id', $list->id)
                ->get();

            $updatedCount = 0;

            foreach ($items as $item) {
                if ($request->is_purchased) {
                    // Tandai sudah dibeli
                    $item->update([
                        'is_purchased' => true,
                        'purchased_at' => now(),
                        'actual_price' => $item->actual_price ?? $item->estimated_price,
                    ]);

                    // Buat expense record jika belum ada
                    Expense::firstOrCreate(
                        ['shopping_list_item_id' => $item->id],
                        [
                            'user_id'           => $request->user()->id,
                            'shopping_list_id'  => $list->id,
                            'ingredient_id'     => $item->ingredient_id,
                            'nama_item'         => $item->nama_item,
                            'jumlah'            => $item->jumlah,
                            'satuan'            => $item->satuan,
                            'estimated_price'   => $item->estimated_price,
                            'actual_price'      => $item->actual_price,
                            'purchase_date'     => now(),
                            'catatan'           => $item->catatan,
                        ]
                    );
                } else {
                    // Tandai belum dibeli
                    $item->update([
                        'is_purchased' => false,
                        'purchased_at' => null,
                        'actual_price' => null,
                    ]);

                    // Hapus expense record
                    Expense::where('shopping_list_item_id', $item->id)->delete();
                }

                $updatedCount++;
            }

            // Update total
            $list->recalculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengupdate {$updatedCount} item",
                'data'    => [
                    'updated_count' => $updatedCount,
                    'is_purchased'  => $request->is_purchased,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate items: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/shopping-lists/{listId}/items/{itemId}
     * Hapus item dari shopping list
     */
    public function destroy(Request $request, $listId, $itemId)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)
            ->findOrFail($listId);

        $item = ShoppingListItem::where('shopping_list_id', $list->id)
            ->findOrFail($itemId);

        DB::beginTransaction();
        try {
            // Hapus expense terkait jika ada
            Expense::where('shopping_list_item_id', $item->id)->delete();

            // Hapus item
            $item->delete();

            // Update total
            $list->recalculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil dihapus',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/shopping-lists/{listId}/items/bulk-delete
     * Hapus multiple items sekaligus
     */
    public function bulkDelete(Request $request, $listId)
    {
        $list = ShoppingList::where('user_id', $request->user()->id)
            ->findOrFail($listId);

        $validator = Validator::make($request->all(), [
            'item_ids'   => 'required|array',
            'item_ids.*' => 'exists:shopping_list_items,id',
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
            // Ambil semua item yang valid
            $items = ShoppingListItem::whereIn('id', $request->item_ids)
                ->where('shopping_list_id', $list->id)
                ->get();

            $notFound = array_diff($request->item_ids, $items->pluck('id')->toArray());

            if (count($notFound) > 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Beberapa item tidak ditemukan: ' . implode(', ', $notFound),
                    'not_found_ids' => $notFound,
                ], 404);
            }

            // Hapus expenses terkait
            Expense::whereIn('shopping_list_item_id', $request->item_ids)->delete();

            // Hapus items
            $deletedCount = ShoppingListItem::whereIn('id', $request->item_ids)
                ->where('shopping_list_id', $list->id)
                ->delete();

            // Update total
            $list->recalculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$deletedCount} item",
                'data'    => [
                    'deleted_count' => $deletedCount,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus item: ' . $e->getMessage(),
            ], 500);
        }
    }
}