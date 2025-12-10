// Buat Pengaduan Page Logic

function loadCategories() {
  apiGet("/mahasiswa/complaints/categories")
    .then((response) => {
      if (response.success && response.data) {
        const select = document.getElementById("category_id");
        let html = '<option value="" disabled selected>Pilih Kategori</option>';

        response.data.forEach((category) => {
          html += `<option value="${category.id}">${category.name} (${category.unit_name})</option>`;
        });

        select.innerHTML = html;
      }
    })
    .catch((error) => {
      console.error("Error loading categories:", error);
      alert("Gagal memuat kategori");
    });
}

function fileSelected(input) {
  const fileName = document.getElementById("fileName");
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const maxSize = 5 * 1024 * 1024; // 5MB

    if (file.size > maxSize) {
      alert("Ukuran file terlalu besar. Maksimal 5MB");
      input.value = "";
      fileName.textContent = "";
      return;
    }

    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
    fileName.textContent = `✓ ${file.name} (${sizeMB} MB)`;
  } else {
    fileName.textContent = "";
  }
}

function submitComplaint(event) {
  event.preventDefault();

  const submitBtn = document.getElementById("submitBtn");
  submitBtn.disabled = true;
  submitBtn.textContent = "Mengirim...";

  const form = document.getElementById("complaintForm");
  const formData = new FormData(form);

  apiPostFile("/mahasiswa/complaints", formData)
    .then((response) => {
      if (response.success) {
        alert("Pengaduan berhasil dikirim!");
        window.location.href = "pengaduan.html";
      } else {
        alert(response.message || "Gagal mengirim pengaduan");
        submitBtn.disabled = false;
        submitBtn.textContent = "Kirim Pengaduan";
      }
    })
    .catch((error) => {
      console.error("Error submitting complaint:", error);
      alert("Terjadi kesalahan saat mengirim pengaduan");
      submitBtn.disabled = false;
      submitBtn.textContent = "Kirim Pengaduan";
    });
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  checkAuth();
  loadCategories();

  getUserInfo().then((user) => {
    if (user) {
      document.getElementById("userName").textContent = user.name;
    }
  });
});
