// Register Page Logic - Simple & Clean

function handleRegister(event) {
  event.preventDefault();

  const form = event.target;
  const name = form.name.value;
  const nim = form.nim.value;
  const email = form.email.value;
  const password = form.password.value;
  const confirmPassword = form.confirm_password.value;
  const submitBtn = form.querySelector('button[type="submit"]');

  // Validate password match
  if (password !== confirmPassword) {
    showError("Password dan konfirmasi password tidak cocok!");
    return;
  }

  // Show loading
  submitBtn.disabled = true;
  submitBtn.textContent = "Memproses...";
  hideMessages();

  // Prepare data
  const data = new URLSearchParams();
  data.append("name", name);
  data.append("nim", nim);
  data.append("email", email);
  data.append("password", password);
  data.append("confirm_password", confirmPassword);

  // Call API
  fetch(API_URL + "/register", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    credentials: "include",
    body: data,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showSuccess("Registrasi berhasil! Mengalihkan ke halaman login...");
        form.reset();
        setTimeout(() => {
          window.location.href = "login.html";
        }, 2000);
      } else {
        showError(data.message || "Registrasi gagal. Periksa data Anda.");
        submitBtn.disabled = false;
        submitBtn.textContent = "Daftar";
      }
    })
    .catch((error) => {
      console.error("Register error:", error);
      showError("Terjadi kesalahan. Pastikan backend sudah berjalan.");
      submitBtn.disabled = false;
      submitBtn.textContent = "Daftar";
    });
}

function showError(message) {
  const errorDiv = document.getElementById("registerError");
  errorDiv.textContent = message;
  errorDiv.classList.remove("hidden");
}

function showSuccess(message) {
  const successDiv = document.getElementById("registerSuccess");
  successDiv.textContent = message;
  successDiv.classList.remove("hidden");
}

function hideMessages() {
  document.getElementById("registerError").classList.add("hidden");
  document.getElementById("registerSuccess").classList.add("hidden");
}
