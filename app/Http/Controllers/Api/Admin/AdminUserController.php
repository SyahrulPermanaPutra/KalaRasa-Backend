<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Recipe;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * GET /api/admin/users
     * Daftar semua user (hanya role user, admin)
     */
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['user','admin']);

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

        // Filter by registration date
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 10);
        $users = $query->paginate($perPage);

        // Transform data untuk tabel
        $users->getCollection()->transform(function ($user) {
            return [
                'id'            => $user->id,
                'nama_lengkap'  => $user->name,
                'email'         => $user->email,
                'no_telp'       => $user->phone ?? '-',
                'jenis_kelamin' => $this->formatGender($user->gender),
                'tanggal_lahir' => $user->birth_date 
                    ? \Carbon\Carbon::parse($user->birth_date)->format('d M Y')
                    : '-',
                'umur'          => $user->birth_date 
                    ? \Carbon\Carbon::parse($user->birth_date)->age . ' tahun'
                    : '-',
                'tanggal_daftar' => \Carbon\Carbon::parse($user->created_at)->format('d M Y'),
                'status_akun'    => $user->email_verified_at ? 'Terverifikasi' : 'Belum Verifikasi',
                'avatar'         => $user->avatar,
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
                'shoppingLists:id,user_id,nama_list,status,total_actual_price,created_at',
                // 'expenses:id,user_id,actual_price,purchase_date,store_name',
                'createdRecipes:id,nama,status,avg_rating,view_count,created_at,created_by',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail pengguna berhasil diambil',
            'data'    => [
                // Informasi Pribadi
                'user' => [
                    'id'                => $user->id,
                    'nama_lengkap'      => $user->name,
                    'email'             => $user->email,
                    'no_telp'           => $user->phone ?? '-',
                    'jenis_kelamin'     => $this->formatGender($user->gender),
                    'tanggal_lahir'     => $user->birth_date 
                        ? \Carbon\Carbon::parse($user->birth_date)->format('d M Y')
                        : '-',
                    'umur'              => $user->birth_date 
                        ? \Carbon\Carbon::parse($user->birth_date)->age . ' tahun'
                        : '-',
                    'alamat'            => $user->address ?? '-',
                    'avatar'            => $user->avatar,
                    'tanggal_daftar'    => $user->created_at->format('d M Y H:i'),
                    'terakhir_login'    => $user->updated_at->format('d M Y H:i'),
                    'status_verifikasi' => $user->email_verified_at ? 'Terverifikasi' : 'Belum Verifikasi',
                ],

                // Resep yang Dibuat
                'created_recipes' => $user->createdRecipes->map(fn($recipe) => [
                    'id'         => $recipe->id,
                    'nama'       => $recipe->nama,
                    'status'     => $recipe->status,
                    'avg_rating' => (float) $recipe->avg_rating,
                    'view_count' => $recipe->view_count,
                    'created_at' => $recipe->created_at->format('d M Y'),
                ])
            ]
        ]);
    }

    /**
     * Helper: Format gender untuk display
     */
    private function formatGender($gender)
    {
        if (empty($gender)) return '-';

        return match(strtolower($gender)) {
            'male', 'laki-laki', 'l', 'm' => 'Laki-laki',
            'female', 'perempuan', 'p', 'f' => 'Perempuan',
            default => ucfirst($gender),
        };
    }
}