<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Resep;
use App\Models\ShoppingList;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // User Statistics
        $totalUsers = User::where('role', 'user')->count();
        $newUsersThisMonth = User::where('role', 'user')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Resep Statistics
        $totalReseps = Resep::count();
        $approvedReseps = Resep::approved()->count();
        $pendingReseps = Resep::pending()->count();

        // Shopping List Statistics
        $totalShoppingItems = ShoppingList::count();
        $totalBoughtItems = ShoppingList::sudahDibeli()->count();

        // Expense Statistics
        $totalExpensesThisMonth = Expense::whereMonth('tanggal_transaksi', now()->month)
            ->whereYear('tanggal_transaksi', now()->year)
            ->sum('total_harga');

        $totalExpensesToday = Expense::today()->sum('total_harga');

        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'new_this_month' => $newUsersThisMonth,
                ],
                'reseps' => [
                    'total' => $totalReseps,
                    'approved' => $approvedReseps,
                    'pending' => $pendingReseps,
                ],
                'shopping_lists' => [
                    'total_items' => $totalShoppingItems,
                    'bought_items' => $totalBoughtItems,
                ],
                'expenses' => [
                    'today' => $totalExpensesToday,
                    'this_month' => $totalExpensesThisMonth,
                ]
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
            ->orderBy('tanggal_transaksi', 'desc')
            ->limit(5)
            ->get();

        $latestShoppingLists = $user->shoppingLists()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate total spending
        $totalSpending = $user->expenses()->sum('total_harga');
        $thisMonthSpending = $user->expenses()
            ->thisMonth()
            ->sum('total_harga');

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
        $period = $request->input('period', 'month'); // day, week, month, year

        $query = Expense::query();

        switch ($period) {
            case 'day':
                $query->today();
                break;
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
            case 'year':
                $query->whereYear('tanggal_transaksi', now()->year);
                break;
        }

        $totalExpenses = $query->sum('total_harga');
        $totalTransactions = $query->count();

        $byKategori = (clone $query)
            ->selectRaw('kategori, COUNT(*) as total_transaksi, SUM(total_harga) as total')
            ->groupBy('kategori')
            ->get();

        $topSpenders = User::where('role', 'user')
            ->withSum(['expenses' => function($q) use ($query) {
                $q->whereIn('id', $query->pluck('id'));
            }], 'total_harga')
            ->orderBy('expenses_sum_total_harga', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'total_expenses' => $totalExpenses,
                'total_transactions' => $totalTransactions,
                'average_per_transaction' => $totalTransactions > 0 ? $totalExpenses / $totalTransactions : 0,
                'by_kategori' => $byKategori,
                'top_spenders' => $topSpenders,
            ]
        ]);
    }
}
