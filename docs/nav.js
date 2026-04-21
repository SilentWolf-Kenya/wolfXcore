/* wolfXcore Docs — shared navigation injector */
(function () {
  const pages = [
    {
      title: 'Getting Started',
      items: [
        { label: 'Overview',        icon: '⚡', href: 'index.html' },
        { label: 'Installation',    icon: '📦', href: 'installation.html' },
      ]
    },
    {
      title: 'Infrastructure',
      items: [
        { label: 'Wings Setup',     icon: '🦅', href: 'wings.html' },
      ]
    },
    {
      title: 'Features',
      items: [
        { label: 'Billing & M-Pesa', icon: '💳', href: 'billing.html' },
        { label: 'Super Admin Panel', icon: '🛡', href: 'super-admin.html' },
        { label: 'Plans',            icon: '📋', href: 'plans.html' },
        { label: 'Maintenance Mode', icon: '🔧', href: 'maintenance.html' },
      ]
    }
  ];

  const current = location.pathname.split('/').pop() || 'index.html';

  /* ── Header ───────────────────────────────────────────── */
  const header = document.getElementById('site-header');
  if (header) {
    header.innerHTML = `
      <button class="hamburger" id="ham-btn" aria-label="Toggle navigation">
        <span></span><span></span><span></span>
      </button>
      <a href="index.html" class="header-logo">⚡ WOLFXCORE</a>
      <span class="header-badge">DOCS</span>
      <div class="header-spacer"></div>
      <span class="header-version">v1.0</span>
    `;
  }

  /* ── Sidebar ──────────────────────────────────────────── */
  const sidebar = document.getElementById('site-sidebar');
  if (sidebar) {
    let html = '';
    pages.forEach(section => {
      html += `<div class="sidebar-section">
        <div class="sidebar-section-title">${section.title}</div>
        <ul class="sidebar-nav">`;
      section.items.forEach(item => {
        const active = item.href === current ? ' active' : '';
        html += `<li><a href="${item.href}" class="${active}">
          <span class="nav-icon">${item.icon}</span>${item.label}
        </a></li>`;
      });
      html += `</ul></div>`;
    });
    sidebar.innerHTML = html;
  }

  /* ── Mobile overlay ───────────────────────────────────── */
  const overlay = document.getElementById('sidebar-overlay');
  const sidebarEl = document.querySelector('.sidebar');
  const hamBtn = document.getElementById('ham-btn');

  function closeSidebar() {
    sidebarEl && sidebarEl.classList.remove('open');
    overlay && overlay.classList.remove('open');
  }

  if (hamBtn) {
    hamBtn.addEventListener('click', () => {
      sidebarEl && sidebarEl.classList.toggle('open');
      overlay && overlay.classList.toggle('open');
    });
  }
  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  /* Close sidebar when a nav link is clicked (mobile) */
  document.querySelectorAll('.sidebar-nav a').forEach(a => {
    a.addEventListener('click', closeSidebar);
  });
})();
