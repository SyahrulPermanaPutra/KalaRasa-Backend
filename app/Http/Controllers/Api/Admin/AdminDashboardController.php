<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Resep;
use App\Models\ShoppingList;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ShoppingListItem;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // User Statistics
        $totalUsers = User::where('role', ['user','admin'])->count();
        $newUsersThisMonth = User::where('role', 'user')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Resep Statistics
        $totalReseps = Resep::count();
        $approvedReseps = Resep::approved()->count();
        $pendingReseps = Resep::pending()->count();
        $rejectedReseps = Resep::rejected()->count();

        // Shopping List Statistics
        $totalShoppingLists = ShoppingList::count();
        $completedLists     = ShoppingList::completed()->count();
        $pendingLists       = ShoppingList::pending()->count();

        // Item Statistics (alternatif lebih detail)
        $totalShoppingItems = ShoppingListItem::count();
        $totalBoughtItems   = ShoppingListItem::where('is_purchased', true)->count();
        $totalUnboughtItems = ShoppingListItem::where('is_purchased', false)->count();

        // Expense Statistics
        $totalExpenses = Expense::sum('actual_price');
        $expensesThisMonth = Expense::whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->sum('actual_price');

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data retrieved successfully',
            'data'    => [
                'users' => [
                    'total'             => $totalUsers,
                    'new_this_month'    => $newUsersThisMonth,
                ],
                'reseps' => [
                    'total'    => $totalReseps,
                    'approved' => $approvedReseps,
                    'pending'  => $pendingReseps,
                    'rejected' => $rejectedReseps,
                ],
                'shopping_lists' => [
                    'total'     => $totalShoppingLists,
                    'completed' => $completedLists,
                    'pending'   => $pendingLists,
                ],
                'shopping_items' => [
                    'total'    => $totalShoppingItems,
                    'bought'   => $totalBoughtItems,
                    'unbought' => $totalUnboughtItems,
                ],
                'expenses' => [
                    'total'       => (float) $totalExpenses,
                    'this_month'  => (float) $expensesThisMonth,
                ],
            ]
        ]);
    }

    public function users(Request $request)
    {
        $query = User::where('role', 'user');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount(['shoppingLists', 'expenses', 'favoriteReseps'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function userDetail($id)
    {
        $user = User::where('role', 'user')
            ->withCount(['shoppingLists', 'expenses', 'favoriteReseps'])
            ->findOrFail($id);

        // Get latest activities
        $latestExpenses = $user->expenses()
            ->orderBy('purchase_date', 'desc')
            ->limit(5)
            ->get();

        $latestShoppingLists = $user->shoppingLists()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate total spending
        $totalSpending = $user->expenses()->sum('actual_price');
        $thisMonthSpending = $user->expenses()
            ->whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->sum('actual_price');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'statistics' => [
                    'total_spending' => $totalSpending,
                    'this_month_spending' => $thisMonthSpending,
                ],
                'latest_activities' => [
                    'expenses' => $latestExpenses,
                    'shopping_lists' => $latestShoppingLists,
                ]
            ]
        ]);
    }

    public function resepStatistics()
    {
        $topFavoritedReseps = Resep::approved()
            ->withCount('favoritedBy')
            ->orderBy('favorited_by_count', 'desc')
            ->limit(10)
            ->get();

        $recentlyAdded = Resep::approved()
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $byKategori = Resep::approved()
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'top_favorited' => $topFavoritedReseps,
                'recently_added' => $recentlyAdded,
                'by_kategori' => $byKategori,
            ]
        ]);
    }

    public function expenseStatistics(Request $request)
    {
        $userId = $request->user()->id;
        
        // By Store
        $byStore = Expense::where('user_id', $userId)
            ->thisMonth()
            ->selectRaw('COALESCE(store_name, "Lainnya") as store, SUM(actual_price) as total')
            ->groupBy('store_name')
            ->orderBy('total', 'desc')
            ->get();

        // By Date (daily breakdown)
        $byDate = Expense::where('user_id', $userId)
            ->thisMonth()
            ->selectRaw('DATE(purchase_date) as tanggal, SUM(actual_price) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'by_store' => $byStore,
                'by_date'  => $byDate,
                'summary' => [
                    'total_transaksi' => Expense::where('user_id', $userId)->thisMonth()->count(),
                    'total_belanja'   => (float) Expense::where('user_id', $userId)->thisMonth()->sum('actual_price'),
                    'rata_rata'       => (float) Expense::where('user_id', $userId)->thisMonth()->avg('actual_price'),
                ]
            ]
        ]);
    }
}
