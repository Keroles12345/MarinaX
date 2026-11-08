@extends('admin.layouts.app')
@section('title')الخدمات الإضافية@endsection
@section('page_title')الخدمات الإضافية@endsection
@section('content')

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif

<div class="panel">
  <div class="panel-head">
    <h3>الخدمات الإضافية — {{ $extras->count() }}</h3>
    @if(request()->boolean('include_archived'))
      <a class="btn btn-dark" href="{{ route('accounting.extras') }}">عرض النشط فقط</a>
    @else
      <a class="btn btn-dark" href="{{ route('accounting.extras', ['include_archived' => 1]) }}">عرض الموقوف</a>
    @endif
    @systemPermission('extras', 'create')
    <button class="btn btn-gold" type="button" onclick="openExtraModal('create-extra-modal')">＋ إضافة خدمة</button>
    @endsystemPermission
  </div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>اسم الخدمة</th><th>السعر</th><th>الضريبة</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
      <tbody>
        @forelse($extras as $extra)
          <tr>
            <td>{{ $extra->name }}</td><td class="num">{{ number_format((float) $extra->price, 2) }}</td>
            <td class="num">{{ number_format((float) $extra->rate_tax, 2) }}%</td><td>{{ $extra->status ? 'نشطة' : 'موقوفة' }}</td>
            <td><div class="j-actions">
              @systemPermission('extras', 'update')
              <button class="btn btn-dark btn-sm" type="button" data-edit-extra
                data-id="{{ $extra->id }}" data-url="{{ route('accounting.extras.update', $extra) }}"
                data-name="{{ $extra->name }}" data-price="{{ $extra->price }}"
                data-tax="{{ $extra->rate_tax }}" data-status="{{ $extra->status ? '1' : '0' }}">تعديل</button>
              @endsystemPermission
              @if($extra->status)
                @systemPermission('extras', 'delete')
                <form method="POST" action="{{ route('accounting.extras.destroy', $extra) }}" onsubmit="return confirm('سيتم إيقاف الخدمة مع الاحتفاظ بسجلاتها السابقة. هل تريد المتابعة؟')">
                  @csrf @method('DELETE')<button class="btn btn-red btn-sm" type="submit">إيقاف</button>
                </form>
                @endsystemPermission
              @endif
            </div></td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty-state">لا توجد خدمات إضافية {{ request()->boolean('include_archived') ? 'مسجلة' : 'نشطة' }}.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="create-extra-modal" role="dialog" aria-modal="true" aria-labelledby="create-extra-title">
  <div class="modal">
    <div class="modal-head"><h3 id="create-extra-title">إضافة خدمة إضافية</h3><button class="modal-close" type="button" aria-label="إغلاق" onclick="closeExtraModal('create-extra-modal')">×</button></div>
    <form method="POST" action="{{ route('accounting.extras.store') }}">
      @csrf<input type="hidden" name="_form_type" value="create">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'create')<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field"><label>اسم الخدمة *</label><input name="name" required maxlength="255" value="{{ old('_form_type') === 'create' ? old('name') : '' }}"></div>
        <div class="grid-2">
          <div class="field"><label>السعر *</label><input type="number" name="price" min="0" step="0.01" required value="{{ old('_form_type') === 'create' ? old('price', '0.00') : '0.00' }}"></div>
          <div class="field"><label>نسبة الضريبة (%) *</label><input type="number" name="rate_tax" min="0" max="100" step="0.01" required value="{{ old('_form_type') === 'create' ? old('rate_tax', '0.00') : '0.00' }}"></div>
        </div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ الخدمة</button><button class="btn btn-dark" type="button" onclick="closeExtraModal('create-extra-modal')">إلغاء</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="edit-extra-modal" role="dialog" aria-modal="true" aria-labelledby="edit-extra-title">
  <div class="modal">
    <div class="modal-head"><h3 id="edit-extra-title">تعديل الخدمة الإضافية</h3><button class="modal-close" type="button" aria-label="إغلاق" onclick="closeExtraModal('edit-extra-modal')">×</button></div>
    <form id="edit-extra-form" method="POST">
      @csrf @method('PUT')
      <input type="hidden" name="_form_type" value="edit"><input type="hidden" name="_resource_id" id="edit-extra-id">
      <input type="hidden" name="_include_archived" value="{{ request()->boolean('include_archived') ? 1 : 0 }}">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'edit')<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field"><label>اسم الخدمة *</label><input id="edit-extra-name" name="name" required maxlength="255"></div>
        <div class="grid-2">
          <div class="field"><label>السعر *</label><input id="edit-extra-price" type="number" name="price" min="0" step="0.01" required></div>
          <div class="field"><label>نسبة الضريبة (%) *</label><input id="edit-extra-tax" type="number" name="rate_tax" min="0" max="100" step="0.01" required></div>
        </div>
        <div class="field"><label>الحالة</label><select id="edit-extra-status" name="status"><option value="1">نشطة</option><option value="0">موقوفة</option></select></div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ التعديلات</button><button class="btn btn-dark" type="button" onclick="closeExtraModal('edit-extra-modal')">إلغاء</button></div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
function openExtraModal(id) { document.getElementById(id).classList.add('open'); }
function closeExtraModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('[data-edit-extra]').forEach(button => button.addEventListener('click', () => {
  const data = button.dataset;
  const form = document.getElementById('edit-extra-form');
  form.action = data.url;
  document.getElementById('edit-extra-id').value = data.id;
  document.getElementById('edit-extra-name').value = data.name;
  document.getElementById('edit-extra-price').value = data.price;
  document.getElementById('edit-extra-tax').value = data.tax;
  document.getElementById('edit-extra-status').value = data.status;
  openExtraModal('edit-extra-modal');
}));

@if($errors->any() && old('_form_type') === 'create')
openExtraModal('create-extra-modal');
@elseif($errors->any() && old('_form_type') === 'edit')
{
  const resourceId = @json(old('_resource_id'));
  const button = [...document.querySelectorAll('[data-edit-extra]')].find(item => item.dataset.id === String(resourceId));
  if (button) button.click();
  @foreach(['name', 'price', 'rate_tax', 'status'] as $field)
  const field{{ $loop->index }} = document.querySelector(`#edit-extra-form [name="{{ $field }}"]`);
  if (field{{ $loop->index }}) field{{ $loop->index }}.value = @json(old($field));
  @endforeach
}
@endif
</script>
@endsection
