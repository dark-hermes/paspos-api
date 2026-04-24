# PASPOS API

Backend API untuk aplikasi Paspos berbasis Laravel 12.

Project ini berfokus pada autentikasi nomor telepon dengan OTP WhatsApp, manajemen profil pengguna, serta otorisasi token API menggunakan Laravel Sanctum.

## Fitur Utama

1. Registrasi user dengan OTP WhatsApp (otomatis menjadi Member).
2. Verifikasi OTP untuk aktivasi akun.
3. Login Admin wajib menggunakan email.
4. Login Member bisa menggunakan nomor telepon atau email + password.
5. Forgot password dan reset password via OTP WhatsApp untuk member.
6. Forgot password dan reset password via OTP Email untuk admin.
7. Endpoint profil user terautentikasi (`/api/me`).
7. Update profil (`full_name` dan avatar).
8. Update password user.
9. Update nomor telepon dengan flow OTP WhatsApp terpisah.
10. Update email dengan flow OTP Email terpisah.
9. Role user (`main_admin`, `branch_admin`, `cashier`, `member`) dan relasi ke store.
10. Command artisan untuk membuat user admin.
11. CRUD Store berbasis role (khusus `main_admin`).
12. CRUD User berbasis role (`main_admin` vs `branch_admin`).

## Stack

- PHP >= 8.2 (direkomendasikan 8.4)
- Laravel 12
- MySQL
- Laravel Sanctum
- Queue database driver
- Pest untuk testing
- Vite + Tailwind CSS 4 (aset frontend bawaan Laravel)

## Struktur Proyek (Ringkas)

```text
app/
  Http/
    Controllers/Api/AuthController.php
    Requests/
    Resources/AuthUserResource.php
  Jobs/SendWhatsappOtpJob.php
  Models/
    User.php
    Store.php
    PhoneVerificationToken.php
  Services/WhatsappBotClient.php
  Support/PhoneNumberNormalizer.php
database/
  migrations/
  factories/
  seeders/
routes/
  api.php
  web.php
tests/
  Feature/AuthOtpTest.php
  Feature/CreateAdminUserCommandTest.php
bruno/
  paspos/
```

## Setup Lokal

### 1) Prasyarat

- PHP + Composer
- MySQL
- Node.js + npm
- Service WhatsApp Bot (untuk pengiriman OTP nyata)

### 2) Install dependency

```bash
composer install
npm install
```

### 3) Buat file environment

PowerShell (Windows):

```powershell
Copy-Item .env.example .env
```

Lalu generate app key:

```bash
php artisan key:generate
```

### 4) Konfigurasi database di `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=paspos_api
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```

### 5) Migrasi dan seeding

```bash
php artisan migrate --seed
```

### 6) Link storage untuk avatar

```bash
php artisan storage:link
```

### 7) Jalankan aplikasi

Cara cepat (server + queue listener + vite):

```bash
composer run dev
```

Atau manual di terminal terpisah:

```bash
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

Health check endpoint:

```text
GET /up
```

## Environment Penting

Variabel `.env` yang paling penting:

- `APP_URL`
- `DB_*`
- `QUEUE_CONNECTION`
- `WHATSAPP_BOT_URL`
- `BOT_API_KEY`
- `WHATSAPP_BOT_TIMEOUT`
- `OTP_EXPIRES_IN_MINUTES`
- `OTP_RATE_LIMIT_MAX_ATTEMPTS`
- `OTP_RATE_LIMIT_DECAY_SECONDS`

## Integrasi WhatsApp Bot

OTP dikirim oleh job `SendWhatsappOtpJob` yang memanggil service `WhatsappBotClient`.

Request ke bot:

- Method: `POST`
- URL: `${WHATSAPP_BOT_URL}/send-message`
- Header opsional: `x-api-key: ${BOT_API_KEY}`
- Body JSON:

```json
{
  "number": "628xxxx",
  "message": "Kode OTP ..."
}
```

Pastikan queue worker berjalan agar OTP terkirim.

## Alur Auth OTP

1. `POST /api/register` membuat user baru (role selalu `member`) dan mengirim OTP registrasi.
2. `POST /api/verify-otp` memverifikasi OTP registrasi lalu memberikan token Sanctum.
3. `POST /api/login` menyesuaikan rule:
   - Admin (`main_admin`, `branch_admin`, `cashier`) wajib login menggunakan email.
   - Member boleh login dengan email (jika sudah verify) atau nomor telepon (jika sudah verify).
4. `POST /api/forgot-password` dan `POST /api/reset-password` untuk reset password member via OTP WhatsApp.
5. `POST /api/admin/forgot-password` dan `POST /api/admin/reset-password` untuk reset password admin via OTP Email.
6. `POST /api/me/phone/request-otp` dan `POST /api/me/phone/verify-otp` untuk update nomor telepon.
6. `POST /api/me/email/request-otp` dan `POST /api/me/email/verify-otp` untuk update email.

Catatan normalisasi nomor:

- Input seperti `0812-3456-7890` akan dinormalisasi menjadi `6281234567890`.

## Endpoint API

Semua endpoint API menggunakan prefix `/api`.

### Public

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/api/register` | Register user + kirim OTP |
| POST | `/api/resend-otp` | Kirim ulang OTP registrasi |
| POST | `/api/verify-otp` | Verifikasi OTP registrasi |
| POST | `/api/login` | Login |
| POST | `/api/forgot-password` | Kirim OTP reset password (member via WhatsApp) |
| POST | `/api/reset-password` | Reset password dengan OTP (member) |
| POST | `/api/admin/forgot-password` | Kirim OTP reset password (admin via Email) |
| POST | `/api/admin/reset-password` | Reset password dengan OTP (admin) |

### Protected (`auth:sanctum`)

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/api/logout` | Logout token saat ini |
| GET | `/api/me` | Ambil profil user login |
| GET | `/api/user` | Alias endpoint profil |
| PATCH | `/api/me` | Update `full_name` dan/atau avatar |
| PUT | `/api/me/password` | Update password |
| POST | `/api/me/phone/request-otp` | Request OTP nomor baru |
| POST | `/api/me/phone/verify-otp` | Verifikasi OTP nomor baru |
| POST | `/api/me/email/request-otp` | Request OTP email baru |
| POST | `/api/me/email/verify-otp` | Verifikasi OTP email baru |

### Protected Admin Resource (`auth:sanctum`)

#### Store Resource

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/api/stores` | List store |
| POST | `/api/stores` | Create store |
| GET | `/api/stores/{store}` | Detail store |
| PATCH | `/api/stores/{store}` | Update store |
| DELETE | `/api/stores/{store}` | Hapus store |

#### User Resource

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/api/users` | List user |
| POST | `/api/users` | Create user |
| GET | `/api/users/{user}` | Detail user |
| PATCH | `/api/users/{user}` | Update user |
| DELETE | `/api/users/{user}` | Hapus user |

### Protected Member Resource (`auth:sanctum`)

#### Address Resource

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/api/member/addresses` | List address milik member |
| POST | `/api/member/addresses` | Create address baru |
| GET | `/api/member/addresses/{address}` | Detail address |
| PATCH/PUT | `/api/member/addresses/{address}` | Update address |
| DELETE | `/api/member/addresses/{address}` | Hapus address |

## Aturan Otorisasi Role

### Store CRUD

- Hanya `main_admin` yang boleh akses Store resource (`index`, `store`, `show`, `update`, `destroy`).
- Role lain (`branch_admin`, `cashier`, `member`) akan mendapat response `403`.

### User CRUD

- `main_admin`: boleh mengelola semua user dan semua role.
- `branch_admin`: hanya boleh mengelola user dengan role `cashier` dan `member`.
- `branch_admin`: hanya boleh mengelola user pada store yang sama.
- `cashier` dan `member`: tidak memiliki akses ke resource user.

### Address CRUD

- Hanya `member` yang bisa mengakses resource ini (`/api/member/addresses`).
- Setiap member hanya dapat melihat, mengubah, dan menghapus alamat pengiriman miliknya sendiri.

Aturan tambahan saat create/update user oleh `main_admin`:

- Jika role `branch_admin`, `store_id` wajib diisi dan harus store bertipe `branch`.
- Jika role `main_admin` dan `store_id` diisi, store harus bertipe `main`.

## Validasi Payload Ringkas

- Register: `full_name`, `phone`, `password`, `password_confirmation`
- Login: `phone` ATAU `email`, dan `password`
- Verify OTP: `phone`, `otp` (6 digit)
- Reset Password: `phone`, `otp`, `password`, `password_confirmation`
- Reset Password Admin: `email`, `otp`, `password`, `password_confirmation`
- Update Profile: minimal salah satu dari `full_name` atau `avatar`
- Update Password: `current_password`, `new_password`, `new_password_confirmation`
- Update Phone: `new_phone`, dan `otp` pada step verifikasi
- Update Email: `new_email`, dan `otp` pada step verifikasi
- Store Create: `name`, `type` (`main` / `branch`), `address` (opsional)
- Store Update: `name`/`type`/`address` (minimal salah satu)
- User Create: `full_name`, `password`, `password_confirmation`, `role`, `email` (opsional), `phone` (opsional), `store_id` (opsional, tergantung aturan role)
- User Update: field user bersifat parsial (`full_name`, `email`, `phone`, `password`, `role`, `store_id`)
- Address Create/Update: `name`, `address`, `receiver_name`, `receiver_phone`, `is_default` (boolean), `notes` (opsional)

## Data Model Ringkas

### users

Kolom penting:

- `name`
- `email` (nullable, unique)
- `phone` (nullable, unique)
- `phone_verified_at`
- `avatar_path`
- `password`
- `role` (`main_admin`, `branch_admin`, `cashier`, `member`)
- `store_id` (nullable, relasi ke `stores`)

### stores

Kolom penting:

- `name` (unique)
- `address` (nullable)
- `type` (`main` atau `branch`)

### addresses

Kolom penting:

- `user_id` (relasi ke `users`)
- `name` (contoh: "Rumah", "Kantor")
- `address` (alamat lengkap)
- `receiver_name`
- `receiver_phone`
- `is_default` (boolean)

### phone_verification_tokens

Kolom penting:

- `phone`
- `purpose` (`registration`, `password_reset`, `phone_update`)
- `token` (hashed)
- `expires_at`

### email_verification_tokens

Kolom penting:

- `email`
- `purpose` (`email_update`)
- `token` (hashed)
- `expires_at`

## Seeder Data Awal

`DatabaseSeeder` memanggil:

- `StoreSeeder`
- `UserSeeder`

`UserSeeder` membuat contoh role:

- main admin
- branch admin
- cashier
- member

Catatan:

- Password default user hasil factory adalah `password`.
- Nomor telepon pada seed user bisa berubah tiap seed (generated by factory).

## Command Artisan Kustom

### Membuat User Admin

```bash
php artisan user:create-admin {role} [--name=] [--email=] [--phone=] [--password=] [--store_id=]
```

`role` hanya boleh:

- `main_admin`
- `branch_admin`

Aturan store:

- `branch_admin` wajib menyertakan `--store_id`.
- Jika `store_id` diisi pada `main_admin`, store harus bertipe `main`.
- Jika `store_id` diisi pada `branch_admin`, store harus bertipe `branch`.

Contoh:

```bash
php artisan user:create-admin main_admin --name="Main Admin" --email="main.admin@example.com" --phone="081234567890" --password="password123" --store_id=1
php artisan user:create-admin branch_admin --name="Branch Admin" --email="branch.admin@example.com" --phone="081355566677" --password="password123" --store_id=2
```

## Testing

Jalankan seluruh test:

```bash
php artisan test --compact
```

Jalankan test spesifik:

```bash
php artisan test --compact tests/Feature/AuthOtpTest.php
php artisan test --compact tests/Feature/CreateAdminUserCommandTest.php
php artisan test --compact tests/Feature/StoreResourceApiTest.php
php artisan test --compact tests/Feature/UserResourceApiTest.php
```

## Koleksi Bruno

Collection request API tersedia di:

```text
bruno/paspos
```

Struktur collection:

```text
bruno/paspos/
  auth/          -> Register, Resend OTP, Verify OTP, Login, Forgot/Reset Password
  profile/       -> Get Me, Update Profile, Update Avatar, Update Password
  phone-update/  -> Request/Verify Phone Update OTP
  email-update/  -> Request/Verify Email Update OTP
  admin-stores/  -> List/Create/Get/Update/Delete Store
  admin-users/   -> List/Create/Get/Update/Delete User
  member-addresses/ -> List/Create/Get/Update/Delete Address
  environments/  -> File environment Bruno
```

Sebelum dipakai, sesuaikan environment:

```text
bruno/paspos/environments/local.bru
```

Variabel yang biasanya diubah:

- `baseUrl`
- `phone`
- `newPhone`
- `password`
- `authToken`
- `storeId`
- `targetUserId`
- `userRole`

## Troubleshooting

1. OTP tidak terkirim.
Pastikan WhatsApp Bot aktif, konfigurasi URL benar, dan queue worker berjalan.

2. Avatar URL tidak muncul.
Pastikan sudah menjalankan `php artisan storage:link`.

3. Kena rate limit OTP (`429`).
Sesuaikan `OTP_RATE_LIMIT_MAX_ATTEMPTS` dan `OTP_RATE_LIMIT_DECAY_SECONDS`.

## Lisensi

Project ini menggunakan lisensi MIT.
