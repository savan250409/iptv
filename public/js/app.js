// ---------- Sidebar collapse / mobile drawer ----------
(function restoreSidebar() {
  try {
    if (localStorage.getItem('sb_collapsed') === '1') {
      document.getElementById('app')?.classList.add('collapsed');
    }
  } catch (e) {}
})();

function isMobile() {
  return window.matchMedia('(max-width:900px)').matches;
}

function toggleSidebar() {
  const app = document.getElementById('app');
  if (!app) return;
  if (isMobile()) {
    app.classList.toggle('sidebar-open');
  } else {
    app.classList.toggle('collapsed');
    try { localStorage.setItem('sb_collapsed', app.classList.contains('collapsed') ? '1' : '0'); } catch (e) {}
  }
}

function closeSidebar() {
  document.getElementById('app')?.classList.remove('sidebar-open');
}

// Close the mobile drawer after tapping a nav link
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.sidebar .nav-link').forEach(a => {
    a.addEventListener('click', () => { if (isMobile()) closeSidebar(); });
  });
});

// ---------- SweetAlert delete confirmation ----------
// Any form with class "js-confirm-delete" asks before submitting.
document.addEventListener('submit', function (e) {
  const form = e.target;
  if (!form.classList || !form.classList.contains('js-confirm-delete')) return;
  if (form.dataset.confirmed === '1') return; // already confirmed, let it through

  e.preventDefault();

  if (typeof Swal === 'undefined') { // fallback if the library failed to load
    if (confirm(form.dataset.confirmText || 'Delete this item?')) { form.dataset.confirmed = '1'; form.submit(); }
    return;
  }

  Swal.fire({
    title: form.dataset.confirmTitle || 'Are you sure?',
    text: form.dataset.confirmText || 'This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    focusCancel: true
  }).then((res) => {
    if (res.isConfirmed) { form.dataset.confirmed = '1'; form.submit(); }
  });
});

// ---------- Modal helpers ----------
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

document.addEventListener('click', (e) => {
  if (e.target.classList?.contains('modal')) e.target.classList.remove('open');
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal.open').forEach(m => m.classList.remove('open'));
    closeSidebar();
  }
});
