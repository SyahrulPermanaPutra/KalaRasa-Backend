<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Recipe;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    /**
     * GET /api/admin/user
     * Daftar semua user (hanya role user & admin)
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->with('role:id,name,display_name')
            ->whereIn('role_id', [1, 2]); // 1=admin, 2=user berdasarkan dump SQL

        // Search by name, email, or phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by gender
        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        // Filter by role
        if ($request->has('role')) {
            $roleId = $request->role === 'admin' ? 1 : 2;
            $query->where('role_id', $roleId);
        }

        // Filter by registration date
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // Sorting
        $allowedSorts = ['id', 'name', 'email', 'created_at', 'points'];
        $sortBy = $request->input('sort_by', 'created_at');
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        
        $sortOrder = $request->input('sort_order', 'desc');
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';
        
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->input('per_page', 10), 100); // Max 100 per page
        $users = $query->paginate($perPage);

        // Transform data untuk tabel
        $users->getCollection()->transform(function ($user) {
            return [
                'id'               => $user->id,
                'nama_lengkap'     => $user->name,
                'email'            => $user->email,
                'no_telp'          => $user->phone ?? '-',
                'jenis_kelamin'    => $this->formatGender($user->gender),
                'tanggal_lahir'    => $user->birthdate 
                    ? \Carbon\Carbon::parse($user->birthdate)->format('d M Y')
                    : '-',
                'umur'             => $user->birthdate 
                    ? \Carbon\Carbon::parse($user->birthdate)->age . ' tahun'
                    : '-',
                'points'           => $user->points ?? 0,
                'role'             => $user->role?->display_name ?? $user->role?->name ?? '-',
                'role_id'          => $user->role_id,
                'tanggal_daftar'   => \Carbon\Carbon::parse($user->created_at)->format('d M Y'),
                'status_akun'      => $user->email_verified_at ? 'Terverifikasi' : 'Belum Verifikasi',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil',
            'data'    => $users
        ]);
    }

    /**
     * GET /api/admin/users/{id}
     * Detail user
     */
    public function show($id)
    {
        $user = User::with([
                'role:id,name,display_name',
                'shoppingLists:id,user_id,recipe_id,nama_list,status,total_estimated_price,total_actual_price,created_at',
                'createdRecipes:id,nama,status,avg_rating,total_ratings,view_count,created_at,created_by',
            ])
            ->findOrFail($id);

        // Hitung statistik user
        $stats = $this->calculateUserStats($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Detail pengguna berhasil diambil',
            'data'    => [
                // Informasi Pribadi
                'user' => [
                    'id'                 => $user->id,
                    'nama_lengkap'       => $user->name,
                    'email'              => $user->email,
                    'no_telp'            => $user->phone ?? '-',
                    'jenis_kelamin'      => $this->formatGender($user->gender),
                    'tanggal_lahir'      => $user->birthdate 
                        ? \Carbon\Carbon::parse($user->birthdate)->format('d M Y')
                        : '-',
                    'umur'               => $user->birthdate 
                        ? \Carbon\Carbon::parse($user->birthdate)->age . ' tahun'
                        : '-',
                    'points'             => $user->points ?? 0,
                    'role'               => $user->role?->display_name ?? $user->role?->name ?? '-',
                    'role_id'            => $user->role_id,
                    'tanggal_daftar'     => \Carbon\Carbon::parse($user->created_at)->format('d M Y H:i'),
                    'terakhir_update'    => \Carbon\Carbon::parse($user->updated_at)->format('d M Y H:i'),
                    'status_verifikasi'  => $user->email_verified_at ? 'Terverifikasi' : 'Belum Verifikasi',
                ],

                // Statistik Aktivitas
                'stats' => $stats,

                // Resep yang Dibuat
                'created_recipes' => $user->createdRecipes->map(fn($recipe) => [
                    'id'             => $recipe->id,
                    'nama'           => $recipe->nama,
                    'status'         => $recipe->status,
                    'kategori'       => $recipe->kategori,
                    'avg_rating'     => (float) $recipe->avg_rating,
                    'total_ratings'  => $recipe->total_ratings,
                    'view_count'     => $recipe->view_count,
                    'created_at'     => \Carbon\Carbon::parse($recipe->created_at)->format('d M Y'),
                ]),

                // Daftar Belanja (opsional, bisa dipaginate terpisah)
                'shopping_lists_summary' => [
                    'total_lists'  => $user->shoppingLists()->count(),
                    'completed'    => $user->shoppingLists()->where('status', 'completed')->count(),
                    'pending'      => $user->shoppingLists()->where('status', 'pending')->count(),
                    'total_spent'  => (float) $user->shoppingLists()->sum('total_actual_price'),
                ]
            ]
        ]);
    }

    /**
     * PUT /api/admin/users/{id}/role
     * Update role user
     */
    /*
public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role_id' => 'required|in:1,2' // 1=admin, 2=user
        ]);

        $user = User::findOrFail($id);
        
        // Prevent self-demote if current user is admin
        if ($request->user()->id === $user->id && $request->role_id == 2) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menurunkan role akun Anda sendiri'
            ], 403);
        }

        $oldRole = $user->role?->name;
        $user->update(['role_id' => $request->role_id]);
        $newRole = Role::find($request->role_id)?->name;

        // Log activity (opsional - bisa pakai package seperti spatie/laravel-activitylog)
        \Log::info('Admin updated user role', [
            'admin_id' => $request->user()->id,
            'target_user_id' => $user->id,
            'old_role' => $oldRole,
            'new_role' => $newRole,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Role pengguna berhasil diubah menjadi {$newRole}",
            'data' => [
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                'role_name' => $newRole,
            ]
        ]);
    }
*/

    /**
     * DELETE /api/admin/users/{id}
     * Soft delete user (opsional, sesuai kebijakan)
     */
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting self
        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri'
            ], 403);
        }

        // Prevent deleting other admins if not super admin
        if ($user->role_id === 1 && $request->user()->role_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak diizinkan menghapus akun admin'
            ], 403);
        }

        // Soft delete atau hard delete sesuai kebutuhan
        // Jika model User menggunakan SoftDeletes:
        $user->delete();

        // Jika hard delete, cleanup relasi secara manual atau gunakan CASCADE di migration
        // DB::table('bookmarks')->where('user_id', $id)->delete();
        // DB::table('recipe_ratings')->where('user_id', $id)->delete();
        // dst...

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus'
        ]);
    }

    /**
     * Helper: Format gender untuk display
     */
    private function formatGender($gender)
    {
        if (empty($gender)) return '-';

        return match(strtolower($gender)) {
            'pria', 'laki-laki', 'l', 'male', 'm' => 'Pria',
            'wanita', 'perempuan', 'p', 'female', 'f' => 'Wanita',
            default => ucfirst($gender),
        };
    }

    /**
     * Helper: Hitung statistik aktivitas user
     */
    private function calculateUserStats($userId)
    {
        return [
            'total_recipes_created' => Recipe::where('created_by', $userId)->count(),
            'approved_recipes'      => Recipe::where('created_by', $userId)->where('status', 'approved')->count(),
            'pending_recipes'       => Recipe::where('created_by', $userId)->where('status', 'pending')->count(),
            'rejected_recipes'      => Recipe::where('created_by', $userId)->where('status', 'rejected')->count(),
            'total_bookmarks'       => DB::table('bookmarks')->where('user_id', $userId)->count(),
            'total_ratings_given'   => DB::table('recipe_ratings')->where('user_id', $userId)->count(),
            'total_shopping_lists'  => DB::table('shopping_lists')->where('user_id', $userId)->count(),
        ];
    }
}