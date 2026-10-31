@extends('admin.layouts.app')
@section('title')شجرة الحسابات@endsection
@section('page_title')شجرة الحسابات@endsection
@section('content')

<div class="panel">
  <div class="panel-head">
    <h3>🌳 هيكل الحسابات</h3>
    <div class="toolbar">
      <button class="btn btn-dark btn-sm" onclick="expandAll()">⬇️ تفريع الكل</button>
      <button class="btn btn-dark btn-sm" onclick="collapseAll()">⬆️ طي الكل</button>
      <a class="btn btn-gold btn-sm" href="/accounts">📚 العرض المسطح</a>
      @if($user->role === 'admin')
      <button class="btn btn-red btn-sm" onclick="wipeTree()">🗑️ مسح الشجرة كاملة</button>
      @endif
    </div>
  </div>
  <div class="tree-container" id="tree"></div>
</div>

@endsection

@section('scripts')
<script>
let TREE = [];
@if(in_array($user->role, ['admin', 'accountant', 'account_user'], true))
const CAN_MANAGE_ACCOUNTS = true;
@else
const CAN_MANAGE_ACCOUNTS = false;
@endif

async function loadTree() {
  try {
    const d = await api('/api/accounts/tree');
    if (!Array.isArray(d.tree)) throw new Error('تعذر تحميل بيانات شجرة الحسابات');
    TREE = d.tree;
    renderTree(TREE);
  } catch (e) { toast(e.message, 'err'); }
}

function renderTree(tree) {
  const c = $('#tree');
  if (tree.length === 0) {
    c.innerHTML = '<div class="empty-state">لا توجد حسابات مسجلة في شركتك حتى الآن.</div>';
    return;
  }
  c.innerHTML = tree.map(typeNode => `
    <div class="tree-node tree-type">
      <div class="tree-row" onclick="toggleNext(this)">
        <span class="tw-arrow">▾</span>
        <span class="tw-icon">📁</span>
        <b>${esc(treeText(typeNode.type))}</b>
        <span class="tw-count">${formatAccountCount(countChildren(typeNode))}</span>
      </div>
      <div class="tree-children">
        ${typeNode.children.map(g => `
          <div class="tree-node tree-group">
            <div class="tree-row" onclick="toggleNext(this)">
              <span class="tw-arrow">▾</span>
              <span class="tw-icon">🗂️</span>
              <b>${esc(treeText(g.label))}</b>
              <span class="tw-count">${formatAccountCount(g.children.reduce((count, account) => count + 1 + (account.tree_children?.length || 0), 0))}</span>
            </div>
            <div class="tree-children">
              ${g.children.map(renderAccountNode).join('')}
            </div>
          </div>`).join('')}
      </div>
    </div>`).join('');
}

function renderAccountNode(account) {
  const children = account.tree_children || [];
  const isProtectedAccount = account.is_system || ['4103', '4104'].includes(String(account.acc_no));
  const editData = JSON.stringify({
    id: account.id,
    acc_no: account.acc_no,
    name: account.name,
    type: account.type,
    opening_balance: account.opening_balance,
    budget: account.budget,
    is_system: account.is_system
  });
  const openingBalance = Number(account.opening_balance || 0);
  const openingLabel = openingBalance < 0 ? 'افتتاحي دائن' : 'افتتاحي مدين';
  const currentBalance = Number(account.current_balance || 0);
  const currentSide = Math.abs(currentBalance) < 0.005 ? 'متوازن' : currentBalance > 0 ? 'مدين' : 'دائن';

  return `
    <div class="tree-node tree-account">
      <div class="tree-row" ${children.length ? 'onclick="toggleNext(this)"' : ''}>
        <span class="tw-arrow ${children.length ? '' : 'tw-leaf'}">${children.length ? '▾' : '•'}</span>
        <span class="tw-icon">${children.length ? '📁' : '📄'}</span>
        <span class="tw-no">${esc(account.acc_no)}</span> — ${esc(account.name)}
        <span class="tw-budget">${openingLabel}: ${fmt(Math.abs(openingBalance))}</span>
        <span class="tw-budget">الرصيد الحالي ${currentSide}: ${fmt(Math.abs(currentBalance))}</span>
        ${account.budget > 0 ? `<span class="tw-budget">ميزانية: ${fmt(account.budget)}</span>` : ''}
        ${CAN_MANAGE_ACCOUNTS ? `
        <span class="tree-act">
          <button class="btn btn-sm btn-dark act-btn" title="تعديل تفاصيل الشركة" onclick='event.stopPropagation(); editAcc(${editData})'>✏️</button>
          ${isProtectedAccount ? '<span class="tree-act hint">حساب أساسي مشترك</span>' : `<button class="btn btn-sm btn-red act-btn" title="حذف" onclick="event.stopPropagation(); delTreeAcc(${account.id}, '${esc(account.acc_no)}')">🗑️</button>`}
        </span>
        ` : ''}
      </div>
      ${children.length ? `<div class="tree-children">${children.map(renderAccountNode).join('')}</div>` : ''}
    </div>`;
}

function treeText(value) {
  return window.dashboardTranslate ? window.dashboardTranslate(value) : value;
}

function formatAccountCount(count) {
  return `${count} ${treeText(count === 1 ? 'حساب' : 'حسابات')}`;
}

function countChildren(node) {
  return node.children.reduce((count, child) => {
    const nodes = child.children || [];
    return count + nodes.reduce((total, account) => total + 1 + (account.tree_children?.length || 0), 0);
  }, 0);
}

function toggleNext(row) {
  const kids = row.nextElementSibling;
  if (!kids) return;
  const isHidden = kids.style.display === 'none';
  kids.style.display = isHidden ? '' : 'none';
  const arrow = row.querySelector('.tw-arrow');
  if (arrow) {
    arrow.textContent = isHidden ? '▾' : '▸';
    arrow.classList.toggle('tw-collapsed', !isHidden);
  }
}

function expandAll() {
  document.querySelectorAll('.tree-children').forEach(c => c.style.display = '');
  document.querySelectorAll('.tw-arrow:not(.tw-leaf)').forEach(a => {
    a.textContent = '▾';
    a.classList.remove('tw-collapsed');
  });
}

function collapseAll() {
  document.querySelectorAll('.tree .tree-type > .tree-children').forEach(c => { c.style.display = ''; });
  document.querySelectorAll('.tree-children .tree-children').forEach(c => c.style.display = 'none');
  document.querySelectorAll('.tw-arrow:not(.tw-leaf)').forEach(a => {
    a.textContent = '▸';
    a.classList.add('tw-collapsed');
  });
  document.querySelectorAll('.tree-type > .tree-row .tw-arrow').forEach(a => {
    a.textContent = '▾';
    a.classList.remove('tw-collapsed');
  });
}

function editAcc(a) {
  window.location.href = '/accounts?q=' + encodeURIComponent(a.acc_no || '') + '&edit=1';
}

async function delTreeAcc(id, no) {
  if (!await confirmDlg(`${treeText('حذف الحساب')} ${no}؟ ${treeText('لا يمكن الحذف إذا كان له حركات.')}`)) return;
  try { await api('/api/accounts/' + id, { method: 'DELETE' }); toast('تم حذف الحساب'); loadTree(); }
  catch (e) { toast(e.message, 'err'); }
}

async function wipeTree() {
  if (!await confirmDlg('تحذير نهائي ⚠️ سيتم حذف الحسابات المخصصة وكل القيود، مع الحفاظ على الحسابات الأساسية المشتركة. متأكد؟')) return;
  if (!await confirmDlg('تأكيد أخير: نفّذ المسح الآن؟ لا يمكن التراجع عن القيود إلا بالنسخ الاحتياطي')) return;
  try {
    const d = await api('/api/accounts/wipe', { method: 'POST' });
    toast(`${treeText('تم مسح')} ${d.deleted_accounts} ${treeText('حسابات مخصصة')} ${treeText('و')} ${d.deleted_entries} ${treeText('قيود')} ${treeText('مع الحفاظ على الحسابات الأساسية')}`);
    loadTree();
  } catch (e) { toast(e.message, 'err'); }
}

loadTree();
</script>
@endsection