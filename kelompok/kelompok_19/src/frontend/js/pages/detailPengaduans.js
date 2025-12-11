// frontend/js/pages/detailPengaduan.js
// Tailwind-based page logic. Menggunakan apiGet, apiPatch/apiPost, getCurrentUser, logout

(function () {
  // helper: ambil query param id
  function getQueryParam(name) {
    try {
      const u = new URL(window.location.href);
      return u.searchParams.get(name);
    } catch (e) {
      return null;
    }
  }

  const pengaduanId = getQueryParam('id');

  document.addEventListener('DOMContentLoaded', () => {
    bindLogout();
    loadUser();
    if (!pengaduanId) {
      alert('ID pengaduan tidak ditemukan. Buka halaman dengan ?id=<id_pengaduan>');
      return;
    }
    loadDetail(pengaduanId);
    loadHistory(pengaduanId);
    setupHandlers();
  });

  function bindLogout() {
    const btn = document.getElementById('btnLogout');
    if (!btn) return;
    btn.addEventListener('click', () => {
      if (confirm('Apakah Anda yakin ingin keluar?')) {
        if (typeof logout === 'function') logout();
        window.location.href = '../login.html';
      }
    });
  }

  function loadUser() {
    try {
      const user = (typeof getCurrentUser === 'function') ? getCurrentUser() : null;
      const el = document.getElementById('headerUserName');
      if (el) el.textContent = (user && user.nama) ? user.nama : 'Petugas';
    } catch (err) { console.warn(err); }
  }

  async function loadDetail(id) {
    try {
      const res = await (typeof apiGet === 'function' ? apiGet : (e => apiRequest(e, { method: 'GET' })) )(`/api/pengaduan/${id}`);
      if (!res) throw new Error('No response');
      if (!res.success) {
        alert(res.message || 'Gagal memuat detail pengaduan.');
        return;
      }
      const p = res.data;
      setText('subtitleId', `ID Pengaduan: #${p.id_pengaduan || p.id || '-'}`);
      setText('judul', p.judul_pengaduan || '-');
      setText('kategori', p.kategori_pengaduan || '-');
      setText('tanggal', formatDateTime(p.tanggal_pengaduan || p.created_at || p.tanggal));
      setText('deskripsi', p.deskripsi_pengaduan || '-');
      setText('pelaporNama', p.nama_mahasiswa || '-');
      setText('pelaporNpm', p.npm_pelapor || '-');
      setText('pelaporEmail', p.email_pelapor || p.email || '-');

      renderTopStatus(p.status_pengaduan);
      renderEvidence(p.bukti || p.bukti_pendukung || p.evidence);
    } catch (err) {
      console.error('loadDetail', err);
      alert('Terjadi kesalahan saat memuat detail.');
    }
  }

  async function loadHistory(id) {
    try {
      const res = await (typeof apiGet === 'function' ? apiGet : (e => apiRequest(e, { method: 'GET' })) )(`/api/pengaduan/${id}/history`);
      const container = document.getElementById('historyTimeline');
      if (!container) return;
      container.innerHTML = '';
      if (!res || !res.success) {
        container.innerHTML = `<div class="text-sm text-gray-500">Riwayat tidak tersedia.</div>`;
        return;
      }
      const rows = res.data || [];
      if (rows.length === 0) {
        container.innerHTML = `<div class="text-sm text-gray-500">Belum ada riwayat penanganan.</div>`;
        return;
      }
      rows.forEach(r => {
        const item = document.createElement('div');
        item.className = 'flex gap-4 items-start';

        const bullet = document.createElement('div');
        bullet.className = 'w-3 h-3 rounded-full mt-1';
        bullet.style.background = getBulletColor(r.status || r.status_pengaduan);

        const body = document.createElement('div');
        const head = document.createElement('div');
        head.className = 'flex items-center justify-between gap-4';
        const statusSpan = document.createElement('div');
        statusSpan.className = `status-badge status-${getStatusClass(r.status)}`
        statusSpan.textContent = (r.status || 'Menunggu').toUpperCase();

        const time = document.createElement('div');
        time.className = 'text-xs text-gray-500';
        time.textContent = formatDateTime(r.created_at || r.tanggal || r.waktu);

        head.appendChild(statusSpan);
        head.appendChild(time);

        const note = document.createElement('div');
        note.className = 'text-sm text-gray-700 mt-2';
        note.textContent = r.catatan || r.keterangan || 'Tidak ada catatan';

        const author = document.createElement('div');
        author.className = 'text-xs text-gray-500 mt-2';
        author.textContent = `Oleh: ${r.nama_petugas || r.penangan || r.sumber || 'Sistem'}`;

        body.appendChild(head);
        body.appendChild(note);
        body.appendChild(author);

        item.appendChild(bullet);
        item.appendChild(body);
        container.appendChild(item);
      });

    } catch (err) {
      console.error('loadHistory', err);
    }
  }

  function renderTopStatus(status) {
    const container = document.getElementById('topStatusContainer');
    if (!container) return;
    container.innerHTML = '';
    const el = document.createElement('div');
    el.className = `status-badge ${getStatusBadgeClass(status)}`;
    el.textContent = (status || 'Menunggu').toUpperCase();
    container.appendChild(el);
  }

  function renderEvidence(evidence) {
    const el = document.getElementById('evidenceContainer');
    if (!el) return;
    el.innerHTML = '';
    if (!evidence) {
      el.innerHTML = `<div class="text-sm text-gray-500">Tidak ada bukti pendukung.</div>`;
      return;
    }
    let arr = [];
    if (Array.isArray(evidence)) arr = evidence;
    else if (typeof evidence === 'string') arr = [evidence];
    else if (typeof evidence === 'object') arr = [evidence];

    arr.forEach(item => {
      const row = document.createElement('div');
      row.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-md border';

      const left = document.createElement('div');
      left.className = 'flex items-center gap-3';

      const thumb = document.createElement('div');
      thumb.className = 'w-12 h-12 bg-blue-800 text-white rounded-md flex items-center justify-center font-semibold';
      thumb.textContent = 'IMG';

      const meta = document.createElement('div');
      const name = document.createElement('div');
      name.className = 'text-sm font-medium text-gray-900';
      name.textContent = (typeof item === 'string') ? getFilenameFromUrl(item) : (item.filename || item.name || 'evidence');

      const size = document.createElement('div');
      size.className = 'text-xs text-gray-500';
      size.textContent = (typeof item === 'object' && item.size) ? humanFileSize(item.size) : '';

      meta.appendChild(name);
      meta.appendChild(size);

      left.appendChild(thumb);
      left.appendChild(meta);

      const link = document.createElement('a');
      link.href = (typeof item === 'string') ? item : (item.url || item.path || '#');
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.className = 'text-sm text-blue-600 hover:underline';
      link.textContent = 'Lihat';

      row.appendChild(left);
      row.appendChild(link);
      el.appendChild(row);
    });
  }

  function setupHandlers() {
    const btn = document.getElementById('saveStatusBtn');
    if (!btn) return;
    btn.addEventListener('click', async () => {
      const status = document.getElementById('statusSelect').value;
      const catatan = document.getElementById('catatanInput').value.trim();
      if (!status) return alert('Pilih status baru terlebih dahulu.');
      if (!catatan) return alert('Tambahkan catatan penanganan.');

      btn.disabled = true;
      btn.textContent = 'Menyimpan...';

      try {
        const payload = { status_pengaduan: status, catatan: catatan };
        let res;
        if (typeof apiPatch === 'function') {
          res = await apiPatch(`/api/pengaduan/${pengaduanId}/status`, payload);
        } else if (typeof apiPost === 'function') {
          res = await apiPost(`/api/pengaduan/${pengaduanId}/status`, payload);
        } else {
          res = await apiRequest(`/api/pengaduan/${pengaduanId}/status`, { method: 'PATCH', body: new URLSearchParams(payload).toString() });
        }

        if (res && res.success) {
          alert('Status berhasil diperbarui.');
          await loadDetail(pengaduanId);
          await loadHistory(pengaduanId);
          document.getElementById('catatanInput').value = '';
        } else {
          alert((res && res.message) ? res.message : 'Gagal memperbarui status.');
        }
      } catch (err) {
        console.error('update status', err);
        alert('Terjadi kesalahan saat menyimpan status.');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan Perubahan';
      }
    });
  }

  // utilities
  function setText(id, text) {
    const el = document.getElementById(id);
    if (!el) return;
    // deskripsi tetap aman karena textContent
    el.textContent = text;
  }

  function formatDateTime(s) {
    if (!s) return '-';
    const d = new Date(s);
    if (isNaN(d)) return s;
    return d.toLocaleString('id-ID', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
  }

  function getBulletColor(status) {
    if (!status) return '#f59e0b';
    const s = (status || '').toLowerCase();
    if (s === 'menunggu') return '#f59e0b';
    if (s === 'diproses') return '#1e40af';
    if (s === 'selesai') return '#10b981';
    return '#6b7280';
  }

  function getStatusBadgeClass(status) {
    const s = (status || '').toLowerCase();
    if (s === 'menunggu') return 'bg-yellow-100 text-yellow-800';
    if (s === 'diproses') return 'bg-blue-100 text-blue-800';
    if (s === 'selesai') return 'bg-green-100 text-green-800';
    return 'bg-gray-100 text-gray-800';
  }

  function getStatusClass(status) {
    if (!status) return 'menunggu';
    const s = (status || '').toLowerCase();
    if (s === 'menunggu') return 'menunggu';
    if (s === 'diproses') return 'diproses';
    if (s === 'selesai') return 'selesai';
    return 'menunggu';
  }

  function getFilenameFromUrl(url) {
    try {
      const u = new URL(url, window.location.origin);
      return decodeURIComponent(u.pathname.split('/').pop() || u.href);
    } catch (e) {
      return String(url).split('/').pop();
    }
  }

  function humanFileSize(bytes) {
    if (!bytes && bytes !== 0) return '';
    const thresh = 1024;
    if (Math.abs(bytes) < thresh) return bytes + ' B';
    const units = ['KB', 'MB', 'GB', 'TB'];
    let u = -1;
    do {
      bytes /= thresh;
      ++u;
    } while (Math.abs(bytes) >= thresh && u < units.length - 1);
    return bytes.toFixed(1) + ' ' + units[u];
  }

})();