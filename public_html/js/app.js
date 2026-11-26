/* أدوات مشتركة */
'use strict';

const $ = (s, el = document) => el.querySelector(s);
const $$ = (s, el = document) => [...el.querySelectorAll(s)];

const sidebarToggle = $('#sidebar-toggle');
if (sidebarToggle) {
  const layout = $('.layout');
  const sidebarStateKey = 'admin-sidebar-state';
  const savedSidebarState = localStorage.getItem(sidebarStateKey);

  if (savedSidebarState === 'open') {
    layout.classList.remove('sidebar-collapsed');
  }

  const updateSidebarToggle = () => {
    const isCollapsed = layout.classList.contains('sidebar-collapsed');
    const label = window.dashboardTranslate
      ? window.dashboardTranslate(isCollapsed ? 'إظهار القائمة الجانبية' : 'إخفاء القائمة الجانبية')
      : (isCollapsed ? 'إظهار القائمة الجانبية' : 'إخفاء القائمة الجانبية');
    sidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
    sidebarToggle.setAttribute('aria-label', label);
    sidebarToggle.title = label;
  };

  updateSidebarToggle();

  sidebarToggle.addEventListener('click', () => {
    const isCollapsed = layout.classList.toggle('sidebar-collapsed');
    localStorage.setItem(sidebarStateKey, isCollapsed ? 'closed' : 'open');
    updateSidebarToggle();
  });
}

$$('.nav-category').forEach(category => {
  const title = $('summary', category)?.textContent.trim();
  if (!title) return;

  const stateKey = `admin-nav-category:${title}`;
  if (localStorage.getItem(stateKey) === 'open') {
    category.open = true;
  }

  category.addEventListener('toggle', () => {
    localStorage.setItem(stateKey, category.open ? 'open' : 'closed');
  });
});

function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));
}

function fmt(n) {
  return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtDate(iso) {
  if (!iso) return '';
  const [y, m, d] = String(iso).slice(0, 10).split('-');
  return d ? `${d}/${m}/${y}` : iso;
}

async function api(url, opts = {}) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const { headers = {}, ...requestOptions } = opts;
  const res = await fetch(url, {
    ...requestOptions,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      ...headers,
    },
    body: opts.body ? JSON.stringify(opts.body) : undefined,
  });
  const data = await res.json().catch(() => ({}));
  if (res.status === 401) { location.href = '/admin'; throw new Error(data.error || 'انتهت الجلسة'); }
  if (!res.ok) {
    const validationError = data.errors ? Object.values(data.errors).flat().find(Boolean) : null;
    throw new Error(data.error || validationError || data.message || 'حدث خطأ غير متوقع');
  }
  return data;
}

function toast(msg, type = 'ok') {
  if (window.dashboardTranslate) msg = window.dashboardTranslate(msg);
  let box = $('#toasts');
  if (!box) { box = document.createElement('div'); box.id = 'toasts'; document.body.appendChild(box); }
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span class="ticon">${type === 'ok' ? '✔' : '✖'}</span><span>${esc(msg)}</span>`;
  box.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .4s'; setTimeout(() => t.remove(), 400); }, 4200);
}

function openModal(id) { $('#' + id).classList.add('open'); }
function closeModal(id) { $('#' + id).classList.remove('open'); }

document.addEventListener('click', e => {
  const ov = e.target.closest('.modal-overlay');
  if (ov && e.target === ov) ov.classList.remove('open');
  if (e.target.closest('[data-close-modal]')) e.target.closest('.modal-overlay').classList.remove('open');
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') $$('.modal-overlay.open').forEach(m => m.classList.remove('open'));
});

async function confirmDlg(msg) {
  return window.confirm(window.dashboardTranslate ? window.dashboardTranslate(msg) : msg);
}
