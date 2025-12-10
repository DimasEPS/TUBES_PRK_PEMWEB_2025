// Auth Utility Module
// Handle authentication and session management for Mahasiswa pages

// Check if user is authenticated
function checkAuth() {
  fetch(API_URL + "/mahasiswa/dashboard", {
    method: "GET",
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) {
        window.location.href = "../login.html";
      }
    })
    .catch((error) => {
      console.error("Auth check error:", error);
      window.location.href = "../login.html";
    });
}

// Logout user
function logout() {
  fetch(API_URL + "/logout", {
    method: "GET",
    credentials: "include",
  })
    .then(() => {
      window.location.href = "../login.html";
    })
    .catch(() => {
      window.location.href = "../login.html";
    });
}

// Get current user info from backend
function getUserInfo() {
  return fetch(API_URL + "/mahasiswa/dashboard", {
    method: "GET",
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.data && data.data.user) {
        return data.data.user;
      }
      return null;
    })
    .catch((error) => {
      console.error("Get user info error:", error);
      return null;
    });
}
