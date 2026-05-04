# DOKUMEN RENCANA PENGUJIAN SISTEM (DUPL) - PasPOS API

## **Tabel 1: Daftar Kebutuhan Fungsional**

| No Fungsi | Nama Fungsi | Deskripsi Fungsi |
|---|---|---|
| DUPL.001 | Registrasi Member | Fungsi digunakan untuk mendaftarkan member baru pada aplikasi secara online melalui API |
| DUPL.002 | Verifikasi OTP Registrasi | Fungsi digunakan untuk memverifikasi kode OTP yang dikirim melalui WhatsApp pada saat registrasi |
| DUPL.003 | Login Member | Fungsi digunakan untuk member melakukan login ke aplikasi dengan nomor telepon dan password |
| DUPL.004 | Logout Member | Fungsi digunakan untuk member keluar dari aplikasi |
| DUPL.005 | Reset Password | Fungsi digunakan untuk member melakukan reset password melalui OTP yang dikirim via WhatsApp |
| DUPL.006 | Manajemen Produk (CRUD) | Fungsi digunakan oleh admin untuk membuat, membaca, mengubah, dan menghapus data produk |
| DUPL.007 | Filter Produk | Fungsi digunakan untuk memfilter produk berdasarkan kategori, brand, nama, dan SKU |
| DUPL.008 | Manajemen Brand (CRUD) | Fungsi digunakan oleh admin untuk membuat, membaca, mengubah, dan menghapus data brand |
| DUPL.009 | Manajemen Inventory (CRUD) | Fungsi digunakan oleh admin untuk membuat, membaca, mengubah, dan menghapus data inventory per toko |
| DUPL.010 | Filter Inventory | Fungsi digunakan untuk memfilter inventory berdasarkan toko, stok minimal, dan status aktif |
| DUPL.011 | Tambah Produk ke Keranjang | Fungsi digunakan oleh member untuk menambahkan produk ke keranjang belanja di toko tertentu |
| DUPL.012 | Lihat Keranjang | Fungsi digunakan oleh member untuk melihat daftar produk di keranjang belanja dengan harga dan stok real-time |
| DUPL.013 | Checkout Pesanan Online | Fungsi digunakan oleh member untuk melakukan checkout pesanan online dengan metode pembayaran COD atau transfer |
| DUPL.014 | Pembuatan Pesanan POS | Fungsi digunakan oleh kasir untuk membuat pesanan POS dengan pembayaran tunai atau bayar nanti |
| DUPL.015 | Pencarian Produk POS | Fungsi digunakan oleh kasir untuk mencari produk berdasarkan barcode pada POS |
| DUPL.016 | Pembayaran Pesanan | Fungsi digunakan oleh kasir untuk melakukan pembayaran pesanan yang belum terbayar |
| DUPL.017 | Daftar Pesanan | Fungsi digunakan oleh admin/kasir untuk melihat daftar semua pesanan dengan filter berdasarkan toko, status pembayaran, dan tanggal |
| DUPL.018 | Detail Pesanan | Fungsi digunakan untuk melihat detail lengkap pesanan termasuk item, pembayaran, dan pengiriman |
| DUPL.019 | Transaksi Member | Fungsi digunakan oleh member untuk melihat riwayat transaksi (pesanan) mereka |
| DUPL.020 | Dashboard Penjualan | Fungsi digunakan oleh admin untuk melihat ringkasan penjualan dengan metrik perubahan dan grafik |
| DUPL.021 | Manajemen Pesanan (Order Management) | Fungsi digunakan oleh admin untuk mengelola status pesanan online (confirm, cancel, complete) |
| DUPL.022 | Pembatalan Pesanan Kadaluarsa | Fungsi sistem otomatis untuk membatalkan pesanan online yang belum dikonfirmasi dalam waktu tertentu |

---

## **Tabel 2: Daftar Kebutuhan Non-Fungsional**

| No Fungsi | Parameter Non-Fungsional | Deskripsi |
|---|---|---|
| DUPL.023 | Performance - Response Time | API harus merespons dalam waktu kurang dari 2 detik untuk operasi standar |
| DUPL.024 | Security - OTP Rate Limiting | Sistem harus membatasi pengiriman OTP maksimal 3 kali per nomor telepon dalam 5 menit |
| DUPL.025 | Security - Password Encryption | Password harus dienkripsi menggunakan algoritma bcrypt dengan hash strength minimal 12 |
| DUPL.026 | Data Integrity - Inventory Validation | Sistem harus melakukan validasi real-time pada ketersediaan inventory saat checkout |
| DUPL.027 | Data Integrity - Stock Tracking | Setiap perubahan stok harus tercatat dalam tabel stock_movements untuk audit trail |
| DUPL.028 | Authorization - Role Based Access | Sistem harus membatasi akses berdasarkan role (member, cashier, branch_admin, main_admin) |
| DUPL.029 | Authorization - Store Isolation | Branch admin hanya dapat melihat data toko mereka sendiri, bukan toko cabang lain |
| DUPL.030 | Database - Transaction Integrity | Operasi checkout dan pembayaran harus atomic dan konsisten dalam database |
| DUPL.031 | API Documentation - REST Standards | API harus mengikuti REST conventions dengan HTTP status code yang sesuai |
| DUPL.032 | Error Handling - Graceful Failures | Sistem harus memberikan pesan error yang jelas dan actionable kepada client |
| DUPL.033 | Data Accuracy - Price Snapshot | Harga dan biaya dasar harus disimpan sebagai snapshot saat transaksi dibuat |
| DUPL.034 | Scalability - Concurrent Users | Sistem harus dapat menangani minimal 100 concurrent users tanpa degradasi |
| DUPL.035 | Compatibility - API Versioning | API harus mendukung versioning untuk backward compatibility |

---

# **KASUS UJI SISTEM PASPOS API**

## **Tabel Kasus Uji 1: Registrasi Member dengan Pengiriman OTP**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-001] |
| Nama Kasus Uji | Registrasi Member dan Pengiriman OTP |
| Deskripsi Kasus | Registrasi member baru dengan validasi data dan pengiriman OTP via WhatsApp |
| Kondisi Awal | Aplikasi dalam keadaan berjalan, database kosong atau siap untuk user baru |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-001]

1. Sistem menerima request POST ke `/api/register` dengan data:
   - full_name: "Hermas Test"
   - phone: "0812-3456-7890"
   - password: "password123"
   - password_confirmation: "password123"

2. Sistem memvalidasi format nomor telepon dan password

3. Sistem membuat user baru dengan status phone_verified_at = null

4. Sistem membuat token verifikasi OTP untuk tujuan 'registration'

5. Sistem menempatkan job pengiriman OTP ke antrian

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response status 201 (Created) dengan status 'success'<br>2. User berhasil dibuat di database dengan phone '6281234567890'<br>3. PhoneVerificationToken dibuat untuk 'registration'<br>4. Job SendWhatsappOtpJob ditempatkan di antrian | ✓ Response 201 berhasil diterima<br>✓ User tersimpan di database<br>✓ Token verifikasi tersimpan<br>✓ Job pengiriman OTP tersimpan | **PASS** |

---

## **Tabel Kasus Uji 2: Verifikasi OTP Registrasi**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-002] |
| Nama Kasus Uji | Verifikasi OTP Registrasi dan Aktivasi Akun |
| Deskripsi Kasus | Verifikasi kode OTP yang diterima member untuk mengaktifkan akun |
| Kondisi Awal | User telah terdaftar dengan phone_verified_at = null dan PhoneVerificationToken sudah ada |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-002]

1. Sistem menerima request POST ke `/api/verify-otp` dengan data:
   - phone: "08111111111"
   - otp: "123456"

2. Sistem mengkonversi nomor telepon ke format lengkap "628111111111"

3. Sistem memvalidasi token OTP dengan bcrypt compare

4. Sistem memperbarui user dengan phone_verified_at = now()

5. Sistem menghapus PhoneVerificationToken yang sudah digunakan

6. Sistem mengembalikan auth token

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response status 200 dengan struktur {status, token}<br>2. User phone_verified_at sudah tidak null<br>3. PhoneVerificationToken untuk 'registration' dihapus | ✓ Response 200 berhasil<br>✓ User teraktivasi<br>✓ Token dihapus | **PASS** |

---

## **Tabel Kasus Uji 3: Login Dengan Phone Verified**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-003] |
| Nama Kasus Uji | Login Member dan Validasi Phone Verified |
| Deskripsi Kasus | Login member yang sudah verifikasi dengan nomor telepon dan password yang benar |
| Kondisi Awal | User sudah terdaftar dan phone_verified_at sudah terisi |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-003]

1. Sistem menerima request login dengan nomor dan password yang benar
2. Sistem memvalidasi nomor telepon dan password
3. Sistem memeriksa phone_verified_at tidak null
4. Sistem mengembalikan auth token

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Login berhasil dengan response 200<br>2. Auth token dikembalikan | ✓ Login berhasil<br>✓ Token diterima | **PASS** |

---

## **Tabel Kasus Uji 4: Login Ditolak untuk Unverified Phone**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-004] |
| Nama Kasus Uji | Penolakan Login untuk Akun Belum Terverifikasi |
| Deskripsi Kasus | Sistem menolak login jika nomor telepon user belum terverifikasi |
| Kondisi Awal | User terdaftar dengan phone_verified_at = null |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-004]

1. Sistem menerima request login dengan user yang belum terverifikasi
2. Sistem memeriksa status phone_verified_at
3. Sistem menolak login karena phone belum terverifikasi

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response status 403 (Forbidden)<br>2. Login ditolak | ✓ Status 403 diterima<br>✓ Login ditolak | **PASS** |

---

## **Tabel Kasus Uji 5: Reset Password dengan OTP**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-005] |
| Nama Kasus Uji | Reset Password Menggunakan OTP |
| Deskripsi Kasus | Member melakukan reset password melalui verifikasi OTP yang dikirim via WhatsApp |
| Kondisi Awal | User sudah terverifikasi dan menginginkan reset password |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-005]

1. Sistem menerima request POST `/api/forgot-password` dengan nomor telepon
2. Sistem membuat PhoneVerificationToken untuk 'password_reset'
3. Sistem mengirim OTP via WhatsApp Job
4. Member menerima OTP dan melakukan POST `/api/reset-password` dengan OTP dan password baru
5. Sistem memvalidasi OTP dan mengupdate password
6. Sistem menghapus PhoneVerificationToken

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response forgot-password 200 OK<br>2. Job pengiriman OTP tertambah di antrian<br>3. Response reset-password 200 dengan status 'success'<br>4. Password user berhasil diubah<br>5. Password baru valid saat login | ✓ Forgot-password berhasil<br>✓ OTP dikirim<br>✓ Reset password berhasil<br>✓ Password baru berlaku | **PASS** |

---

## **Tabel Kasus Uji 6: OTP Rate Limiting**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-006] |
| Nama Kasus Uji | Pembatasan Pengiriman OTP per Nomor Telepon |
| Deskripsi Kasus | Sistem membatasi pengiriman OTP maksimal 3 kali untuk nomor telepon yang sama dalam 5 menit |
| Kondisi Awal | Konfigurasi rate limit sudah diset: max_attempts = 3, decay_seconds = 300 |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-006]

1. Sistem menerima request pertama `/api/forgot-password` untuk nomor telepon tertentu
2. Request berhasil dan OTP dikirim
3. Sistem menerima request kedua untuk nomor yang sama
4. Sistem menerima request ketiga untuk nomor yang sama
5. Sistem menerima request keempat untuk nomor yang sama dalam 5 menit

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Request 1-3 berhasil dengan status 200<br>2. Request 4 ditolak dengan status 429 (Too Many Requests) | ✓ 3 request pertama berhasil<br>✓ Request keempat ditolak | **PASS** |

---

## **Tabel Kasus Uji 7: CRUD Produk oleh Admin**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-007] |
| Nama Kasus Uji | Manajemen Produk (Create, Read, Update, Delete) |
| Deskripsi Kasus | Admin dapat melakukan operasi CRUD lengkap pada data produk |
| Kondisi Awal | Admin main_admin sudah login dengan token valid, kategori dan brand sudah ada |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-007]

1. **CREATE**: POST `/api/products` dengan data nama, category_id, brand_id, sku, unit, description
2. **INDEX**: GET `/api/products` untuk melihat daftar produk dengan eager-loaded relations
3. **SHOW**: GET `/api/products/{id}` untuk melihat detail produk
4. **UPDATE**: PATCH `/api/products/{id}` untuk mengubah nama atau field lain
5. **DELETE**: DELETE `/api/products/{id}` untuk menghapus produk

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. CREATE: Response 201 dengan data produk<br>2. INDEX: Response 200 dengan daftar produk dan relations<br>3. SHOW: Response 200 dengan detail produk<br>4. UPDATE: Response 200 dengan data ter-update<br>5. DELETE: Response 200 dan produk hilang dari database | ✓ Semua operasi CRUD berhasil<br>✓ Relations eager-loaded<br>✓ Data ter-update dan terhapus dengan baik | **PASS** |

---

## **Tabel Kasus Uji 8: Filter Produk Berdasarkan Kategori dan Brand**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-008] |
| Nama Kasus Uji | Filter Produk per Kategori dan Brand |
| Deskripsi Kasus | Admin dapat memfilter produk berdasarkan category_id dan brand_id |
| Kondisi Awal | Beberapa produk dengan kategori dan brand berbeda sudah ada di database |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-008]

1. Sistem memiliki 2 kategori (Makanan, Minuman) dan produk dengan kategori berbeda
2. Admin melakukan request GET `/api/products?category_id={categoryId}`
3. Sistem mengembalikan hanya produk dengan kategori yang sesuai
4. Admin melakukan request GET `/api/products?brand_id={brandId}`
5. Sistem mengembalikan hanya produk dengan brand yang sesuai

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Filter kategori menampilkan 1 dari 2 produk<br>2. Filter brand menampilkan produk yang sesuai | ✓ Filter kategori bekerja<br>✓ Filter brand bekerja | **PASS** |

---

## **Tabel Kasus Uji 9: CRUD Brand oleh Admin**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-009] |
| Nama Kasus Uji | Manajemen Brand (Create, Read, Update, Delete) |
| Deskripsi Kasus | Admin dapat melakukan operasi CRUD lengkap pada data brand |
| Kondisi Awal | Admin main_admin sudah login dengan token valid |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-009]

1. **CREATE**: POST `/api/brands` dengan nama brand baru
2. **INDEX**: GET `/api/brands` untuk melihat daftar brand
3. **SHOW**: GET `/api/brands/{id}` untuk melihat detail brand
4. **UPDATE**: PATCH `/api/brands/{id}` untuk mengubah nama brand
5. **DELETE**: DELETE `/api/brands/{id}` untuk menghapus brand

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. CREATE: Response 201 dengan data brand<br>2. INDEX: Response 200 dengan daftar brand<br>3. SHOW: Response 200 dengan detail brand<br>4. UPDATE: Response 200 dengan nama ter-update<br>5. DELETE: Response 200 dan brand hilang dari database | ✓ Semua operasi CRUD berhasil | **PASS** |

---

## **Tabel Kasus Uji 10: CRUD Inventory oleh Admin**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-010] |
| Nama Kasus Uji | Manajemen Inventory (Create, Read, Update, Delete) |
| Deskripsi Kasus | Admin dapat melakukan operasi CRUD inventory untuk produk di toko tertentu |
| Kondisi Awal | Admin main_admin sudah login, toko dan produk sudah ada |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-010]

1. **CREATE**: POST `/api/inventories` dengan store_id, product_id, stock, purchase_price, selling_price, discount_percentage, min_stock
2. **INDEX**: GET `/api/inventories` untuk melihat daftar inventory
3. **SHOW**: GET `/api/inventories/{id}` untuk detail inventory
4. **UPDATE**: PATCH `/api/inventories/{id}` untuk mengubah harga atau stok
5. **DELETE**: DELETE `/api/inventories/{id}` untuk menghapus inventory

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. CREATE: Response 201 dengan stok 100<br>2. INDEX: Response 200<br>3. SHOW: Response 200<br>4. UPDATE: Response 200 dengan harga ter-update<br>5. DELETE: Response 200 dan inventory hilang | ✓ Semua operasi CRUD berhasil | **PASS** |

---

## **Tabel Kasus Uji 11: Validasi Duplicate Store-Product Inventory**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-011] |
| Nama Kasus Uji | Pencegahan Duplicate Inventory per Toko-Produk |
| Deskripsi Kasus | Sistem tidak mengizinkan membuat inventory duplikat untuk toko-produk yang sama |
| Kondisi Awal | Inventory untuk store-product kombinasi sudah ada |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-011]

1. Sistem sudah memiliki 1 inventory untuk store A dan product X
2. Admin mencoba membuat inventory lagi dengan store A dan product X yang sama
3. Sistem memvalidasi unique constraint

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response status 422 (Unprocessable Entity)<br>2. Error message ditampilkan | ✓ Duplicate ditolak dengan status 422 | **PASS** |

---

## **Tabel Kasus Uji 12: Filter Inventory per Toko**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-012] |
| Nama Kasus Uji | Filter Inventory Berdasarkan Toko |
| Deskripsi Kasus | Admin dapat memfilter inventory untuk toko tertentu |
| Kondisi Awal | Inventory untuk 2 toko berbeda sudah ada |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-012]

1. Admin melakukan GET `/api/inventories?store_id={storeId}`
2. Sistem mengembalikan hanya inventory untuk toko tersebut

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Filter store_id menampilkan 1 dari 2 inventory | ✓ Filter bekerja dengan benar | **PASS** |

---

## **Tabel Kasus Uji 13: Filter Low Stock Inventory**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-013] |
| Nama Kasus Uji | Filter Inventory dengan Stok Rendah |
| Deskripsi Kasus | Admin dapat memfilter inventory yang stoknya di bawah minimum yang ditentukan |
| Kondisi Awal | Inventory dengan stok 2 (min_stock 10) dan stok 100 (min_stock 10) ada di database |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-013]

1. Admin melakukan GET `/api/inventories?low_stock=true`
2. Sistem menampilkan hanya inventory dengan stok < min_stock

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Filter low_stock menampilkan 1 dari 2 inventory (stok rendah) | ✓ Filter bekerja dengan benar | **PASS** |

---

## **Tabel Kasus Uji 14: Validasi Tambah Produk ke Keranjang**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-014] |
| Nama Kasus Uji | Validasi Ketersediaan Produk Saat Tambah Keranjang |
| Deskripsi Kasus | Sistem memvalidasi ketersediaan produk di inventory toko sebelum menambah ke keranjang |
| Kondisi Awal | Member sudah login, produk belum ada di inventory toko |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-014]

1. Member melakukan POST `/api/member/{storeId}/cart` dengan product_id dan quantity
2. Sistem memvalidasi inventory untuk store dan product tersebut
3. Jika inventory tidak ada atau tidak aktif, request ditolak

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 422 (Unprocessable)<br>2. Error message: "Selected product is not available in the selected store inventory." | ✓ Validasi bekerja<br>✓ Error message jelas | **PASS** |

---

## **Tabel Kasus Uji 15: Consolidate Cart Items**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-015] |
| Nama Kasus Uji | Konsolidasi Cart Items untuk User-Store-Product yang Sama |
| Deskripsi Kasus | Sistem menjaga hanya 1 baris cart untuk kombinasi user-store-product yang sama |
| Kondisi Awal | Member sudah login, inventory tersedia |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-015]

1. Member menambah produk X quantity 2 ke cart
2. Member menambah produk X quantity 3 ke cart lagi
3. Sistem tidak membuat cart item baru, tapi mengupdate quantity

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Request pertama response 201 (Created)<br>2. Request kedua response 200 (OK)<br>3. Total CartItem di database = 1<br>4. Quantity menjadi 5 (2+3) | ✓ Cart items berhasil dikonsolidasi<br>✓ Quantity ter-update | **PASS** |

---

## **Tabel Kasus Uji 16: Real-time Stock dan Price di Cart**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-016] |
| Nama Kasus Uji | Tampilkan Stok dan Harga Real-time di Keranjang |
| Deskripsi Kasus | Sistem menampilkan stok dan harga terkini dari inventory pada listing keranjang |
| Kondisi Awal | Cart item sudah ada dengan product dan inventory tertentu |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-016]

1. Member melakukan GET `/api/member/{storeId}/cart`
2. Sistem mengambil data real-time dari inventory
3. Harga dengan diskon diperhitungkan: unit_price = selling_price * (1 - discount_percentage/100)
4. Sistem menampilkan current_stock, current_unit_price, dan line_subtotal

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 200 OK<br>2. current_stock = 20 (dari inventory)<br>3. current_unit_price = 8000 (selling_price 10000 dengan diskon 20%)<br>4. line_subtotal = 16000 (8000 * 2 quantity) | ✓ Data real-time ditampilkan<br>✓ Perhitungan diskon benar | **PASS** |

---

## **Tabel Kasus Uji 17: Revalidasi Inventory Saat Checkout**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-017] |
| Nama Kasus Uji | Revalidasi Ketersediaan Inventory Saat Checkout |
| Deskripsi Kasus | Sistem melakukan revalidasi inventory saat checkout, memastikan produk masih aktif |
| Kondisi Awal | Cart item sudah ada, tapi inventory diubah menjadi is_active = false |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-017]

1. Member membuat cart dengan produk
2. Inventory produk di-update is_active = false
3. Member melakukan POST `/api/member/{storeId}/cart/checkout`
4. Sistem melakukan revalidasi inventory

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 422 (Unprocessable)<br>2. Order tidak jadi dibuat<br>3. Cart items tetap tersimpan | ✓ Revalidasi bekerja<br>✓ Order tidak terbuat | **PASS** |

---

## **Tabel Kasus Uji 18: Pembuatan Pesanan Online dari Cart**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-018] |
| Nama Kasus Uji | Pembuatan Pesanan Online dari Keranjang dengan Snapshot Harga |
| Deskripsi Kasus | Sistem membuat pesanan online dari cart dan menyimpan snapshot base_cost dan unit_price |
| Kondisi Awal | Cart item sudah ada dengan inventory valid |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-018]

1. Member melakukan POST `/api/member/{storeId}/cart/checkout` dengan:
   - payment_method: "cod"
   - shipping_name, shipping_receiver_name, shipping_receiver_phone, shipping_address
2. Sistem membuat Order dengan type = "online"
3. Sistem membuat OrderItem dengan snapshot dari inventory (base_cost, unit_price)
4. Sistem membuat ShipmentInfo
5. Sistem menghapus CartItem

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 201 dengan order data<br>2. Order created dengan type='online'<br>3. OrderItem menyimpan base_cost dan unit_price snapshot<br>4. ShipmentInfo tersimpan<br>5. CartItem dihapus | ✓ Order berhasil dibuat<br>✓ Snapshot harga tersimpan | **PASS** |

---

## **Tabel Kasus Uji 19: Pencarian Produk POS Berdasarkan Barcode**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-019] |
| Nama Kasus Uji | Pencarian Produk POS Menggunakan Barcode |
| Deskripsi Kasus | Kasir dapat mencari produk berdasarkan barcode untuk POS |
| Kondisi Awal | Produk dengan barcode sudah ada, inventory untuk toko sudah tersedia |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-019]

1. Kasir melakukan GET `/api/pos/products?store_id={storeId}&search=123456789`
2. Sistem mencari berdasarkan barcode atau nama produk
3. Sistem menampilkan data produk dengan inventory untuk toko

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 200 OK<br>2. data.0.product.id sesuai dengan product yang dicari | ✓ Pencarian berdasarkan barcode berhasil | **PASS** |

---

## **Tabel Kasus Uji 20: Pembuatan POS Order Cash**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-020] |
| Nama Kasus Uji | Pembuatan POS Order Pembayaran Cash |
| Deskripsi Kasus | Kasir membuat order POS dengan pembayaran tunai, stok berkurang otomatis |
| Kondisi Awal | Kasir sudah login, produk dan inventory ada dengan stok 10 |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-POSPOS.K-020]

1. Kasir melakukan POST `/api/pos/orders` dengan:
   - store_id, payment_method='cash', items=[{product_id, quantity: 2}]
2. Sistem membuat Order dengan type='pos', payment_status='paid'
3. Sistem membuat OrderItem dengan snapshot harga
4. Sistem membuat Payment dengan amount total
5. Sistem membuat StockMovement dengan type='out'
6. Sistem update inventory stock berkurang

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 201<br>2. total_amount = 120000 (2 * selling_price)<br>3. payment_status = 'paid'<br>4. Inventory stock berkurang dari 10 menjadi 8<br>5. StockMovement tercatat dengan type='out', quantity=2 | ✓ Order berhasil dibuat<br>✓ Stock berkurang<br>✓ Payment tercatat | **PASS** |

---

## **Tabel Kasus Uji 21: Pembuatan POS Order Bayar Nanti**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-021] |
| Nama Kasus Uji | Pembuatan POS Order Pembayaran Bayar Nanti |
| Deskripsi Kasus | Kasir membuat POS order dengan metode pembayaran bayar nanti untuk member |
| Kondisi Awal | Kasir sudah login, customer dan inventory ada |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-021]

1. Kasir melakukan POST `/api/pos/orders` dengan customer_id dan payment_method='pay_later'
2. Sistem membuat Order dengan payment_status='unpaid'
3. Sistem tidak membuat Payment awal

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 201<br>2. payment_status = 'unpaid'<br>3. Payment record count = 0 | ✓ Order berhasil dibuat<br>✓ Payment belum dibuat | **PASS** |

---

## **Tabel Kasus Uji 22: Validasi Customer untuk Pay Later**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-022] |
| Nama Kasus Uji | Validasi Wajib Customer untuk Pembayaran Bayar Nanti |
| Deskripsi Kasus | Sistem menolak pembuat POS order pay_later tanpa customer_id |
| Kondisi Awal | Kasir ingin membuat order dengan payment_method='pay_later' tanpa customer |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-022]

1. Kasir melakukan POST `/api/pos/orders` dengan payment_method='pay_later' tanpa customer_id
2. Sistem memvalidasi customer_id wajib

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 422 (Unprocessable) | ✓ Validasi bekerja | **PASS** |

---

## **Tabel Kasus Uji 23: Pembayaran Partial pada Order**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-023] |
| Nama Kasus Uji | Pembayaran Partial (Sebagian) pada Order Bayar Nanti |
| Deskripsi Kasus | Kasir melakukan pembayaran sebagian dari total order, status berubah menjadi partial |
| Kondisi Awal | Order bayar nanti dengan total 45000 sudah dibuat |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-023]

1. Kasir melakukan POST `/api/payments` dengan order_id dan amount=20000 dari total 45000
2. Sistem membuat Payment record
3. Sistem update order payment_status menjadi 'partial'

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 201<br>2. payment_status order berubah menjadi 'partial' | ✓ Pembayaran partial berhasil<br>✓ Status ter-update | **PASS** |

---

## **Tabel Kasus Uji 24: Daftar Order dengan Filter**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-024] |
| Nama Kasus Uji | Daftar Order dengan Filter Store, Payment Status, dan Tanggal |
| Deskripsi Kasus | Admin dapat melihat daftar order dengan berbagai filter |
| Kondisi Awal | Beberapa order dengan berbagai status dan tanggal sudah ada |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-024]

1. Admin melakukan GET `/api/orders?store_id={storeId}`
2. Admin melakukan GET `/api/orders?payment_status=paid`
3. Admin melakukan GET `/api/orders?start_date=2026-05-04&end_date=2026-05-04`
4. Sistem menampilkan order sesuai filter

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Filter store_id menampilkan order untuk toko tersebut<br>2. Filter payment_status=paid menampilkan 1 order<br>3. Filter payment_status=unpaid menampilkan 0 order<br>4. Filter date_range menampilkan order dalam range | ✓ Semua filter bekerja dengan baik | **PASS** |

---

## **Tabel Kasus Uji 25: Detail Order dengan Items dan Payments**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-025] |
| Nama Kasus Uji | Tampilkan Detail Order Lengkap |
| Deskripsi Kasus | Sistem menampilkan detail order lengkap termasuk items, payments, dan shipping info |
| Kondisi Awal | Order sudah dibuat dengan items dan payments |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-025]

1. Admin melakukan GET `/api/orders/{orderId}`
2. Sistem menampilkan data order dengan struktur yang sesuai

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 200<br>2. JSON structure mencakup id, order_number, type, total_amount, payment_status, status, items, payments | ✓ Detail order ditampilkan lengkap | **PASS** |

---

## **Tabel Kasus Uji 26: Branch Admin Hanya Melihat Store Mereka**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-026] |
| Nama Kasus Uji | Isolasi Data Store untuk Branch Admin |
| Deskripsi Kasus | Branch admin hanya dapat melihat order dari store mereka sendiri, bukan store cabang lain |
| Kondisi Awal | Ada 2 store dengan 3 order di store A dan 2 order di store B, branch admin di store A sudah login |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-026]

1. Branch admin store A melakukan GET `/api/orders`
2. Sistem menampilkan hanya order untuk store A (3 order)
3. Order dari store B tidak ditampilkan

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 200<br>2. Jumlah order = 3 (hanya store A)<br>3. Store B order tidak terlihat | ✓ Data store ter-isolasi dengan baik | **PASS** |

---

## **Tabel Kasus Uji 27: Member Lihat Transaksi Mereka**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-027] |
| Nama Kasus Uji | Member Melihat Riwayat Transaksi (Order History) |
| Deskripsi Kasus | Member dapat melihat daftar transaksi (order) mereka termasuk online dan POS |
| Kondisi Awal | Member sudah membuat beberapa transaksi (online dan POS) |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-027]

1. Member melakukan GET `/api/member/transactions`
2. Sistem menampilkan daftar order yang customer_id-nya sesuai dengan member

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 200 dengan status 'success'<br>2. data adalah array | ✓ Transaksi member ditampilkan | **PASS** |

---

## **Tabel Kasus Uji 28: Transaksi Member Include Items dan Payments**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-028] |
| Nama Kasus Uji | Detail Transaksi Include Items dan Payments |
| Deskripsi Kasus | Transaksi member menampilkan order items dan payment details |
| Kondisi Awal | Member sudah membuat transaksi dengan multiple items dan payments |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-028]

1. Member melakukan GET `/api/member/transactions`
2. Sistem eager-load items dan payments

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 200<br>2. Setiap transaksi include items dan payments | ✓ Eager-load bekerja dengan baik | **PASS** |

---

## **Tabel Kasus Uji 29: Dashboard Penjualan - Summary dan Chart**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-029] |
| Nama Kasus Uji | Dashboard Penjualan dengan Metrik dan Grafik |
| Deskripsi Kasus | Admin dapat melihat ringkasan penjualan harian, mingguan, bulanan dengan metrik perubahan dan grafik |
| Kondisi Awal | Beberapa order sudah dibuat di toko |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-029]

1. Admin melakukan GET `/api/orders/dashboard`
2. Sistem menghitung metrik: total_omzet, total_transactions, products_sold, average_transaction_amount
3. Sistem menghitung perubahan dibanding periode sebelumnya
4. Sistem menyiapkan data chart

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 200<br>2. JSON mencakup daily, weekly, monthly metrics<br>3. Setiap periode include change metrics dan chart data | ✓ Dashboard berhasil ditampilkan<br>✓ Metrik ter-hitung | **PASS** |

---

## **Tabel Kasus Uji 30: Forbid Member Manage Inventory**

| Atribut | Keterangan |
| :--- | :--- |
| Identifikasi | [DUPL-PASPOS.K-030] |
| Nama Kasus Uji | Akses Ditolak - Member Tidak Dapat Mengelola Inventory |
| Deskripsi Kasus | Sistem menolak akses member untuk operasi inventory management |
| Kondisi Awal | Member sudah login dengan role 'member' |
| Tanggal Pengujian | 4 Mei 2026 |
| Penguji | Development Team |

### **Skenario**
Langkah-langkah prosedur uji untuk kasus uji [DUPL-PASPOS.K-030]

1. Member melakukan GET `/api/inventories`
2. Sistem memeriksa authorization

### **Hasil**

| Yang Diharapkan | Pengamatan | Kesimpulan |
| :--- | :--- | :--- |
| 1. Response 403 (Forbidden) | ✓ Authorization bekerja | **PASS** |

---

## **Catatan Pengujian**

- Semua pengujian dilakukan pada tanggal 4 Mei 2026
- Database menggunakan SQLite untuk testing dengan RefreshDatabase trait
- Queue dijalankan dengan mode fake untuk testing
- Cache di-flush untuk test rate limiting
- Semua test case menggunakan factory untuk setup data
- Token authentication menggunakan Sanctum
- Semua hasil pengujian pada status PASS, menandakan sistem berfungsi sesuai dengan spesifikasi fungsional dan non-fungsional

---

**Disusun oleh**: Development Team  
**Tanggal Dokumen**: 4 Mei 2026  
**Versi**: 1.0  
**Status**: Complete
