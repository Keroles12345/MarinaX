@extends('admin.layouts.app')
@section('title')إدارة الرحلات@endsection
@section('page_title')إدارة الرحلات@endsection
@section('content')

<style>
  .trip-summary{display:flex;align-items:center;gap:12px;min-width:260px}
  .trip-cover{width:96px;height:68px;flex:0 0 96px;overflow:hidden;border-radius:10px;background:#eef2f6}
  .trip-cover img{width:100%;height:100%;object-fit:cover}
  .trip-cover-empty{display:grid;height:100%;place-items:center;color:#8390a0;font-size:11px}
  .trip-details{max-width:680px;margin-top:8px;white-space:normal}
  .trip-details summary{color:#245ca6;font-size:12px;font-weight:700;cursor:pointer}
  .trip-details-content{display:grid;grid-template-columns:minmax(160px,.8fr) minmax(220px,1.2fr);gap:14px;margin-top:10px;padding:12px;border:1px solid #e7edf5;border-radius:12px;background:#fff;text-align:start}
  .trip-details-copy{display:grid;gap:8px;align-content:start}
  .trip-details-copy p{margin:0;line-height:1.7}
  .trip-details-gallery{display:flex;flex-wrap:wrap;gap:8px;align-content:start}
  .trip-details-gallery img{width:100px;height:76px;border-radius:8px;object-fit:cover}
  .trip-image-gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(145px,1fr));gap:12px}
  .trip-image-card{position:relative;display:grid;gap:8px;padding:9px;border:1px solid #e1e8f0;border-radius:13px;background:#fff;box-shadow:0 3px 12px #172b4d0a}
  .trip-image-card.is-primary{border-color:#d6ad55;box-shadow:0 0 0 2px #d6ad5526}
  .trip-image-card.is-removed{display:none}
  .trip-image-preview{width:100%;height:112px;border-radius:9px;background:#eef2f6;object-fit:cover}
  .trip-image-remove{position:absolute;top:14px;inset-inline-end:14px;display:grid;width:29px;height:29px;place-items:center;border:1px solid #ffffffaa;border-radius:50%;background:#b42318;color:#fff;font-size:19px;line-height:1;cursor:pointer;box-shadow:0 2px 6px #0002}
  .trip-image-primary{display:flex;align-items:center;gap:7px;color:#526174;font-size:12px;font-weight:700}
  .trip-image-primary input{accent-color:#bd8c29}
  .trip-image-card.is-primary .trip-image-primary{color:#8b6415}
  .trip-image-replace{display:grid;gap:4px;color:#66758b;font-size:11px}
  .trip-image-replace input{width:100%;font-size:11px}
  .trip-image-primary-badge{position:absolute;top:14px;inset-inline-start:14px;padding:4px 7px;border-radius:99px;background:#d6ad55;color:#fff;font-size:10px;font-weight:800}
  .trip-item-list{display:grid;gap:8px}
  .trip-item-row{display:flex;align-items:center;gap:8px}
  .trip-item-row input{min-width:0;flex:1}
  .trip-item-row button{flex:0 0 34px}
  .trip-faq-row{display:grid;grid-template-columns:1fr 1fr 36px;gap:8px;align-items:center;margin-bottom:8px}
  .trip-faq-row input{min-width:0}
  .trip-add-row{margin-top:8px}
  @media(max-width:700px){.trip-details-content{grid-template-columns:1fr}.trip-summary{min-width:220px}}
</style>

@if(session('status'))<div class="alert ok">{{ session('status') }}</div>@endif

<div class="panel">
  <div class="panel-head">
    <h3>الرحلات — {{ $trips->count() }}</h3>
    <a class="btn btn-dark" href="{{ route('accounting.hotels') }}">إدارة الفنادق</a>
    <a class="btn btn-dark" href="{{ route('accounting.trip-hotels') }}">ربط الرحلات بالفنادق</a>
    @if(request()->boolean('include_archived'))
      <a class="btn btn-dark" href="{{ route('accounting.trips') }}">عرض النشط فقط</a>
    @else
      <a class="btn btn-dark" href="{{ route('accounting.trips', ['include_archived' => 1]) }}">عرض المؤرشف</a>
    @endif
    @systemPermission('trips', 'create')
    <button class="btn btn-gold" type="button" onclick="openTripModal('create-trip-modal')">＋ إضافة رحلة</button>
    @endsystemPermission
  </div>
  <div class="panel-body hint">الرحلات خاصة بشركتك. اربط الرحلة بمنطقة نشطة لتظهر لموظفي المنطقة.</div>
  <div class="panel-head"><input id="trip-search" type="search" class="search-inline" placeholder="🔍 بحث بالاسم أو المنطقة..."></div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>اسم الرحلة</th><th>المنطقة</th><th>ميعاد الرحلة</th><th>الفنادق المرتبطة</th><th>الحالة</th><th>الإجراءات</th></tr></thead>
    <tbody>
      @forelse($trips as $trip)
        <tr data-trip-row data-search="{{ mb_strtolower($trip->name.' '.($trip->region?->name ?? '')) }}">
          <td>
            <div class="trip-summary">
              <div class="trip-cover">
                @if($trip->images->isNotEmpty())
                  <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($trip->images->first()->path) }}" alt="{{ $trip->name }}" loading="lazy">
                @else
                  <span class="trip-cover-empty">بدون صورة</span>
                @endif
              </div>
              <strong>{{ $trip->name }}</strong>
            </div>
            <details class="trip-details">
              <summary>عرض تفاصيل الرحلة</summary>
              <div class="trip-details-content">
                <div class="trip-details-copy">
                  <p><strong>الوصف:</strong> {{ $trip->description ?: 'لا يوجد وصف' }}</p>
                  <div>
                    <strong>يشمل</strong>
                    @forelse($trip->includes ?? [] as $item)<div>{{ $item }}</div>@empty<div>—</div>@endforelse
                  </div>
                  <div>
                    <strong>لا يشمل</strong>
                    @forelse($trip->excludes ?? [] as $item)<div>{{ $item }}</div>@empty<div>—</div>@endforelse
                  </div>
                  <div>
                    <strong>الأسئلة والإجابات</strong>
                    @forelse($trip->faqs ?? [] as $faq)
                      <p><strong>{{ $faq['question'] ?? '' }}</strong><br>{{ $faq['answer'] ?? '' }}</p>
                    @empty
                      <div>لا توجد أسئلة.</div>
                    @endforelse
                  </div>
                </div>
                <div class="trip-details-gallery" aria-label="صور الرحلة">
                  @forelse($trip->images as $image)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}" target="_blank" rel="noopener">
                      <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}" alt="{{ $trip->name }}" loading="lazy">
                    </a>
                  @empty
                    <span class="hint">لم تُضف صور لهذه الرحلة.</span>
                  @endforelse
                </div>
              </div>
            </details>
          </td><td>{{ $trip->region?->name ?? 'غير محددة' }}</td>
          <td>{{ $trip->time_trip ? substr((string) $trip->time_trip, 0, 5) : '—' }}</td>
          <td>{{ $trip->active_hotels_count }}</td><td>{{ $trip->status ? 'نشطة' : 'مؤرشفة' }}</td>
          <td><div class="j-actions">
            @systemPermission('trips', 'update')
            <button class="btn btn-dark btn-sm" type="button" data-edit-trip
              data-id="{{ $trip->id }}" data-url="{{ route('accounting.trips.update', $trip) }}"
              data-name="{{ $trip->name }}" data-region="{{ $trip->region_id }}"
              data-time="{{ $trip->time_trip ? substr((string) $trip->time_trip, 0, 5) : '' }}"
              data-description="{{ $trip->description }}" data-status="{{ $trip->status ? '1' : '0' }}"
              data-includes="{{ json_encode($trip->includes ?? []) }}"
              data-excludes="{{ json_encode($trip->excludes ?? []) }}"
              data-faqs="{{ json_encode($trip->faqs ?? []) }}"
              data-images="{{ json_encode($trip->images->map(fn ($image) => ['id' => $image->id, 'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($image->path)])->values()) }}">تعديل</button>
            @endsystemPermission
            @if($trip->status)
              @systemPermission('trips', 'delete')
              <form method="POST" action="{{ route('accounting.trips.destroy', $trip) }}" onsubmit="return confirm('سيتم أرشفة الرحلة مع الاحتفاظ بالحجوزات السابقة. متابعة؟')">
                @csrf @method('DELETE')<button class="btn btn-red btn-sm" type="submit">أرشفة</button>
              </form>
              @endsystemPermission
            @endif
          </div></td>
        </tr>
      @empty<tr><td colspan="6" class="empty-state">لا توجد رحلات {{ request()->boolean('include_archived') ? 'مسجلة' : 'نشطة' }}.</td></tr>@endforelse
    </tbody>
  </table></div>
</div>

<div class="modal-overlay" id="create-trip-modal" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="modal-head"><h3>إضافة رحلة</h3><button class="modal-close" type="button" onclick="closeTripModal('create-trip-modal')">×</button></div>
    <form method="POST" enctype="multipart/form-data" action="{{ route('accounting.trips.store') }}">
      @csrf<input type="hidden" name="_form_type" value="create-trip">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'create-trip')<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field"><label>اسم الرحلة *</label><input name="name" required maxlength="255" value="{{ old('_form_type') === 'create-trip' ? old('name') : '' }}">@if(old('_form_type') === 'create-trip') @error('name')<small class="hint" style="color:var(--red)">{{ $message }}</small>@enderror @endif</div>
        <div class="grid-2">
          <div class="field"><label>المنطقة</label><select name="region_id"><option value="">بدون منطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected(old('_form_type') === 'create-trip' && old('region_id') == $region->id)>{{ $region->name }}{{ $region->is_active ? '' : ' (غير نشطة)' }}</option>@endforeach</select></div>
          <div class="field"><label>ميعاد الرحلة</label><input type="time" name="time_trip" value="{{ old('_form_type') === 'create-trip' ? old('time_trip') : '' }}"></div>
        </div>
        <div class="field"><label>الوصف</label><textarea name="description" rows="3" maxlength="4000">{{ old('_form_type') === 'create-trip' ? old('description') : '' }}</textarea></div>
        <div class="grid-2">
          <div class="field">
            <label>يشمل</label>
            <div id="create-trip-includes" class="trip-item-list" data-trip-items data-kind="includes_items"></div>
            <button class="btn btn-dark btn-sm trip-add-row" type="button" data-add-trip-item="create-trip-includes">＋ إضافة عنصر</button>
          </div>
          <div class="field">
            <label>لا يشمل</label>
            <div id="create-trip-excludes" class="trip-item-list" data-trip-items data-kind="excludes_items"></div>
            <button class="btn btn-dark btn-sm trip-add-row" type="button" data-add-trip-item="create-trip-excludes">＋ إضافة عنصر</button>
          </div>
        </div>
        <div class="field">
          <label>الأسئلة والإجابات</label>
          <div id="create-trip-faqs" data-trip-items data-kind="faqs"></div>
          <button class="btn btn-dark btn-sm trip-add-row" type="button" data-add-trip-item="create-trip-faqs">＋ إضافة سؤال</button>
        </div>
        <div class="field">
          <label>صور الرحلة (حتى 10 صور، تُحفظ بصيغة WebP)</label>
          <input id="create-trip-image-input" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
          <div class="hint">اضغط × لإزالة صورة، واختر «الصورة الرئيسية» لتظهر أول صورة في الموقع (images[0]).</div>
          <div id="create-trip-images" class="trip-image-gallery"></div>
        </div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ الرحلة</button><button class="btn btn-dark" type="button" onclick="closeTripModal('create-trip-modal')">إلغاء</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="edit-trip-modal" role="dialog" aria-modal="true">
  <div class="modal">
    <div class="modal-head"><h3>تعديل الرحلة</h3><button class="modal-close" type="button" onclick="closeTripModal('edit-trip-modal')">×</button></div>
    <form id="edit-trip-form" method="POST" enctype="multipart/form-data">
      @csrf @method('PUT')<input type="hidden" name="_form_type" value="edit-trip"><input type="hidden" name="_resource_id" id="edit-trip-id">
      <input type="hidden" name="_include_archived" value="{{ request()->boolean('include_archived') ? 1 : 0 }}">
      <div class="modal-body">
        @if($errors->any() && old('_form_type') === 'edit-trip')<div class="alert err">{{ $errors->first() }}</div>@endif
        <div class="field"><label>اسم الرحلة *</label><input id="edit-trip-name" name="name" required maxlength="255"></div>
        <div class="grid-2">
          <div class="field"><label>المنطقة</label><select id="edit-trip-region" name="region_id"><option value="">بدون منطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}">{{ $region->name }}{{ $region->is_active ? '' : ' (غير نشطة)' }}</option>@endforeach</select></div>
          <div class="field"><label>ميعاد الرحلة</label><input id="edit-trip-time" type="time" name="time_trip"></div>
        </div>
        <div class="field"><label>الوصف</label><textarea id="edit-trip-description" name="description" rows="3" maxlength="4000"></textarea></div>
        <div class="grid-2">
          <div class="field">
            <label>يشمل</label>
            <div id="edit-trip-includes" class="trip-item-list" data-trip-items data-kind="includes_items"></div>
            <button class="btn btn-dark btn-sm trip-add-row" type="button" data-add-trip-item="edit-trip-includes">＋ إضافة عنصر</button>
          </div>
          <div class="field">
            <label>لا يشمل</label>
            <div id="edit-trip-excludes" class="trip-item-list" data-trip-items data-kind="excludes_items"></div>
            <button class="btn btn-dark btn-sm trip-add-row" type="button" data-add-trip-item="edit-trip-excludes">＋ إضافة عنصر</button>
          </div>
        </div>
        <div class="field">
          <label>الأسئلة والإجابات</label>
          <div id="edit-trip-faqs" data-trip-items data-kind="faqs"></div>
          <button class="btn btn-dark btn-sm trip-add-row" type="button" data-add-trip-item="edit-trip-faqs">＋ إضافة سؤال</button>
        </div>
        <div class="field">
          <label>صور الرحلة (حتى 10 صور)</label>
          <div class="hint">اضغط × لحذف الصورة، أو اختر ملفًا لاستبدالها، وحدد الصورة الرئيسية التي ستظهر أولًا في الموقع (images[0]).</div>
          <div id="edit-trip-images" class="trip-image-gallery"></div>
        </div>
        <div class="field">
          <label>إضافة صور جديدة (تُحفظ بصيغة WebP)</label>
          <input id="edit-trip-image-input" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
        </div>
        <div class="field"><label>الحالة</label><select id="edit-trip-status" name="status"><option value="1">نشطة</option><option value="0">مؤرشفة</option></select></div>
      </div>
      <div class="modal-foot"><button class="btn btn-gold" type="submit">حفظ التعديلات</button><button class="btn btn-dark" type="button" onclick="closeTripModal('edit-trip-modal')">إلغاء</button></div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
function openTripModal(id) { document.getElementById(id).classList.add('open'); }
function closeTripModal(id) { document.getElementById(id).classList.remove('open'); }

function getTripItems(container) {
  if (container.dataset.kind === 'faqs') {
    return [...container.querySelectorAll('.trip-faq-row')].map(row => ({
      question: row.querySelector('[data-faq-question]').value,
      answer: row.querySelector('[data-faq-answer]').value
    }));
  }
  return [...container.querySelectorAll('.trip-item-row input')].map(input => input.value);
}

function renderTripItems(container, values) {
  container.replaceChildren();
  values.forEach((value, index) => {
    const row = document.createElement('div');
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-red btn-sm';
    removeButton.textContent = '×';
    removeButton.setAttribute('aria-label', 'حذف العنصر');

    if (container.dataset.kind === 'faqs') {
      row.className = 'trip-faq-row';
      const question = document.createElement('input');
      question.type = 'text';
      question.name = `faq_items[${index}][question]`;
      question.placeholder = 'السؤال';
      question.maxLength = 1000;
      question.dataset.faqQuestion = '';
      question.value = value.question || '';
      const answer = document.createElement('input');
      answer.type = 'text';
      answer.name = `faq_items[${index}][answer]`;
      answer.placeholder = 'الإجابة';
      answer.maxLength = 2000;
      answer.dataset.faqAnswer = '';
      answer.value = value.answer || '';
      row.append(question, answer, removeButton);
    } else {
      row.className = 'trip-item-row';
      const input = document.createElement('input');
      input.type = 'text';
      input.name = `${container.dataset.kind}[]`;
      input.placeholder = container.dataset.kind === 'includes_items' ? 'عنصر يشمله السعر' : 'عنصر غير شامل';
      input.maxLength = 500;
      input.value = value || '';
      row.append(input, removeButton);
    }

    removeButton.addEventListener('click', () => {
      const items = getTripItems(container);
      items.splice(index, 1);
      renderTripItems(container, items);
    });
    container.append(row);
  });
}

document.querySelectorAll('[data-trip-items]').forEach(container => {
  renderTripItems(container, container.dataset.kind === 'faqs' ? [{}] : ['']);
});
document.querySelectorAll('[data-add-trip-item]').forEach(button => button.addEventListener('click', () => {
  const container = document.getElementById(button.dataset.addTripItem);
  const items = getTripItems(container);
  items.push(container.dataset.kind === 'faqs' ? {} : '');
  renderTripItems(container, items);
}));

function updatePrimaryImageCards(container) {
  container.querySelectorAll('.trip-image-card').forEach(card => {
    const isPrimary = card.querySelector('input[name="primary_image"]')?.checked ?? false;
    card.classList.toggle('is-primary', isPrimary);
    const badge = card.querySelector('.trip-image-primary-badge');
    if (badge) badge.hidden = !isPrimary;
  });
}

function renderTripImages(container, existingImages, fileInput, primaryValue) {
  container.replaceChildren();
  const selectedPrimary = primaryValue || (existingImages[0]?.key ?? null);
  const images = [...existingImages];
  Array.from(fileInput.files).forEach((file, index) => {
    images.push({
      key: `new:${index}`,
      isNew: true,
      url: URL.createObjectURL(file)
    });
  });
  const primaryKey = selectedPrimary || images[0]?.key;

  images.forEach(image => {
    const card = document.createElement('div');
    card.className = 'trip-image-card';
    card.dataset.key = image.key;

    const preview = document.createElement('img');
    preview.className = 'trip-image-preview';
    preview.src = image.url;
    preview.alt = 'صورة رحلة';

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'trip-image-remove';
    removeButton.textContent = '×';
    removeButton.setAttribute('aria-label', 'حذف الصورة');

    const badge = document.createElement('span');
    badge.className = 'trip-image-primary-badge';
    badge.textContent = 'رئيسية';

    const primaryLabel = document.createElement('label');
    primaryLabel.className = 'trip-image-primary';
    const primaryRadio = document.createElement('input');
    primaryRadio.type = 'radio';
    primaryRadio.name = 'primary_image';
    primaryRadio.value = image.key;
    primaryRadio.checked = image.key === primaryKey;
    primaryLabel.append(primaryRadio, document.createTextNode(' الصورة الرئيسية'));

    card.append(preview, removeButton, badge, primaryLabel);

    if (image.isNew) {
      card.dataset.uploadIndex = image.key.slice(4);
    } else {
      const removeInput = document.createElement('input');
      removeInput.type = 'checkbox';
      removeInput.name = 'remove_images[]';
      removeInput.value = String(image.id);
      removeInput.hidden = true;
      card.append(removeInput);

      const replaceLabel = document.createElement('label');
      replaceLabel.className = 'trip-image-replace';
      replaceLabel.append(document.createTextNode('استبدال الصورة'));
      const replaceInput = document.createElement('input');
      replaceInput.type = 'file';
      replaceInput.name = `replace_images[${image.id}]`;
      replaceInput.accept = 'image/jpeg,image/png,image/webp';
      replaceInput.addEventListener('change', () => {
        if (replaceInput.files[0]) preview.src = URL.createObjectURL(replaceInput.files[0]);
      });
      replaceLabel.append(replaceInput);
      card.append(replaceLabel);
    }

    removeButton.addEventListener('click', () => {
      if (image.isNew) {
        const transfer = new DataTransfer();
        Array.from(fileInput.files).forEach((file, index) => {
          if (index !== Number(image.key.slice(4))) transfer.items.add(file);
        });
        fileInput.files = transfer.files;
        renderTripImages(container, existingImages, fileInput);
        return;
      }

      const removeInput = card.querySelector('input[name="remove_images[]"]');
      removeInput.checked = true;
      card.classList.add('is-removed');
      const replaceInput = card.querySelector('input[type="file"]');
      if (replaceInput) replaceInput.value = '';
      if (primaryRadio.checked) {
        const nextRadio = container.querySelector('.trip-image-card:not(.is-removed) input[name="primary_image"]');
        if (nextRadio) nextRadio.checked = true;
      }
      updatePrimaryImageCards(container);
    });
    primaryRadio.addEventListener('change', () => updatePrimaryImageCards(container));
    container.append(card);
  });

  if (images.length === 0) {
    const empty = document.createElement('p');
    empty.className = 'hint';
    empty.textContent = 'لم تُضف صور لهذه الرحلة بعد.';
    container.append(empty);
  }
  updatePrimaryImageCards(container);
}

const createTripImageInput = document.getElementById('create-trip-image-input');
createTripImageInput.addEventListener('change', () => {
  renderTripImages(document.getElementById('create-trip-images'), [], createTripImageInput);
});

const editTripImageInput = document.getElementById('edit-trip-image-input');
let currentTripImages = [];
editTripImageInput.addEventListener('change', () => {
  const selectedPrimary = document.querySelector('#edit-trip-images input[name="primary_image"]:checked')?.value;
  renderTripImages(document.getElementById('edit-trip-images'), currentTripImages, editTripImageInput, selectedPrimary);
});

document.getElementById('trip-search').addEventListener('input', event => {
  const query = event.target.value.trim().toLowerCase();
  document.querySelectorAll('[data-trip-row]').forEach(row => {
    row.hidden = query !== '' && !row.dataset.search.includes(query);
  });
});

document.querySelectorAll('[data-edit-trip]').forEach(button => button.addEventListener('click', () => {
  const data = button.dataset;
  const form = document.getElementById('edit-trip-form');
  form.action = data.url;
  document.getElementById('edit-trip-id').value = data.id;
  document.getElementById('edit-trip-name').value = data.name;
  document.getElementById('edit-trip-region').value = data.region || '';
  document.getElementById('edit-trip-time').value = data.time || '';
  document.getElementById('edit-trip-description').value = data.description || '';
  renderTripItems(document.getElementById('edit-trip-includes'), JSON.parse(data.includes || '[]'));
  renderTripItems(document.getElementById('edit-trip-excludes'), JSON.parse(data.excludes || '[]'));
  const faqs = JSON.parse(data.faqs || '[]');
  renderTripItems(document.getElementById('edit-trip-faqs'), faqs);
  currentTripImages = JSON.parse(data.images || '[]').map(image => ({
    ...image,
    key: `existing:${image.id}`,
    isNew: false
  }));
  editTripImageInput.value = '';
  renderTripImages(document.getElementById('edit-trip-images'), currentTripImages, editTripImageInput);
  document.getElementById('edit-trip-status').value = data.status;
  openTripModal('edit-trip-modal');
}));

@if($errors->any() && old('_form_type') === 'create-trip')
renderTripItems(document.getElementById('create-trip-includes'), @json(old('includes_items', [''])));
renderTripItems(document.getElementById('create-trip-excludes'), @json(old('excludes_items', [''])));
renderTripItems(document.getElementById('create-trip-faqs'), @json(old('faq_items', [[]])));
openTripModal('create-trip-modal');
@elseif($errors->any() && old('_form_type') === 'edit-trip')
{
  const resourceId = @json(old('_resource_id'));
  const button = [...document.querySelectorAll('[data-edit-trip]')].find(item => item.dataset.id === String(resourceId));
  if (button) button.click();
  @foreach(['name', 'region_id', 'time_trip', 'description', 'status'] as $field)
  const field{{ $loop->index }} = document.querySelector(`#edit-trip-form [name="{{ $field }}"]`);
  if (field{{ $loop->index }}) field{{ $loop->index }}.value = @json(old($field));
  @endforeach
  renderTripItems(document.getElementById('edit-trip-includes'), @json(old('includes_items', [''])));
  renderTripItems(document.getElementById('edit-trip-excludes'), @json(old('excludes_items', [''])));
  renderTripItems(document.getElementById('edit-trip-faqs'), @json(old('faq_items', [[]])));
}
@endif
</script>
@endsection
