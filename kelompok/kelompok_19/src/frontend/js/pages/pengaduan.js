// Pengaduan List Page Logic

var currentPage = 1;
var currentStatus = "";

function loadComplaints(page, status) {
  currentPage = page || 1;
  currentStatus = status || "";

  var endpoint = "/mahasiswa/complaints?page=" + currentPage;
  if (currentStatus) {
    endpoint += "&status=" + currentStatus;
  }

  apiGet(endpoint)
    .then(function (response) {
      if (response.success && response.data) {
        displayComplaints(response.data.complaints);
        displayPagination(response.data.pagination);
        updateCountInfo(response.data.pagination);

        // Update user name
        getUserInfo().then(function (user) {
          if (user) {
            document.getElementById("userName").textContent = user.name;
          }
        });
      }
    })
    .catch(function (error) {
      console.error("Error loading complaints:", error);
      document.getElementById("complaintsList").innerHTML =
        '<div class="p-6 text-center text-red-500"><p>Gagal memuat data pengaduan</p></div>';
    });
}

function displayComplaints(complaints) {
  var container = document.getElementById("complaintsList");

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
    html += '  <div class="flex justify-between items-start">';
    html += '    <div class="flex-1">';
    html +=
      '      <h3 class="font-semibold text-gray-800 text-lg mb-2">' +
      complaint.title +
      "</h3>";
    html +=
      '      <p class="text-sm text-gray-600 mb-1">' +
      complaint.category_name +
      "</p>";
    html +=
      '      <p class="text-sm text-gray-500">' +
      formatDate(complaint.created_at) +
      "</p>";
    html += "    </div>";
    html += '    <div class="flex items-center space-x-4">';
    html +=
      '      <span class="px-3 py-1 rounded-full text-xs font-semibold ' +
      statusBg +
      " " +
      statusColor +
      '">' +
      complaint.status +
      "</span>";
    html +=
      '      <a href="detailPengaduan.html?id=' +
      complaint.id +
      '" class="text-brand-blue hover:text-blue-800 font-medium border border-brand-blue hover:border-blue-800 px-4 py-2 rounded-lg text-sm">Lihat Detail</a>';
    html += "    </div>";
    html += "  </div>";
    html += "</div>";
  }

  container.innerHTML = html;
}

function displayPagination(pagination) {
  var container = document.getElementById("pagination");

  if (pagination.total_pages <= 1) {
    container.innerHTML = "";
    return;
  }

  var html = "";

  // Previous button
  if (pagination.current_page > 1) {
    html +=
      '<button onclick="loadComplaints(' +
      (pagination.current_page - 1) +
      ', currentStatus)" class="text-gray-600 p-2 hover:text-gray-800">';
    html +=
      '  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">';
    html +=
      '    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />';
    html += "  </svg>";
    html += "</button>";
  }

  // Page numbers
  for (var i = 1; i <= pagination.total_pages; i++) {
    if (i === pagination.current_page) {
      html +=
        '<span class="px-3 py-1 rounded-lg bg-brand-blue text-white font-semibold">' +
        i +
        "</span>";
    } else {
      html +=
        '<button onclick="loadComplaints(' +
        i +
        ', currentStatus)" class="px-3 py-1 rounded-lg text-gray-600 hover:bg-gray-200">' +
        i +
        "</button>";
    }
  }

  // Next button
  if (pagination.current_page < pagination.total_pages) {
    html +=
      '<button onclick="loadComplaints(' +
      (pagination.current_page + 1) +
      ', currentStatus)" class="text-gray-600 p-2 hover:text-gray-800">';
    html +=
      '  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">';
    html +=
      '    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />';
    html += "  </svg>";
    html += "</button>";
  }

  container.innerHTML = html;
}

function updateCountInfo(pagination) {
  var start = (pagination.current_page - 1) * pagination.per_page + 1;
  var end = Math.min(
    pagination.current_page * pagination.per_page,
    pagination.total
  );
  var info =
    "Menampilkan " +
    start +
    "-" +
    end +
    " dari " +
    pagination.total +
    " pengaduan";
  document.getElementById("countInfo").textContent = info;
}

function filterChanged() {
  var status = document.getElementById("filterStatus").value;
  loadComplaints(1, status);
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
  loadComplaints(1, "");
});
