# SiPEMAU API Documentation

## Base URL

```
http://localhost/TUBES_PRK_PEMWEB_2025/kelompok/kelompok_19/backend/public
```

## Authentication

Aplikasi menggunakan session-based authentication. Setelah login, session akan disimpan otomatis.

---

## 📋 Table of Contents

- [Authentication](#authentication-endpoints)
- [Mahasiswa](#mahasiswa-endpoints)
- [Petugas](#petugas-endpoints)
- [Admin](#admin-endpoints)
- [Response Format](#response-format)
- [Error Codes](#error-codes)

---

## Authentication Endpoints

### Register Mahasiswa

**POST** `/register`

Registrasi akun mahasiswa baru.

**Request Body:**

```
name: string (required)
nim: string (required, unique)
email: string (required, unique, valid email)
password: string (required, min 8 chars)
confirm_password: string (required, must match password)
```

**Example:**

```bash
curl -X POST http://localhost/.../public/register \
  -d "name=John Doe" \
  -d "nim=2011521001" \
  -d "email=john@student.unila.ac.id" \
  -d "password=password123" \
  -d "confirm_password=password123"
```

**Success Response:**

- Status: 302 (Redirect to login)
- Session: `success` message set

**Error Response:**

- Session: `error` message set
- Redirects back to register page

---

### Login

**POST** `/login`

Login untuk semua role (Mahasiswa, Petugas, Admin).

**Request Body:**

```
email: string (required)
password: string (required)
```

**Example:**

```bash
curl -X POST http://localhost/.../public/login \
  -d "email=admin@sipemau.ac.id" \
  -d "password=password"
```

**Success Response:**

- Status: 302 (Redirect based on role)
- Session variables set:
  - `user_id`
  - `name`
  - `email`
  - `role` (MAHASISWA/PETUGAS/ADMIN)
  - Additional: `nim` (mahasiswa), `unit_id` & `jabatan` (petugas)

**Redirects:**

- Mahasiswa → `/mahasiswa/dashboard`
- Petugas → `/petugas/dashboard`
- Admin → `/admin/dashboard`

---

### Logout

**GET** `/logout`

Logout dan hapus session.

**Example:**

```bash
curl http://localhost/.../public/logout
```

**Response:**

- Status: 302 (Redirect to home)
- Session destroyed

---

## Mahasiswa Endpoints

### Dashboard

**GET** `/mahasiswa/dashboard`

Dashboard mahasiswa dengan statistik pengaduan.

**Auth Required:** Yes (MAHASISWA role)

**Response Data:**

```javascript
{
  stats: {
    total: int,
    menunggu: int,
    diproses: int,
    selesai: int
  },
  recentComplaints: [
    {
      id: int,
      title: string,
      category_name: string,
      unit_name: string,
      status: string,
      created_at: datetime
    }
  ]
}
```

---

### List Complaints

**GET** `/mahasiswa/complaints`

List semua pengaduan mahasiswa dengan pagination.

**Auth Required:** Yes (MAHASISWA role)

**Query Parameters:**

```
page: int (optional, default: 1)
status: string (optional, values: MENUNGGU|DIPROSES|SELESAI)
```

**Example:**

```bash
curl "http://localhost/.../public/mahasiswa/complaints?page=1&status=MENUNGGU"
```

**Response Data:**

```javascript
{
  complaints: [
    {
      id: int,
      mahasiswa_id: int,
      category_id: int,
      category_name: string,
      unit_name: string,
      title: string,
      description: string,
      evidence_path: string|null,
      status: string,
      created_at: datetime,
      updated_at: datetime,
      resolved_at: datetime|null
    }
  ],
  currentPage: int,
  totalPages: int,
  status: string
}
```

---

### Detail Complaint

**GET** `/mahasiswa/complaints/:id`

Detail pengaduan dengan catatan petugas.

**Auth Required:** Yes (MAHASISWA role)

**URL Parameters:**

```
id: int (complaint ID)
```

**Example:**

```bash
curl http://localhost/.../public/mahasiswa/complaints/1
```

**Response Data:**

```javascript
{
  complaint: {
    id: int,
    title: string,
    description: string,
    category_name: string,
    unit_name: string,
    status: string,
    evidence_path: string|null,
    created_at: datetime,
    updated_at: datetime,
    resolved_at: datetime|null
  },
  notes: [
    {
      id: int,
      petugas_name: string,
      jabatan: string,
      note: string,
      created_at: datetime
    }
  ]
}
```

---

### Create Complaint

**POST** `/mahasiswa/complaints/create`

Buat pengaduan baru dengan upload bukti opsional.

**Auth Required:** Yes (MAHASISWA role)

**Request Body (multipart/form-data):**

```
title: string (required, max 150 chars)
description: text (required)
category_id: int (required)
evidence: file (optional, max 5MB, types: jpg, jpeg, png, pdf)
```

**Example:**

```bash
curl -X POST http://localhost/.../public/mahasiswa/complaints/create \
  -F "title=Tidak bisa login ke SISTER" \
  -F "description=Saya sudah mencoba login berkali-kali..." \
  -F "category_id=6" \
  -F "evidence=@screenshot.png"
```

**Success Response:**

- Status: 302 (Redirect to complaints list)
- Session: `success` message set

**Error Response:**

- Session: `error` message set
- Redirects back

---

## Petugas Endpoints

### Dashboard

**GET** `/petugas/dashboard`

Dashboard petugas dengan statistik laporan unit.

**Auth Required:** Yes (PETUGAS role)

**Response Data:**

```javascript
{
  stats: {
    total: int,
    menunggu: int,
    diproses: int,
    selesai: int
  },
  recentComplaints: [
    {
      id: int,
      mahasiswa_name: string,
      nim: string,
      category_name: string,
      title: string,
      status: string,
      created_at: datetime
    }
  ]
}
```

---

### List Complaints

**GET** `/petugas/complaints`

List pengaduan untuk unit petugas.

**Auth Required:** Yes (PETUGAS role)

**Query Parameters:**

```
page: int (optional, default: 1)
status: string (optional)
category: int (optional, category ID)
```

**Example:**

```bash
curl "http://localhost/.../public/petugas/complaints?status=MENUNGGU&category=6"
```

---

### Detail Complaint

**GET** `/petugas/complaints/:id`

Detail pengaduan dengan informasi mahasiswa.

**Auth Required:** Yes (PETUGAS role)

**URL Parameters:**

```
id: int (complaint ID)
```

---

### Update Status

**POST** `/petugas/complaints/update-status`

Update status pengaduan.

**Auth Required:** Yes (PETUGAS role)

**Request Body:**

```
complaint_id: int (required)
status: string (required, values: MENUNGGU|DIPROSES|SELESAI)
```

**Example:**

```bash
curl -X POST http://localhost/.../public/petugas/complaints/update-status \
  -d "complaint_id=1" \
  -d "status=DIPROSES"
```

**Success Response:**

- Status: 302 (Redirect to complaint detail)
- Session: `success` message set
- If status = SELESAI, `resolved_at` timestamp is set

---

### Add Note

**POST** `/petugas/complaints/add-note`

Tambah catatan tindak lanjut.

**Auth Required:** Yes (PETUGAS role)

**Request Body:**

```
complaint_id: int (required)
note: text (required)
```

**Example:**

```bash
curl -X POST http://localhost/.../public/petugas/complaints/add-note \
  -d "complaint_id=1" \
  -d "note=Sedang kami proses, mohon ditunggu"
```

---

## Admin Endpoints

### Dashboard

**GET** `/admin/dashboard`

Dashboard admin dengan statistik keseluruhan sistem.

**Auth Required:** Yes (ADMIN role)

**Response Data:**

```javascript
{
  stats: {
    total_complaints: int,
    menunggu: int,
    diproses: int,
    selesai: int,
    total_mahasiswa: int,
    total_petugas: int,
    total_units: int,
    total_categories: int
  },
  complaintsByUnit: [
    {
      unit_name: string,
      total: int
    }
  ]
}
```

---

### Units Management

#### List Units

**GET** `/admin/units`

List semua unit dengan jumlah kategori dan petugas.

**Auth Required:** Yes (ADMIN role)

---

#### Create Unit

**POST** `/admin/units/create`

Buat unit baru.

**Auth Required:** Yes (ADMIN role)

**Request Body:**

```
name: string (required, unique)
description: text (optional)
is_active: int (optional, default: 1, values: 0|1)
```

---

#### Update Unit

**POST** `/admin/units/update/:id`

Update unit.

**Auth Required:** Yes (ADMIN role)

**URL Parameters:**

```
id: int (unit ID)
```

**Request Body:** Same as Create Unit

---

#### Delete Unit

**POST** `/admin/units/delete/:id`

Hapus unit (tidak bisa jika masih memiliki kategori).

**Auth Required:** Yes (ADMIN role)

**URL Parameters:**

```
id: int (unit ID)
```

---

### Categories Management

#### List Categories

**GET** `/admin/categories`

List semua kategori dengan jumlah pengaduan.

**Auth Required:** Yes (ADMIN role)

---

#### Create Category

**POST** `/admin/categories/create`

Buat kategori baru.

**Auth Required:** Yes (ADMIN role)

**Request Body:**

```
name: string (required, unique)
description: text (optional)
unit_id: int (required)
is_active: int (optional, default: 1)
```

---

#### Update Category

**POST** `/admin/categories/update/:id`

Update kategori.

**Auth Required:** Yes (ADMIN role)

---

#### Delete Category

**POST** `/admin/categories/delete/:id`

Hapus kategori (tidak bisa jika masih memiliki pengaduan).

**Auth Required:** Yes (ADMIN role)

---

### Petugas Management

#### List Petugas

**GET** `/admin/petugas`

List semua akun petugas.

**Auth Required:** Yes (ADMIN role)

---

#### Create Petugas

**POST** `/admin/petugas/create`

Buat akun petugas baru.

**Auth Required:** Yes (ADMIN role)

**Request Body:**

```
name: string (required)
email: string (required, unique, valid email)
password: string (required, min 8 chars)
unit_id: int (required)
jabatan: string (optional)
```

---

#### Update Petugas

**POST** `/admin/petugas/update/:id`

Update akun petugas.

**Auth Required:** Yes (ADMIN role)

**Request Body:**

```
name: string (required)
email: string (required)
password: string (optional, leave empty to keep current)
unit_id: int (required)
jabatan: string (optional)
```

---

#### Delete Petugas

**POST** `/admin/petugas/delete/:id`

Hapus akun petugas.

**Auth Required:** Yes (ADMIN role)

---

## Response Format

### Success (with redirect)

```
HTTP/1.1 302 Found
Location: /path/to/redirect
Set-Cookie: PHPSESSID=...
```

Session variable `success` or `error` contains the message.

### Page Load (HTML)

```
HTTP/1.1 200 OK
Content-Type: text/html

<!DOCTYPE html>
...
```

---

## Error Codes

| HTTP Code | Description                         |
| --------- | ----------------------------------- |
| 200       | Success                             |
| 302       | Redirect (success/error in session) |
| 403       | Forbidden (unauthorized access)     |
| 404       | Not Found                           |
| 500       | Internal Server Error               |

---

## Status Values

### Complaint Status

- `MENUNGGU` - Waiting for processing
- `DIPROSES` - Being processed
- `SELESAI` - Completed

### User Roles

- `MAHASISWA` - Student
- `PETUGAS` - Unit staff
- `ADMIN` - System administrator

---

## File Upload

**Allowed Extensions:** jpg, jpeg, png, pdf  
**Max File Size:** 5 MB  
**Upload Directory:** `backend/assets/uploads/`

Files are renamed to: `{unique_id}_{timestamp}.{ext}`

---

## Testing with Postman

1. Import `postman_collection.json`
2. Set variable `baseUrl` to your server URL
3. Start with Login request to create session
4. Test other endpoints (session will be maintained)

---

## Security Notes

- All passwords are hashed using bcrypt
- SQL injection prevention via PDO prepared statements
- Input sanitization on all user inputs
- Role-based access control on all protected routes
- File upload validation
- CSRF token support (implement in forms)

---

## Default Test Accounts

```
Admin:
Email: admin@sipemau.ac.id
Password: password

Petugas:
Email: budi@sipemau.ac.id
Password: password

Mahasiswa:
Email: john@student.unila.ac.id
Password: password
```

**⚠️ Ganti password setelah login pertama!**
