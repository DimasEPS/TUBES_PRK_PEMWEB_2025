// Detail Pengaduan Page Logic

function getComplaintId() {
  var params = new URLSearchParams(window.location.search);
  return params.get("id");
}

function loadComplaintDetail() {
  var id = getComplaintId();

  if (!id) {
    alert("ID pengaduan tidak ditemukan");
    window.location.href = "pengaduan.html";
    return;
  }

  apiGet("/mahasiswa/complaints/" + id)
    .then(function (response) {
      if (response.success && response.data) {
        displayComplaintDetail(response.data);

        // Update user name
        getUserInfo().then(function (user) {
          if (user) {
            document.getElementById("userName").textContent = user.name;
          }
        });
      } else {
        alert("Pengaduan tidak ditemukan");
        window.location.href = "pengaduan.html";
      }
    })
    .catch(function (error) {
      console.error("Error loading complaint detail:", error);
      alert("Gagal memuat detail pengaduan");
      window.location.href = "pengaduan.html";
    });
}

function displayComplaintDetail(data) {
  var complaint = data.complaint;

  // Basic info
  document.getElementById("complaintId").textContent =
    "ID Pengaduan: #" + complaint.id;
  document.getElementById("complaintTitle").textContent = complaint.title;
  document.getElementById("complaintCategory").textContent =
    complaint.category_name + " (" + complaint.unit_name + ")";
  document.getElementById("complaintDate").textContent = formatDateTime(
    complaint.created_at
  );
  document.getElementById("complaintUser").textContent =
    complaint.mahasiswa_name;
  document.getElementById("complaintNim").textContent = complaint.mahasiswa_nim;
  document.getElementById("complaintDescription").textContent =
    complaint.description;

  // Status badge
  var statusBadge = document.getElementById("statusBadge");
  var statusColor = getStatusColor(complaint.status);
  var statusBg = getStatusBg(complaint.status);
  statusBadge.textContent = complaint.status;
  statusBadge.className =
    "text-md font-bold px-4 py-2 rounded-lg " + statusBg + " " + statusColor;

  // Evidence
  if (complaint.evidence_path) {
    document.getElementById("evidenceSection").style.display = "block";
    var fileName = complaint.evidence_path.split("/").pop();
    document.getElementById("evidenceName").textContent = fileName;
    document.getElementById("evidenceLink").href =
      API_URL + "/uploads/" + complaint.evidence_path;
  }

  // Timeline with notes
  displayTimeline(complaint, data.notes || []);
}

function displayTimeline(complaint, notes) {
  var timeline = document.getElementById("timeline");
  var html = "";

  // Created status
  html += '<div class="mb-8 relative">';
  html +=
    '  <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gray-300 ml-3.5"></div>';
  html +=
    '  <div class="absolute -left-0.5 top-0 h-4 w-4 bg-yellow-500 rounded-full border-4 border-white"></div>';
  html +=
    '  <p class="text-sm text-gray-700 font-semibold inline-flex items-center">';
  html +=
    '    <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded mr-2">MENUNGGU</span>';
  html += "    " + formatDateTime(complaint.created_at);
  html += "  </p>";
  html +=
    '  <p class="text-gray-800 mt-2 ml-4">Pengaduan telah diterima dan menunggu verifikasi</p>';
  html += "</div>";

  // Process notes
  if (notes && notes.length > 0) {
    for (var i = 0; i < notes.length; i++) {
      var note = notes[i];
      var noteStatusBg =
        note.status === "SELESAI" ? "bg-green-500" : "bg-blue-600";
      var noteStatusText =
        note.status === "SELESAI"
          ? "text-green-800 bg-green-100"
          : "text-blue-800 bg-blue-100";

      var isLast = i === notes.length - 1 && complaint.status !== "SELESAI";
      var lineClass = isLast
        ? ""
        : "bg-" + (note.status === "SELESAI" ? "green" : "blue") + "-600";

      html += '<div class="mb-8 relative">';
      if (!isLast) {
        html +=
          '  <div class="absolute left-0 top-0 bottom-0 w-0.5 ' +
          lineClass +
          ' ml-3.5"></div>';
      }
      html +=
        '  <div class="absolute -left-0.5 top-0 h-4 w-4 ' +
        noteStatusBg +
        ' rounded-full border-4 border-white"></div>';
      html +=
        '  <p class="text-sm text-gray-700 font-semibold inline-flex items-center">';
      html +=
        '    <span class="' +
        noteStatusText +
        ' text-xs font-semibold px-2 py-1 rounded mr-2">' +
        note.status +
        "</span>";
      html += "    " + formatDateTime(note.created_at);
      html += "  </p>";
      html += '  <p class="text-gray-800 mt-2 ml-4">' + note.note + "</p>";
      html +=
        '  <p class="text-gray-500 text-sm mt-1 ml-4">Oleh: ' +
        note.petugas_name +
        "</p>";
      html += "</div>";
    }
  }

  // Status message
  if (complaint.status === "SELESAI") {
    html +=
      '<p class="text-green-700 font-medium mt-4 border-t pt-4 ml-4 bg-green-50 p-3 rounded">✓ Pengaduan Anda telah selesai ditangani</p>';
  } else if (complaint.status === "DIPROSES") {
    html +=
      '<p class="text-blue-700 mt-4 border-t pt-4 ml-4 bg-blue-50 p-3 rounded">Pengaduan Anda sedang dalam proses penanganan. Kami akan memberikan update segera.</p>';
  } else {
    html +=
      '<p class="text-yellow-700 mt-4 border-t pt-4 ml-4 bg-yellow-50 p-3 rounded">Pengaduan Anda sedang menunggu verifikasi dari petugas terkait.</p>';
  }

  timeline.innerHTML = html;
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

function formatDateTime(dateString) {
  var date = new Date(dateString);
  var options = {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  };
  return date.toLocaleDateString("id-ID", options);
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  checkAuth();
  loadComplaintDetail();
});
