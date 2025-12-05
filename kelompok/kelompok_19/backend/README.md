# SiPEMAU Backend - PHP Native

Backend untuk Sistem Pengaduan Mahasiswa Universitas Lampung (SiPEMAU) menggunakan PHP Native dan MySQL.

## Struktur Folder

```
backend/
├── config/           # Konfigurasi aplikasi
│   ├── app.php      # Konfigurasi umum
│   └── database.php # Konfigurasi database
├── core/            # Core framework
│   ├── Controller.php
│   ├── Router.php
│   └── Helper.php
├── modules/         # Modul aplikasi
│   ├── auth/        # Authentication
│   ├── mahasiswa/   # Fitur mahasiswa
│   ├── petugas/     # Fitur petugas
│   ├── admin/       # Fitur admin
│   └── pages/       # Public pages
├── public/          # Public directory (document root)
│   ├── index.php    # Entry point
│   └── .htaccess    # Apache config
└── assets/          # Assets
    └── uploads/     # Upload directory
```

## Setup

### Requirements

- PHP 7.4 atau lebih tinggi
- MySQL 8.0 atau MariaDB 10.5
- Apache dengan mod_rewrite enabled
- Extension: PDO, PDO_MySQL

### Instalasi

1. **Import Database**

   ```bash
   mysql -u root -p < database/SIPEMAU.sql
   mysql -u root -p sipemau_db < database/seed.sql
   ```

2. **Setup Environment**

   Copy `.env.example` ke `.env`:

   ```bash
   cp .env.example .env
   ```

   Edit `.env` sesuai konfigurasi:

   ```env
   DB_HOST=localhost
   DB_NAME=sipemau_db
   DB_USER=root
   DB_PASS=

   APP_URL=http://localhost/TUBES_PRK_PEMWEB_2025/kelompok/kelompok_19/backend/public
   APP_DEBUG=true
   ```

3. **Set Permissions**

   ```bash
   chmod 755 assets/uploads
   ```

4. **Apache Configuration**

   Set document root ke folder `public/`:

   ```apache
   <VirtualHost *:80>
       DocumentRoot "/path/to/backend/public"
       <Directory "/path/to/backend/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

## Fitur

### Mahasiswa

- ✅ Registrasi dan login
- ✅ Buat pengaduan (dengan upload bukti)
- ✅ Lihat daftar pengaduan
- ✅ Lihat detail pengaduan dan status

### Petugas

- ✅ Login (akun dibuat oleh admin)
- ✅ Dashboard laporan unit
- ✅ Update status pengaduan
- ✅ Tambah catatan tindak lanjut

### Admin

- ✅ Dashboard monitoring
- ✅ CRUD Unit
- ✅ CRUD Kategori
- ✅ CRUD Akun Petugas

## Testing API

### Postman Collection

Import file `postman_collection.json` ke Postman:

1. Buka Postman
2. File → Import → pilih `postman_collection.json`
3. Set variable `baseUrl` di Collection settings
4. Mulai testing dari request "Login" untuk create session

### API Documentation

Lihat dokumentasi lengkap di **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)**

### Manual Testing dengan cURL

```bash
# Login
curl -X POST http://localhost/.../public/login \
  -d "email=admin@sipemau.ac.id" \
  -d "password=password" \
  -c cookies.txt

# Create complaint (menggunakan session dari login)
curl -X POST http://localhost/.../public/mahasiswa/complaints/create \
  -b cookies.txt \
  -F "title=Test Complaint" \
  -F "description=Testing API" \
  -F "category_id=6"
```

## Routing

### Public Routes

- `GET /` - Landing page
- `GET /about` - About page
- `GET /login` - Login page
- `POST /login` - Process login
- `GET /register` - Register page
- `POST /register` - Process registration

### Mahasiswa Routes

- `GET /mahasiswa/dashboard` - Dashboard
- `GET /mahasiswa/complaints` - List complaints
- `GET /mahasiswa/complaints/create` - Create form
- `POST /mahasiswa/complaints/create` - Store complaint
- `GET /mahasiswa/complaints/:id` - Detail complaint

### Petugas Routes

- `GET /petugas/dashboard` - Dashboard
- `GET /petugas/complaints` - List complaints
- `GET /petugas/complaints/:id` - Detail complaint
- `POST /petugas/complaints/update-status` - Update status
- `POST /petugas/complaints/add-note` - Add note

### Admin Routes

- `GET /admin/dashboard` - Dashboard
- `GET /admin/units` - Manage units
- `GET /admin/categories` - Manage categories
- `GET /admin/petugas` - Manage petugas

## Security Features

- ✅ Environment variables (.env)
- ✅ Password hashing dengan bcrypt
- ✅ Prepared statements (SQL injection prevention)
- ✅ Input sanitization
- ✅ CSRF token support
- ✅ Role-based access control
- ✅ File upload validation

## Default Accounts

Setelah import `seed.sql`, gunakan akun berikut untuk testing:

```
Admin:
  Email: admin@sipemau.ac.id
  Password: password

Petugas (Unit TI):
  Email: budi@sipemau.ac.id
  Password: password

Mahasiswa:
  Email: john@student.unila.ac.id
  Password: password
  NIM: 2011521001
```

**⚠️ Segera ganti password setelah login pertama untuk production!**

## API Response Format

Untuk AJAX requests, gunakan header `Accept: application/json`:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {}
}
```

## Development

### Debug Mode

Set di `.env`:

```env
APP_ENV=development
APP_DEBUG=true
```

Ini akan mengaktifkan error display. **⚠️ Set `APP_DEBUG=false` untuk production!**

### Environment Variables

Semua konfigurasi ada di `.env`:

- `DB_*` - Database credentials
- `APP_URL` - Base URL aplikasi
- `APP_DEBUG` - Debug mode
- `UPLOAD_MAX_SIZE` - Max upload size (bytes)
- `UPLOAD_ALLOWED_EXT` - Allowed file extensions
- `SESSION_LIFETIME` - Session timeout (seconds)
- `PASSWORD_MIN_LENGTH` - Min password length
- `ITEMS_PER_PAGE` - Pagination items

### Structure

Backend mengikuti pola **MVC sederhana**:

- **Models**: Query langsung di Controller (untuk simplicity)
- **Views**: File PHP di `modules/*/`
- **Controllers**: Class di `modules/*/Controller.php`
- **Router**: Simple regex-based routing di `core/Router.php`

## License

Tugas Besar Praktikum Pemrograman Web 2025
