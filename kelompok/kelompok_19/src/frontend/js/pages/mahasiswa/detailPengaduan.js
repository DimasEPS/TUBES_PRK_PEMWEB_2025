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
      API_URL + "/uploads/" + fileName;
  }

  // Timeline with notes
  displayTimeline(complaint, data.notes || []);
}

function displayTimeline(complaint, notes) {
  var timeline = document.getElementById("timeline");
  var html = "";

  // Created status - MENUNGGU
  html += '<div class="mb-6 flex items-start gap-4">';
  html +=
    '  <div class="flex-shrink-0 h-4 w-4 bg-yellow-500 rounded-full border-4 border-white mt-0.5"></div>';
  html += '  <div class="flex-1">';
  html +=
    '    <p class="text-sm text-gray-700 font-semibold inline-flex items-center">';
  html +=
    '      <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded mr-2">MENUNGGU</span>';
  html += "      " + formatDateTime(complaint.created_at);
  html += "    </p>";
  html +=
    '    <p class="text-gray-800 mt-2">Pengaduan telah diterima dan menunggu verifikasi</p>';
  html += "  </div>";
  html += "</div>";

  // Process notes - show status based on complaint's current status
  if (notes && notes.length > 0) {
    // Sort notes by created_at descending (newest first)
    var sortedNotes = notes.sort(function(a, b) {
      return new Date(b.created_at) - new Date(a.created_at);
    });

    for (var i = 0; i < sortedNotes.length; i++) {
      var note = sortedNotes[i];
      // Use complaint status for display, especially for the latest note
      var status = (i === 0) ? complaint.status : "DIPROSES";

      var dotColor, badgeBg, badgeText;
      if (status === "SELESAI") {
        dotColor = "bg-green-500";
        badgeBg = "bg-green-100";
        badgeText = "text-green-800";
      } else {
        dotColor = "bg-blue-600";
        badgeBg = "bg-blue-100";
        badgeText = "text-blue-800";
      }

      html += '<div class="mb-6 flex items-start gap-4">';
      html +=
        '  <div class="flex-shrink-0 h-4 w-4 ' +
        dotColor +
        ' rounded-full border-4 border-white mt-0.5"></div>';
      html += '  <div class="flex-1">';
      html +=
        '    <p class="text-sm text-gray-700 font-semibold inline-flex items-center">';
      html +=
        '      <span class="' +
        badgeBg +
        " " +
        badgeText +
        ' text-xs font-semibold px-2 py-1 rounded mr-2">' +
        status +
        "</span>";
      html += "      " + formatDateTime(note.created_at);
      html += "    </p>";
      html += '    <p class="text-gray-800 mt-2">' + escapeHtml(note.note) + "</p>";
      html +=
        '    <p class="text-gray-500 text-sm mt-1">Oleh: ' +
        escapeHtml(note.petugas_name) +
        "</p>";
      html += "  </div>";
      html += "</div>";
    }
  }

  // Status message based on current complaint status
  if (complaint.status === "SELESAI") {
    html +=
      '<p class="text-green-700 font-medium mt-4 border-t pt-4 bg-green-50 p-3 rounded">✓ Pengaduan Anda telah selesai ditangani</p>';
  } else if (complaint.status === "DIPROSES") {
    html +=
      '<p class="text-blue-700 mt-4 border-t pt-4 bg-blue-50 p-3 rounded">Pengaduan Anda sedang dalam proses penanganan. Kami akan memberikan update segera.</p>';
  } else {
    html +=
      '<p class="text-yellow-700 mt-4 border-t pt-4 bg-yellow-50 p-3 rounded">Pengaduan Anda sedang menunggu verifikasi dari petugas terkait.</p>';
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

function escapeHtml(text) {
  var div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  checkAuth();
  loadComplaintDetail();
});
