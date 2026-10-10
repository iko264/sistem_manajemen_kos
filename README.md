# SISTEM MANAJEMEN KOS
> Sistem Manajemen Satu Kos: kelola kamar, penghuni, tagihan, dan pembayaran dalam satu aplikasi web.
---

## 📌 Informasi Kelompok
- **Nomor Kelompok:**  Kelompok 4
- **Shift Praktikum:** Contoh: Shift C

---

## 👥 Anggota Kelompok

| No | Nama Lengkap | NIM | Shift Awal | Shift Akhir | Jobdesk / Kontribusi | Link Video Penjelasan |
|---|---|---|---|---|---|---|
| 1 | WINDI SULAIMAN ISMANSA | H1H024005 | Shift A | Shift C | Tagihan, Pembayaran, Rekap Billing, dan Dashboard Admin | [YouTube](https://youtu.be/5tUjMaZ9JOM) |
| 2 | Refan Nur Chandra | H1H024029 | Shift D | Shift C | Penghuni dan Penghunian | [YouTube](https://drive.google.com/file/d/1axSGsearc0gMrE36VZa1-RCmtJ-Gxlg9/view) |
| 3 | Hammed Jastiko Apuranam | H1H024030 | Shift A | Shift C  | Fondasi proyek, Autentikasi , dan CRUD Kamar | [YouTube](https://youtu.be/4XZbl2IWk4c?si=SFHbfeD2o3pkCzgg) |

---

## 📖 Deskripsi Aplikasi
Manajemen Kos adalah aplikasi web untuk mengelola satu kos dengan banyak kamar. Pencatatan kos yang masih manual (buku, chat, atau spreadsheet) mudah salah: status kamar tidak sinkron, tagihan bulanan terlewat, dan bukti transfer sulit dilacak.

Aplikasi ini menyelesaikan masalah tersebut dengan satu alur terpadu: kamar didata, penghuni masuk lewat proses check-in, tagihan bulanan dibuat per periode, penghuni mengunggah bukti pembayaran, lalu pengelola memverifikasinya.

Target pengguna:

- Admin (pengelola kos): mengelola kamar, penghuni, tagihan, verifikasi pembayaran, dan memantau dashboard.
- Tenant (penghuni): melihat daftar kamar, melihat tagihan miliknya, dan membayar dengan mengunggah bukti.

---

## ⚙️ Penjelasan Teknis

### 1. Teknologi (Tech Stack)
- **Backend:** Laravel 13 (PHP 8.5)
- **Frontend:** Bootstrap 5, JavaScript
- **Database:** MySQL 
- **Library / Package:** Laravel Sanctum (autentikasi Bearer token) 

### 2. Fitur Utama & Modul
- Autentikasi & Otorisasi: register (role otomatis tenant), login, logout, dan profil (me) dengan token Sanctum. Dua role (admin, tenant) dijaga middleware role. Tenant hanya dapat mengakses data miliknya dan tidak dapat melihat identitas penghuni lain.
- Kamar: CRUD kamar (hanya admin) dengan filter status, tipe, rentang harga, pencarian nomor, dan pagination. Kamar yang sedang terisi tidak dapat dihapus.
- Penghuni & Penghunian: daftar penghuni, check-in (kamar menjadi occupied dan tagihan pertama otomatis dibuat), dan check-out.
- Tagihan: pembuatan tagihan per periode YYYY-MM untuk semua penghuni aktif, filter status, periode, dan keterlambatan.
- Pembayaran: penghuni mengunggah bukti (jpg/png, maksimal 2 MB), admin menyetujui atau menolak dengan catatan.
- Rekap & Dashboard Admin: status bayar per penghuni, rekap per kamar, dan ringkasan operasional (kamar, penghuni aktif, tagihan, pendapatan bulan ini).

### 3. Skema Data Singkat
- users (1 : N) tenancies
- rooms (1 : N) tenancies
- users (M : N) rooms melalui tenancies (riwayat penghunian)
- tenancies (1 : N) invoices (unik per tenancy_id + period)
- invoices (1 : N) payments
- users (1 : N) payments sebagai verifikator (verified_by)

| Tabel | Kolom utama |
|---|---|
| `users` | name, email, password, role (`admin`/`tenant`), phone, identity_number, address |
| `rooms` | number, type (`standard`/`deluxe`), price, status (`available`/`occupied`/`maintenance`), description |
| `tenancies` | user_id, room_id, start_date, end_date, status (`active`/`ended`) |
| `invoices` | tenancy_id, period, amount, due_date, status (`unpaid`/`pending`/`paid`) |
| `payments` | invoice_id, amount, proof_path, status (`pending`/`approved`/`rejected`), verified_by, verified_at, note |
---

### 4. Aturan Bisnis Utama
1. **Check-in:** kamar harus `available` dan penghuni belum punya penghunian aktif, jika tidak responsnya 422. Proses berjalan dalam satu transaksi database: membuat penghunian, mengubah kamar menjadi `occupied`, dan membuat tagihan pertama (jatuh tempo 7 hari setelah tanggal mulai).
2. **Check-out:** ditolak (422) selama masih ada tagihan yang belum `paid`. Jika lolos, penghunian menjadi `ended` dan kamar kembali `available`.
3. **Tagihan:** admin membuat tagihan per periode untuk semua penghuni aktif, periode yang sudah ada dilewati, jatuh tempo tanggal 10 pada periode tersebut.
4. **Overdue:** tidak disimpan di database, dihitung dari status belum `paid` dan jatuh tempo yang sudah lewat.
5. **Pembayaran:** penghuni hanya dapat membayar tagihannya sendiri yang berstatus `unpaid`. Setelah unggah, pembayaran dan tagihan menjadi `pending`. Persetujuan mengubah keduanya menjadi `approved` dan `paid`; penolakan mengembalikan tagihan ke `unpaid` dan wajib menyertakan catatan.
6. **Status kamar `occupied`** hanya berubah lewat check-in dan check-out, tidak dapat diatur manual dari CRUD kamar.

### 5. Ringkasan Endpoint API
Semua endpoint di bawah prefix `/api`. Response selalu berformat `{success, message, data}` (daftar menambahkan `meta` paginasi, error menambahkan `errors`). Daftar memakai `paginate(10)` dengan `per_page` maksimal 50.

| Modul | Endpoint | Akses |
|---|---|---|
| Auth | `POST /auth/register`, `POST /auth/login` | Publik |
| Auth | `POST /auth/logout`, `GET /auth/me` | Login |
| Kamar | `GET /rooms`, `GET /rooms/{room}` | Login |
| Kamar | `POST /rooms`, `PUT /rooms/{room}`, `DELETE /rooms/{room}` | Admin |
| Penghuni | `GET /tenants`, `GET /rooms/{room}/tenant` | Admin |
| Penghunian | `GET /tenancies`, `GET /tenancies/{tenancy}` | Admin / pemilik data |
| Penghunian | `POST /tenancies` (check-in), `PATCH /tenancies/{tenancy}/checkout` | Admin |
| Tagihan | `GET /invoices`, `GET /invoices/{invoice}` | Admin / pemilik data |
| Tagihan | `POST /invoices/generate` | Admin |
| Pembayaran | `POST /invoices/{invoice}/payments` | Tenant pemilik tagihan |
| Pembayaran | `GET /payments`, `PATCH /payments/{payment}/verify` | Admin |
| Rekap | `GET /billing/tenants`, `GET /billing/rooms`, `GET /admin/dashboard` | Admin |

---

## 🚀 Panduan Instalasi Lokal

```bash
# Clone repository
git clone https://github.com/iko264/Kelompok4-ShiftC-ResponsiPemweb2Laravel.git
cd Kelompok4-ShiftC-ResponsiPemweb2Laravel

# Install dependensi PHP & Node
composer install
npm install

# Konfigurasi Environment
cp .env.example .env
php artisan key:generate

# Konfigurasi database di file .env, lalu migrasi & seed
php artisan migrate --seed

# Jalankan development server
php artisan serve
npm run dev
```
