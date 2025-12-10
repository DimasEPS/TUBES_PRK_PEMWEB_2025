// Dashboard Page Logic

function loadDashboard() {
  apiGet("/mahasiswa/dashboard")
    .then(function (response) {
      if (response.success && response.data) {
        var data = response.data;

        // Update user info
        document.getElementById("userName").textContent = data.user.name;

        // Update statistics
        document.getElementById("totalCount").textContent = data.stats.total;
        document.getElementById("prosesCount").textContent =
          data.stats.menunggu + data.stats.diproses;
        document.getElementById("selesaiCount").textContent =
          data.stats.selesai;

        // Load recent complaints
        loadRecentComplaints();
      }
    })
    .catch(function (error) {
      console.error("Error loading dashboard:", error);
      alert("Gagal memuat data dashboard");
    });
}

function loadRecentComplaints() {
  apiGet("/mahasiswa/complaints?page=1")
    .then(function (response) {
      if (response.success && response.data) {
        displayRecentComplaints(response.data.complaints.slice(0, 5));
      }
    })
    .catch(function (error) {
      console.error("Error loading recent complaints:", error);
    });
}

function displayRecentComplaints(complaints) {
  var container = document.getElementById("recentComplaints");

  if (complaints.length === 0) {
    container.innerHTML =
      '<div class="p-6 text-center text-gray-500"><p>Belum ada pengaduan. <a href="buatPengaduan.html" class="text-brand-blue hover:underline">Buat pengaduan pertama Anda</a></p></div>';
    return;
  }

  var html = "";
  for (var i = 0; i < complaints.length; i++) {
    var complaint = complaints[i];
    var statusColor = getStatusColor(complaint.status);
    var statusBg = getStatusBg(complaint.status);

    html += '<div class="p-6 hover:bg-gray-50 transition">';
    html += '  <div class="flex justify-between items-start mb-2">';
    html +=
      '    <h3 class="font-semibold text-gray-800 flex-1">' +
      complaint.title +
      "</h3>";
    html +=
      '    <span class="px-3 py-1 rounded-full text-xs font-semibold ' +
      statusBg +
      " " +
      statusColor +
      '">' +
      complaint.status +
      "</span>";
    html += "  </div>";
    html +=
      '  <p class="text-sm text-gray-600 mb-3">' +
      complaint.category_name +
      "</p>";
    html += '  <div class="flex justify-between items-center text-sm">';
    html +=
      '    <span class="text-gray-500">' +
      formatDate(complaint.created_at) +
      "</span>";
    html +=
      '    <a href="detailPengaduan.html?id=' +
      complaint.id +
      '" class="text-brand-blue hover:text-blue-800 font-medium">Lihat Detail →</a>';
    html += "  </div>";
    html += "</div>";
  }

  container.innerHTML = html;
}

function getStatusColor(status) {
  if (status === "MENUNGGU") return "text-yellow-800";
  if (status === "DIPROSES") return "text-blue-800";
  if (status === "SELESAI") return "text-green-800";
  return "text-gray-800";
}

function getStatusBg(status) {
  if (status === "MENUNGGU") return "bg-yellow-100";
  if (status === "DIPROSES") return "bg-blue-100";
  if (status === "SELESAI") return "bg-green-100";
  return "bg-gray-100";
}

function formatDate(dateString) {
  var date = new Date(dateString);
  var options = { year: "numeric", month: "long", day: "numeric" };
  return date.toLocaleDateString("id-ID", options);
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  checkAuth();
  loadDashboard();
});
