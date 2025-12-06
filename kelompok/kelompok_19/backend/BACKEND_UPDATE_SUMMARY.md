# Backend Update Summary - JSON API

## ✅ Completed Changes

Seluruh backend SiPEMAU telah dikonversi dari MVC tradisional menjadi **REST API** yang mengembalikan **JSON responses**.

---

## 📝 Changes Overview

### 1. **Controller Updates**

#### AuthController

- ✅ `login()` - Return JSON dengan user data dan role info
- ✅ `register()` - Return JSON dengan created user data (201)
- ✅ `logout()` - Return JSON success message
- ❌ Removed `showLogin()` dan `showRegister()` (tidak diperlukan)

#### MahasiswaController

- ✅ `dashboard()` - Return JSON stats dan recent complaints
- ✅ `listComplaints()` - Return JSON dengan pagination
- ✅ `detailComplaint()` - Return JSON complaint detail dengan notes
- ✅ `getCategories()` - Return JSON active categories (renamed dari showCreateComplaint)
- ✅ `storeComplaint()` - Return JSON dengan complaint_id (201)

#### PetugasController

- ✅ `dashboard()` - Return JSON stats unit
- ✅ `listComplaints()` - Return JSON dengan filter categories
- ✅ `detailComplaint()` - Return JSON dengan mahasiswa info
- ✅ `updateStatus()` - Return JSON success dengan status baru
- ✅ `addNote()` - Return JSON dengan note_id (201)

#### AdminController

- ✅ `dashboard()` - Return JSON overall stats
- ✅ `listUnits()` - Return JSON units dengan stats
- ✅ `createUnit()` - Return JSON dengan unit_id (201)
- ✅ `updateUnit()` - Return JSON success
- ✅ `deleteUnit()` - Return JSON success atau conflict (409)
- ✅ `listCategories()` - Return JSON categories + units
- ✅ `createCategory()` - Return JSON dengan category_id (201)
- ✅ `updateCategory()` - Return JSON success
- ✅ `deleteCategory()` - Return JSON success atau conflict (409)
- ✅ `listPetugas()` - Return JSON petugas + units
- ✅ `createPetugas()` - Return JSON dengan petugas_id (201)
- ✅ `updatePetugas()` - Return JSON success
- ✅ `deletePetugas()` - Return JSON success

#### PageController

- ✅ `index()` - Return JSON API info
- ✅ `about()` - Return JSON documentation info
- ✅ `notFound()` - Return JSON error (404)
- ✅ `unauthorized()` - Return JSON error (403)

#### Controller Base Class

- ✅ `view()` method - Sekarang return JSON 501 (Not Implemented) instead of fatal error

---

### 2. **CORS Support**

Added CORS headers di `index.php`:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
```

Handles preflight OPTIONS requests automatically.

---

### 3. **Routes Update**

Removed GET routes untuk form pages:

- ❌ `GET /login` (showLogin)
- ❌ `GET /register` (showRegister)
- ❌ `GET /mahasiswa/complaints/create` (showCreateComplaint)

Added new route:

- ✅ `GET /mahasiswa/complaints/categories` (getCategories)

---

### 4. **HTTP Status Codes**

Semua endpoint sekarang menggunakan proper HTTP status codes:

| Status | Usage                                    |
| ------ | ---------------------------------------- |
| 200    | Success (GET, POST update/delete)        |
| 201    | Created (POST create)                    |
| 400    | Bad Request (validation errors)          |
| 401    | Unauthorized (login failed)              |
| 403    | Forbidden (no permission)                |
| 404    | Not Found                                |
| 409    | Conflict (duplicate, cascade constraint) |
| 500    | Internal Server Error                    |
| 501    | Not Implemented (view rendering)         |

---

### 5. **Response Format**

Semua responses menggunakan consistent format:

**Success:**

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error:**

```json
{
  "success": false,
  "message": "Error message",
  "errors": ["Field error 1", "Field error 2"] // optional
}
```

---

### 6. **Documentation**

Created new comprehensive API documentation:

- ✅ `API_DOCUMENTATION_JSON.md` - Complete JSON API documentation
- ✅ Includes all endpoints dengan request/response examples
- ✅ Frontend integration examples (JavaScript fetch)
- ✅ Default test accounts
- ✅ CORS configuration info

---

### 7. **Postman Collection**

Updated:

- ✅ Base URL changed to `http://localhost:8000`
- ✅ Added "Get Categories" endpoint di Mahasiswa section
- ✅ All endpoints ready for JSON responses

---

## 🚀 How to Run

### Start PHP Server:

```bash
cd /home/dimseps/coding-project/TUBES_PRK_PEMWEB_2025/kelompok/kelompok_19/backend/public
php -S localhost:8000
```

### Test dengan Postman:

1. Import `postman_collection.json`
2. Base URL sudah diset ke `http://localhost:8000`
3. Test endpoint login dulu untuk mendapatkan session
4. Test endpoint lainnya (session otomatis ter-include)

### Test dengan cURL:

```bash
# Login
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=admin@sipemau.ac.id&password=password" \
  -c cookies.txt

# Get dashboard (dengan session dari login)
curl http://localhost:8000/admin/dashboard -b cookies.txt
```

---

## 🎯 Frontend Integration

### JavaScript Fetch Example:

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
    credentials: "include", // ⚠️ PENTING untuk session
  });

  const data = await response.json();

  if (data.success) {
    console.log("Login success:", data.data);
    // Redirect atau simpan user info
  } else {
    console.error("Login failed:", data.message);
  }
};

// Get data dengan authentication
const getComplaints = async (page = 1) => {
  const response = await fetch(
    `http://localhost:8000/mahasiswa/complaints?page=${page}`,
    {
      credentials: "include", // ⚠️ PENTING untuk session
    }
  );

  const data = await response.json();
  return data;
};

// Create complaint dengan file upload
const createComplaint = async (title, description, categoryId, file) => {
  const formData = new FormData();
  formData.append("title", title);
  formData.append("description", description);
  formData.append("category_id", categoryId);
  if (file) {
    formData.append("evidence", file);
  }

  const response = await fetch(
    "http://localhost:8000/mahasiswa/complaints/create",
    {
      method: "POST",
      body: formData,
      credentials: "include", // ⚠️ PENTING untuk session
    }
  );

  const data = await response.json();
  return data;
};
```

---

## ⚠️ Important Notes

1. **Session Cookies**: Frontend harus include `credentials: 'include'` di semua fetch requests untuk mengirim session cookie.

2. **CORS**: Jika frontend jalan di domain/port berbeda, CORS sudah di-handle di backend.

3. **File Upload**: Gunakan `FormData` bukan `URLSearchParams` untuk request dengan file.

4. **Content-Type**:

   - Form data biasa: `application/x-www-form-urlencoded`
   - File upload: `multipart/form-data` (automatic dengan FormData)
   - GET requests: tidak perlu Content-Type

5. **Session Persistence**: Session disimpan di server. Pastikan PHP session_start() sudah berjalan (already handled di index.php).

---

## 🧪 Testing Checklist

### Authentication

- [ ] POST /register - Create new mahasiswa account
- [ ] POST /login - Login dengan berbagai role
- [ ] GET /logout - Logout dan destroy session

### Mahasiswa

- [ ] GET /mahasiswa/dashboard - Stats dan recent complaints
- [ ] GET /mahasiswa/complaints - List dengan pagination
- [ ] GET /mahasiswa/complaints/:id - Detail dengan notes
- [ ] GET /mahasiswa/complaints/categories - Active categories list
- [ ] POST /mahasiswa/complaints/create - Create dengan file upload

### Petugas

- [ ] GET /petugas/dashboard - Stats unit
- [ ] GET /petugas/complaints - List dengan filter
- [ ] GET /petugas/complaints/:id - Detail dengan mahasiswa info
- [ ] POST /petugas/complaints/update-status - Update status complaint
- [ ] POST /petugas/complaints/add-note - Add follow-up note

### Admin

- [ ] GET /admin/dashboard - Overall stats
- [ ] GET /admin/units - List all units
- [ ] POST /admin/units/create - Create unit
- [ ] POST /admin/units/update/:id - Update unit
- [ ] POST /admin/units/delete/:id - Delete unit (with cascade check)
- [ ] GET /admin/categories - List all categories
- [ ] POST /admin/categories/create - Create category
- [ ] POST /admin/categories/update/:id - Update category
- [ ] POST /admin/categories/delete/:id - Delete category (with cascade check)
- [ ] GET /admin/petugas - List all petugas
- [ ] POST /admin/petugas/create - Create petugas account
- [ ] POST /admin/petugas/update/:id - Update petugas
- [ ] POST /admin/petugas/delete/:id - Delete petugas

---

## 📚 Documentation Files

1. **API_DOCUMENTATION_JSON.md** - Comprehensive JSON API documentation (NEW)
2. **API_DOCUMENTATION.md** - Original documentation (partially updated)
3. **README.md** - Project setup and overview
4. **postman_collection.json** - Postman collection untuk testing

---

## 🎉 Next Steps

1. **Test API** - Test semua endpoint di Postman
2. **Build Frontend** - Buat HTML/CSS/JS di folder `frontend/`
3. **Integrate** - Connect frontend dengan API menggunakan fetch
4. **Deploy** - Deploy ke server production (optional)

---

## Default Test Accounts

| Role      | Email                    | Password |
| --------- | ------------------------ | -------- |
| Admin     | admin@sipemau.ac.id      | password |
| Petugas   | budi@sipemau.ac.id       | password |
| Mahasiswa | john@student.unila.ac.id | password |

---

**All Done! Backend siap untuk frontend development! 🚀**
