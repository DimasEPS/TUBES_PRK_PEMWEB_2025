# SiPEMAU REST API Documentation

REST API untuk Sistem Pengaduan Mahasiswa Universitas Lampung

## Base URL

```
http://localhost:8000
```

## Authentication

Aplikasi menggunakan **session-based authentication**. Setelah login, session akan disimpan otomatis dan digunakan untuk request berikutnya.

**Note:** Semua endpoint mengembalikan response dalam format **JSON**.

---

## 📋 Table of Contents

- [Response Format](#response-format)
- [Error Codes](#error-codes)
- [Authentication](#authentication-endpoints)
- [Mahasiswa](#mahasiswa-endpoints)
- [Petugas](#petugas-endpoints)
- [Admin](#admin-endpoints)

---

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "errors": ["Error 1", "Error 2"] // Optional, for validation errors
}
```

---

## Error Codes

| Status Code | Meaning                                 |
| ----------- | --------------------------------------- |
| 200         | OK - Request successful                 |
| 201         | Created - Resource created successfully |
| 400         | Bad Request - Validation error          |
| 401         | Unauthorized - Authentication failed    |
| 403         | Forbidden - No permission               |
| 404         | Not Found - Resource not found          |
| 409         | Conflict - Resource already exists      |
| 500         | Internal Server Error                   |
| 501         | Not Implemented                         |

---

## Authentication Endpoints

### 1. Register Mahasiswa

**POST** `/register`

Registrasi akun mahasiswa baru.

**Request Body:**

```
Content-Type: application/x-www-form-urlencoded

name=John Doe
nim=2011521001
email=john@student.unila.ac.id
password=password123
confirm_password=password123
```

**Success Response (201):**

```json
{
  "success": true,
  "message": "Registrasi berhasil",
  "data": {
    "id": 4,
    "name": "John Doe",
    "email": "john@student.unila.ac.id",
    "nim": "2011521001"
  }
}
```

**Error Response (400):**

```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": ["Nama harus diisi", "Email tidak valid"]
}
```

---

### 2. Login

**POST** `/login`

Login untuk semua role (Mahasiswa, Petugas, Admin).

**Request Body:**

```
Content-Type: application/x-www-form-urlencoded

email=admin@sipemau.ac.id
password=password
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "id": 1,
    "name": "Admin SiPEMAU",
    "email": "admin@sipemau.ac.id",
    "role": "ADMIN"
  }
}
```

**For Mahasiswa:**

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "id": 3,
    "name": "John Doe",
    "email": "john@student.unila.ac.id",
    "role": "MAHASISWA",
    "nim": "2011521001"
  }
}
```

---

### 3. Logout

**GET** `/logout`

Logout dan destroy session.

**Success Response (200):**

```json
{
  "success": true,
  "message": "Logout berhasil"
}
```

---

## Mahasiswa Endpoints

**Authentication Required:** Role `MAHASISWA`

### 1. Dashboard

**GET** `/mahasiswa/dashboard`

Get statistik pengaduan mahasiswa dan recent complaints.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "stats": {
      "total": 5,
      "menunggu": 2,
      "diproses": 1,
      "selesai": 2
    },
    "recent_complaints": [
      {
        "id": 1,
        "title": "Tidak bisa login SISTER",
        "status": "DIPROSES",
        "category_name": "Sistem Informasi",
        "unit_name": "UPT TIK",
        "created_at": "2024-12-06 10:30:00"
      }
    ]
  }
}
```

---

### 2. List Complaints

**GET** `/mahasiswa/complaints?page=1&status=MENUNGGU`

Get list pengaduan dengan pagination dan filter.

**Query Parameters:**

- `page` (optional): Page number (default: 1)
- `status` (optional): Filter by status (MENUNGGU, DIPROSES, SELESAI)

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "complaints": [
      {
        "id": 1,
        "title": "Tidak bisa login SISTER",
        "description": "Detail pengaduan...",
        "status": "MENUNGGU",
        "category_name": "Sistem Informasi",
        "unit_name": "UPT TIK",
        "created_at": "2024-12-06 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3,
      "total_items": 25,
      "per_page": 10
    },
    "filter": {
      "status": "MENUNGGU"
    }
  }
}
```

---

### 3. Detail Complaint

**GET** `/mahasiswa/complaints/:id`

Get detail pengaduan dan notes.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "complaint": {
      "id": 1,
      "title": "Tidak bisa login SISTER",
      "description": "Detail lengkap...",
      "status": "DIPROSES",
      "evidence_path": "/uploads/evidence_123.jpg",
      "category_name": "Sistem Informasi",
      "unit_name": "UPT TIK",
      "created_at": "2024-12-06 10:30:00",
      "resolved_at": null
    },
    "notes": [
      {
        "id": 1,
        "note": "Sedang kami proses",
        "petugas_name": "Budi Santoso",
        "jabatan": "Staff",
        "created_at": "2024-12-06 11:00:00"
      }
    ]
  }
}
```

**Error Response (404):**

```json
{
  "success": false,
  "message": "Pengaduan tidak ditemukan"
}
```

---

### 4. Get Categories

**GET** `/mahasiswa/complaints/categories`

Get list kategori aktif untuk form create complaint.

**Success Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Sistem Informasi",
      "description": "Masalah terkait sistem informasi",
      "unit_name": "UPT TIK",
      "unit_id": 1
    }
  ]
}
```

---

### 5. Create Complaint

**POST** `/mahasiswa/complaints/create`

Buat pengaduan baru dengan upload file (optional).

**Request Body:**

```
Content-Type: multipart/form-data

title=Tidak bisa login SISTER
description=Saya sudah mencoba berkali-kali...
category_id=6
evidence=<file> (optional)
```

**Success Response (201):**

```json
{
  "success": true,
  "message": "Pengaduan berhasil dikirim",
  "data": {
    "complaint_id": 7
  }
}
```

**Error Response (400):**

```json
{
  "success": false,
  "message": "Validasi gagal",
  "errors": ["Judul harus diisi", "Kategori harus dipilih"]
}
```

---

## Petugas Endpoints

**Authentication Required:** Role `PETUGAS`

### 1. Dashboard

**GET** `/petugas/dashboard`

Get statistik pengaduan unit petugas dan recent complaints.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "stats": {
      "total": 15,
      "menunggu": 5,
      "diproses": 7,
      "selesai": 3
    },
    "recent_complaints": [
      {
        "id": 1,
        "title": "Tidak bisa login SISTER",
        "status": "MENUNGGU",
        "category_name": "Sistem Informasi",
        "mahasiswa_name": "John Doe",
        "nim": "2011521001",
        "created_at": "2024-12-06 10:30:00"
      }
    ]
  }
}
```

---

### 2. List Complaints

**GET** `/petugas/complaints?page=1&status=MENUNGGU&category=6`

Get list pengaduan untuk unit petugas.

**Query Parameters:**

- `page` (optional): Page number
- `status` (optional): Filter by status
- `category` (optional): Filter by category ID

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "complaints": [...],
    "categories": [
      {"id": 1, "name": "Sistem Informasi"}
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 2,
      "total_items": 15,
      "per_page": 10
    },
    "filter": {
      "status": "MENUNGGU",
      "category": "6"
    }
  }
}
```

---

### 3. Detail Complaint

**GET** `/petugas/complaints/:id`

Get detail pengaduan dengan info mahasiswa dan notes.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "complaint": {
      "id": 1,
      "title": "Tidak bisa login SISTER",
      "description": "Detail...",
      "status": "MENUNGGU",
      "category_name": "Sistem Informasi",
      "mahasiswa_name": "John Doe",
      "nim": "2011521001",
      "mahasiswa_email": "john@student.unila.ac.id",
      "evidence_path": "/uploads/evidence_123.jpg",
      "created_at": "2024-12-06 10:30:00"
    },
    "notes": [...]
  }
}
```

---

### 4. Update Status

**POST** `/petugas/complaints/update-status`

Update status pengaduan.

**Request Body:**

```
Content-Type: application/x-www-form-urlencoded

complaint_id=1
status=DIPROSES
```

**Status Options:** `MENUNGGU`, `DIPROSES`, `SELESAI`

**Success Response (200):**

```json
{
  "success": true,
  "message": "Status berhasil diperbarui",
  "data": {
    "status": "DIPROSES",
    "resolved_at": null
  }
}
```

**Error Response (400):**

```json
{
  "success": false,
  "message": "Status tidak valid"
}
```

---

### 5. Add Note

**POST** `/petugas/complaints/add-note`

Tambahkan catatan follow-up ke pengaduan.

**Request Body:**

```
Content-Type: application/x-www-form-urlencoded

complaint_id=1
note=Sedang kami proses, mohon ditunggu
```

**Success Response (201):**

```json
{
  "success": true,
  "message": "Catatan berhasil ditambahkan",
  "data": {
    "note_id": 5
  }
}
```

---

## Admin Endpoints

**Authentication Required:** Role `ADMIN`

### 1. Dashboard

**GET** `/admin/dashboard`

Get statistik keseluruhan sistem.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "stats": {
      "total": 50,
      "menunggu": 15,
      "diproses": 20,
      "selesai": 15,
      "total_mahasiswa": 150,
      "total_petugas": 10,
      "total_units": 5,
      "total_categories": 20
    },
    "complaints_by_unit": [
      {
        "unit_name": "UPT TIK",
        "total": 25
      }
    ]
  }
}
```

---

### 2. Units Management

#### List Units

**GET** `/admin/units`

Get list semua unit.

**Success Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "UPT TIK",
      "description": "Unit Pelaksana Teknis TIK",
      "is_active": 1,
      "total_categories": 5,
      "total_petugas": 3,
      "created_at": "2024-01-01 00:00:00"
    }
  ]
}
```

---

#### Create Unit

**POST** `/admin/units/create`

Buat unit baru.

**Request Body:**

```
Content-Type: application/x-www-form-urlencoded

name=Unit Baru
description=Deskripsi unit
is_active=1
```

**Success Response (201):**

```json
{
  "success": true,
  "message": "Unit berhasil dibuat",
  "data": {
    "unit_id": 6
  }
}
```

---

#### Update Unit

**POST** `/admin/units/update/:id`

Update unit yang ada.

**Request Body:**

```
name=Unit Updated
description=Deskripsi baru
is_active=1
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Unit berhasil diperbarui"
}
```

---

#### Delete Unit

**POST** `/admin/units/delete/:id`

Hapus unit (harus tidak memiliki kategori).

**Success Response (200):**

```json
{
  "success": true,
  "message": "Unit berhasil dihapus"
}
```

**Error Response (409):**

```json
{
  "success": false,
  "message": "Unit masih memiliki 5 kategori. Hapus kategori terlebih dahulu."
}
```

---

### 3. Categories Management

#### List Categories

**GET** `/admin/categories`

Get list semua kategori.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Sistem Informasi",
        "description": "Masalah sistem informasi",
        "unit_id": 1,
        "unit_name": "UPT TIK",
        "is_active": 1,
        "total_complaints": 15
      }
    ],
    "units": [{ "id": 1, "name": "UPT TIK" }]
  }
}
```

---

#### Create Category

**POST** `/admin/categories/create`

Buat kategori baru.

**Request Body:**

```
name=Kategori Baru
description=Deskripsi kategori
unit_id=1
is_active=1
```

**Success Response (201):**

```json
{
  "success": true,
  "message": "Kategori berhasil dibuat",
  "data": {
    "category_id": 21
  }
}
```

---

#### Update Category

**POST** `/admin/categories/update/:id`

Update kategori.

**Success Response (200):**

```json
{
  "success": true,
  "message": "Kategori berhasil diperbarui"
}
```

---

#### Delete Category

**POST** `/admin/categories/delete/:id`

Hapus kategori (harus tidak memiliki pengaduan).

**Error Response (409):**

```json
{
  "success": false,
  "message": "Kategori masih memiliki 10 pengaduan. Tidak dapat dihapus."
}
```

---

### 4. Petugas Management

#### List Petugas

**GET** `/admin/petugas`

Get list semua petugas.

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "petugas_list": [
      {
        "id": 2,
        "name": "Budi Santoso",
        "email": "budi@sipemau.ac.id",
        "unit_id": 1,
        "unit_name": "UPT TIK",
        "jabatan": "Staff",
        "created_at": "2024-01-01 00:00:00"
      }
    ],
    "units": [{ "id": 1, "name": "UPT TIK" }]
  }
}
```

---

#### Create Petugas

**POST** `/admin/petugas/create`

Buat akun petugas baru.

**Request Body:**

```
name=Petugas Baru
email=petugas@sipemau.ac.id
password=password123
unit_id=1
jabatan=Staff
```

**Success Response (201):**

```json
{
  "success": true,
  "message": "Petugas berhasil dibuat",
  "data": {
    "petugas_id": 5
  }
}
```

**Error Response (409):**

```json
{
  "success": false,
  "message": "Email sudah terdaftar"
}
```

---

#### Update Petugas

**POST** `/admin/petugas/update/:id`

Update data petugas (password optional).

**Request Body:**

```
name=Petugas Updated
email=petugas@sipemau.ac.id
password= (leave empty to keep current password)
unit_id=1
jabatan=Supervisor
```

**Success Response (200):**

```json
{
  "success": true,
  "message": "Petugas berhasil diperbarui"
}
```

---

#### Delete Petugas

**POST** `/admin/petugas/delete/:id`

Hapus akun petugas.

**Success Response (200):**

```json
{
  "success": true,
  "message": "Petugas berhasil dihapus"
}
```

---

## Default Accounts

Untuk testing, gunakan akun default berikut:

| Role      | Email                    | Password |
| --------- | ------------------------ | -------- |
| Admin     | admin@sipemau.ac.id      | password |
| Petugas   | budi@sipemau.ac.id       | password |
| Mahasiswa | john@student.unila.ac.id | password |

---

## CORS Support

API mendukung CORS untuk frontend development:

- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With`

---

## Frontend Integration

Contoh fetch dari JavaScript:

```javascript
// Login
const login = async (email, password) => {
  const formData = new URLSearchParams();
  formData.append("email", email);
  formData.append("password", password);

  const response = await fetch("http://localhost:8000/login", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: formData,
    credentials: "include", // Important for session
  });

  const data = await response.json();
  return data;
};

// Get complaints
const getComplaints = async (page = 1) => {
  const response = await fetch(
    `http://localhost:8000/mahasiswa/complaints?page=${page}`,
    {
      credentials: "include", // Important for session
    }
  );

  const data = await response.json();
  return data;
};

// Create complaint with file
const createComplaint = async (formData) => {
  const response = await fetch(
    "http://localhost:8000/mahasiswa/complaints/create",
    {
      method: "POST",
      body: formData, // FormData object with file
      credentials: "include",
    }
  );

  const data = await response.json();
  return data;
};
```

---

## Notes

- Session cookie akan diset otomatis setelah login
- Semua request harus include `credentials: 'include'` untuk mengirim session cookie
- File upload maksimal 2MB (configurable di .env)
- Allowed file extensions: jpg, jpeg, png, pdf
