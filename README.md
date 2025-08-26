# Aplikasi Cuti ASN & P3K

Sistem manajemen cuti pegawai untuk Aparatur Sipil Negara (ASN) dan Pegawai Pemerintah dengan Perjanjian Kerja (P3K) yang dibangun dengan framework Symfony.

## 📋 Deskripsi

Aplikasi ini menyediakan sistem pengelolaan cuti yang lengkap untuk instansi pemerintah, meliputi:

- **Pengajuan Cuti** - Pegawai dapat mengajukan berbagai jenis cuti
- **Persetujuan Cuti** - Sistem workflow persetujuan oleh atasan
- **Manajemen Hari Libur** - Pengelolaan hari libur nasional dan daerah
- **Perhitungan Otomatis** - Perhitungan hari kerja yang akurat (exclude weekend & hari libur)
- **Laporan Cuti** - Dashboard dan laporan penggunaan cuti
- **Multi-Role** - Mendukung role User, Approver, dan Admin

## ✨ Fitur Utama

### 👤 **Untuk Pegawai:**
- Pengajuan cuti dengan berbagai jenis (tahunan, sakit, melahirkan, dll)
- Tracking status pengajuan (draft, diajukan, disetujui, ditolak)
- Riwayat penggunaan cuti dan sisa hak cuti
- Edit dan ajukan ulang pengajuan yang ditolak

### 👨‍💼 **Untuk Atasan/Approver:**
- Dashboard persetujuan cuti
- Approve atau tolak pengajuan dengan alasan
- Monitoring pengajuan cuti bawahan

### 🔧 **Untuk Admin:**
- Master data: User, Jenis Cuti, Unit Kerja, Pejabat
- Manajemen hari libur nasional dan daerah
- Konfigurasi sistem dan aturan cuti
- Laporan komprehensif penggunaan cuti

## 🛠️ Teknologi yang Digunakan

- **Backend:** PHP 8.2+ dengan Symfony 6.4
- **Database:** MySQL/MariaDB
- **Frontend:** Twig Templates + Bootstrap 5 + JavaScript
- **Authentication:** Symfony Security
- **ORM:** Doctrine
- **CSS Framework:** Bootstrap 5 dengan Bootstrap Icons

## 📦 Instalasi

### Persyaratan Sistem
- PHP 8.2 atau lebih tinggi
- Composer
- MySQL/MariaDB
- Node.js & npm (opsional, untuk asset management)
- Symfony CLI (opsional, untuk development)

### Langkah Instalasi

1. **Clone repository**
   ```bash
   git clone https://github.com/echal/cuti.git
   cd cuti
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Install frontend dependencies (opsional)**
   ```bash
   npm install
   ```

4. **Konfigurasi environment**
   ```bash
   cp .env .env.local
   ```
   
   Edit file `.env.local` dan sesuaikan konfigurasi database:
   ```env
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/nama_database?serverVersion=8.0&charset=utf8mb4"
   ```

5. **Buat database dan jalankan migrasi**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. **Load fixtures (opsional - untuk data sample)**
   ```bash
   php bin/console doctrine:fixtures:load
   ```

7. **Generate keys untuk JWT (jika digunakan)**
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```

## 🚀 Menjalankan Aplikasi

### Development Server
```bash
# Menggunakan Symfony CLI (recommended)
symfony serve

# Atau menggunakan built-in PHP server
php -S localhost:8000 -t public/
```

Aplikasi akan berjalan di: `http://localhost:8000`

### Production
Untuk production, deploy ke web server seperti Apache atau Nginx dengan document root mengarah ke folder `public/`.

## 👥 User Default

Setelah menjalankan fixtures, Anda dapat login dengan:

**Admin:**
- Email: `admin@example.com`
- Password: `password`

**Approver:**
- Email: `approver@example.com`
- Password: `password`

**User:**
- Email: `user@example.com`
- Password: `password`

## 📚 Dokumentasi API

### Hari Libur API
- `GET /api/hari-libur/by-year/{year}` - Daftar hari libur per tahun
- `GET /api/hari-libur/check/{date}` - Cek apakah tanggal adalah hari libur
- `GET /api/hari-libur/range/{startDate}/{endDate}` - Hari libur dalam rentang tanggal

## 🏗️ Struktur Project

```
├── src/
│   ├── Controller/        # Controllers
│   │   ├── Admin/         # Admin controllers
│   │   └── Api/           # API controllers
│   ├── Entity/            # Doctrine entities
│   ├── Form/              # Symfony forms
│   ├── Repository/        # Doctrine repositories
│   ├── Service/           # Business logic services
│   └── Security/          # Security & authentication
├── templates/             # Twig templates
│   ├── admin/             # Admin templates
│   ├── pengajuan_cuti/    # Cuti application templates
│   └── base.html.twig     # Base template
├── migrations/            # Database migrations
├── public/                # Web accessible files
├── config/                # Configuration files
└── var/                   # Cache, logs, sessions
```

## 🔧 Konfigurasi

### Database
Edit konfigurasi database di `.env.local`:
```env
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0&charset=utf8mb4"
```

### Email (untuk notifikasi)
```env
MAILER_DSN=smtp://localhost:587?encryption=tls&auth_mode=login&username=&password=
```

### Environment
- `dev` - Development environment
- `prod` - Production environment
- `test` - Testing environment

## 📋 TODO & Roadmap

- [ ] Notifikasi email untuk persetujuan cuti
- [ ] Export laporan ke Excel/PDF
- [ ] Calendar view untuk cuti pegawai
- [ ] Mobile app integration
- [ ] API documentation dengan Swagger

## 🤝 Kontribusi

1. Fork repository
2. Buat branch feature (`git checkout -b feature/amazing-feature`)
3. Commit perubahan (`git commit -m 'Add some amazing feature'`)
4. Push ke branch (`git push origin feature/amazing-feature`)
5. Buat Pull Request

## 📝 Lisensi

Project ini menggunakan lisensi [MIT](LICENSE).

## 📞 Kontak

Untuk pertanyaan atau dukungan, silakan buat issue di repository ini atau hubungi:

- Email: [your-email@example.com]
- GitHub: [@echal](https://github.com/echal)

---

**Dibuat dengan ❤️ menggunakan Symfony Framework**