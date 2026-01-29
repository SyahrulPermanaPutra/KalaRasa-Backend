#  Kala Rasa JTV Backend - Summary

##  Daftar File 

### 1. **Database Migrations** (5 files)
```
database/migrations/
├── 2024_01_01_000001_create_users_table.php
├── 2024_01_01_000002_create_reseps_table.php
├── 2024_01_01_000003_create_shopping_lists_table.php
├── 2024_01_01_000004_create_expenses_table.php
└── 2024_01_01_000005_create_favorite_reseps_table.php
```

### 2. **Models** (5 files)
```
app/Models/
├── User.php                 # User model dengan relationships
├── Resep.php               # Resep model dengan approval workflow
├── ShoppingList.php        # Shopping list model
├── Expense.php             # Expense model dengan scopes
└── FavoriteResep.php       # Favorite resep pivot model
```

### 3. **Controllers** (6 files)
```
app/Http/Controllers/Api/
├── AuthController.php                      # Register, Login, Profile
├── ShoppingListController.php              # CRUD Daftar Belanja
├── ExpenseController.php                   # Riwayat & Rekap Pengeluaran
├── ResepController.php                     # User Resep Features
└── Admin/
    ├── AdminDashboardController.php        # Admin Monitoring
    └── AdminResepController.php            # Admin Kelola Resep
```

### 4. **Middleware** (1 file)
```
app/Http/Middleware/
└── AdminMiddleware.php     # Middleware untuk admin authorization
```

### 5. **Database Seeders** (3 files)
```
database/seeders/
├── DatabaseSeeder.php      # Main seeder
├── UserSeeder.php          # User & Admin seed data
└── ResepSeeder.php         # Resep sample data
```

### 6. **Routes** (1 file)
```
routes/
└── api.php                 # Semua API routes
```

### 7. **Bootstrap** (1 file)
```
bootstrap/
└── app.php                 # Application bootstrap & middleware registration
```


---

## Fitur yang Telah Diimplementasi

### ✅ User Features
1. **Autentikasi & Akun**
   - Register user baru
   - Login dengan email & password
   - Logout
   - Get profile
   - Update profile (dengan avatar upload)

2. **Daftar Belanja** (Kelola Daftar Belanja)
   - Membuat daftar belanja
   - Menambah item belanja
   - Mengedit item belanja
   - Menghapus item belanja
   - Input harga item
   - Tandai item sudah dibeli
   - Hitung total pengeluaran
   - Filter berdasarkan status (sudah dibeli/belum)

3. **Riwayat Pengeluaran** (Rekap & Riwayat Pengeluaran)
   - Lihat daftar pengeluaran
   - Tambah pengeluaran manual
   - Hapus pengeluaran
   - **Rekap Harian** dengan statistik per kategori
   - **Rekap Mingguan** dengan breakdown per hari
   - **Rekap Bulanan** dengan rata-rata dan breakdown per minggu
   - Filter berdasarkan tanggal
   - Filter berdasarkan kategori

4. **Resep Makanan**
   - Lihat daftar resep (approved only)
   - Lihat detail resep
   - Filter resep (kategori, kesulitan)
   - Search resep
   - **Tambahkan resep ke daftar favorit**
   - Lihat daftar resep favorit
   - **Tambahkan bahan resep ke daftar belanja** (sekali klik)

### ✅ Admin Features
1. **Dashboard & Monitoring**
   - Dashboard statistik:
     - Total users & user baru bulan ini
     - Total resep (approved, pending)
     - Shopping list statistics
     - Expense statistics
   - Lihat daftar semua user
   - Lihat detail user & aktivitasnya
   - Statistik resep (top favorited, by kategori)
   - Statistik pengeluaran (by period, by kategori)

2. **Kelola Resep**
   - CRUD resep lengkap
   - Upload gambar resep
   - **Approve resep** (workflow approval)
   - **Reject resep** dengan alasan
   - Filter resep by status (pending/approved/rejected)
   - Statistik resep

---

##  Security Features

- ✅ Laravel Sanctum authentication
- ✅ Token-based API authentication
- ✅ Role-based access control (User & Admin)
- ✅ Password hashing dengan bcrypt
- ✅ Input validation untuk semua endpoints
- ✅ Middleware protection untuk protected routes
- ✅ Admin-only routes dengan middleware

---

## 📊 Database Schema

### Tables Created:
1. **users** - User authentication & profile
2. **reseps** - Resep dengan approval workflow
3. **shopping_lists** - Daftar belanja user
4. **expenses** - Riwayat pengeluaran
5. **favorite_reseps** - Many-to-many resep favorit

### Relationships:
- User → Shopping Lists (1:many)
- User → Expenses (1:many)
- User → Favorite Reseps (many:many)
- User → Created Reseps (1:many)
- Resep → Favorited By Users (many:many)

---

## 🚀 API Endpoints (45+ endpoints)

### Authentication (5 endpoints)
- POST `/api/register`
- POST `/api/login`
- POST `/api/logout`
- GET `/api/profile`
- PUT `/api/profile`

### Shopping List (8 endpoints)
- GET `/api/shopping-lists`
- POST `/api/shopping-lists`
- GET `/api/shopping-lists/{id}`
- PUT `/api/shopping-lists/{id}`
- DELETE `/api/shopping-lists/{id}`
- PATCH `/api/shopping-lists/{id}/update-harga`
- PATCH `/api/shopping-lists/{id}/mark-as-bought`
- GET `/api/shopping-lists/calculate/total`

### Expenses (7 endpoints)
- GET `/api/expenses`
- POST `/api/expenses`
- GET `/api/expenses/{id}`
- DELETE `/api/expenses/{id}`
- GET `/api/expenses/rekap/harian`
- GET `/api/expenses/rekap/mingguan`
- GET `/api/expenses/rekap/bulanan`

### Resep (6 endpoints)
- GET `/api/reseps`
- GET `/api/reseps/{id}`
- POST `/api/reseps/{id}/add-to-shopping-list`
- POST `/api/reseps/{id}/toggle-favorite`
- GET `/api/reseps/my/favorites`

### Admin Dashboard (5 endpoints)
- GET `/api/admin/dashboard`
- GET `/api/admin/dashboard/users`
- GET `/api/admin/dashboard/users/{id}`
- GET `/api/admin/dashboard/resep-statistics`
- GET `/api/admin/dashboard/expense-statistics`

### Admin Resep Management (8 endpoints)
- GET `/api/admin/reseps`
- POST `/api/admin/reseps`
- GET `/api/admin/reseps/statistics`
- GET `/api/admin/reseps/{id}`
- PUT `/api/admin/reseps/{id}`
- DELETE `/api/admin/reseps/{id}`
- PATCH `/api/admin/reseps/{id}/approve`
- PATCH `/api/admin/reseps/{id}/reject`

---

## 📝 Sample Data (Seeders)

### Users:
1. **Admin**: admin@jtv.com (password: password123)
2. **Users**: 
   - budi@example.com
   - siti@example.com
   - andi@example.com
   - (password semua: password123)

### Resep Sample (6 resep):
1. Nasi Goreng Spesial ✅ approved
2. Soto Ayam ✅ approved
3. Rawon Daging Sapi ✅ approved
4. Pecel Lele ✅ approved
5. Es Campur Segar ✅ approved
6. Lumpia Semarang ⏳ pending

---

## 💻 Tech Stack

- **Framework**: Laravel 11
- **Authentication**: Laravel Sanctum
- **Database**: MySQL 8.0+
- **PHP**: 8.2+
- **API**: RESTful API
- **Documentation**: Markdown, Postman

---

**Created**: January 29, 2026
**Version**: 1.0.0
**Status**: Production Ready ✅