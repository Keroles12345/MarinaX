@extends('staff.main')

@section('body')
<div class="py-6">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-semibold uppercase tracking-wider text-teal-700">Operations</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Resource assignments</h1><p class="mt-1 text-sm text-slate-500">Manage boats, vehicles and their assigned drivers.</p></div>
        <a href="{{ route('operation-assignments.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">＋ New assignment</a>
    </div>

    @if(session('status'))<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
<form method="GET"
      action="{{ route('operation-assignments.index') }}"
      class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">

    <label class="grid gap-1 text-xs font-semibold text-slate-600">
        Booking ID
        <input
            name="search"
            value="{{ request('search') }}"
            placeholder="Search booking…"
            class="rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600"
        >
    </label>

    <label class="grid gap-1 text-xs font-semibold text-slate-600">
        Assignment date
        <input
            type="date"
            name="date"
            value="{{ request('date') }}"
            class="rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600"
        >
    </label>

    <label class="grid gap-1 text-xs font-semibold text-slate-600">
        Resource type
        <select
            name="type"
            class="rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600"
        >
            <option value="">All resources</option>
            <option value="boat" @selected(request('type') === 'boat')>
                Boat
            </option>
            <option value="vehicle" @selected(request('type') === 'vehicle')>
                Vehicle
            </option>
        </select>
    </label>

    <div class="flex items-end gap-2">

        {{-- Filter --}}
        <button
            type="submit"
            class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900"
        >
            Filter
        </button>

        {{-- Print --}}
       <button
    type="submit"
    form="print-form"
    class="rounded-lg bg-green-800 px-4 py-2 text-sm font-semibold text-white hover:bg-green-900"
    onclick="copyFiltersToPrintForm()"
>
    Print
</button>

        {{-- Clear --}}
        <a
            href="{{ route('operation-assignments.index') }}"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50"
        >
            Clear
        </a>

    </div>
</form>

<form id="accounting-form" method="POST" action="{{ route('operation-assignments.post-to-accounting') }}">
    @csrf
</form>
<div class="mb-5 flex flex-wrap items-end justify-between gap-4 rounded-2xl border border-teal-200 bg-teal-50 p-4">
    <div>
        <p class="font-semibold text-teal-950">ترحيل العمليات المحددة إلى الحسابات</p>
        <p class="mt-1 text-xs text-teal-800">يُرحّل إيراد كل مركب أو سيارة إلى حساب فرعي تحت إيرادات العمليات الداخلية أو الخارجية حسب الملكية؛ والسيارات تُعامل كتشغيل داخلي.</p>
    </div>
    <div class="flex flex-wrap items-end gap-3">
        <label class="grid gap-1 text-xs font-semibold text-slate-700">
            المنطقة المحاسبية
            <select name="region" form="accounting-form" required class="min-w-48 rounded-lg border-slate-300 text-sm focus:border-teal-600 focus:ring-teal-600">
                @foreach($regions as $region)<option value="{{ $region }}" @selected($region === old('region', $regions->first()))>{{ $region }}</option>@endforeach
            </select>
        </label>
        <button type="submit" form="accounting-form" class="rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50" @disabled($regions->isEmpty())>
            ترحيل المحدد إلى الحسابات
        </button>
    </div>
    @if($regions->isEmpty())
        <p class="w-full text-xs text-rose-700">أضف منطقة مفعّلة من إعدادات النظام المحاسبي قبل الترحيل.</p>
    @endif
</div>


<form
    id="print-form"
    method="POST"
    action="{{ route('print.save') }}"
>
    @csrf

    <input type="hidden" name="search" id="print-search">
    <input type="hidden" name="date" id="print-date">
    <input type="hidden" name="type" id="print-type">
</form>

<script>
    function copyFiltersToPrintForm() {
        document.getElementById('print-search').value =
            document.querySelector('input[name="search"]').value;

        document.getElementById('print-date').value =
            document.querySelector('input[name="date"]').value;

        document.getElementById('print-type').value =
            document.querySelector('select[name="type"]').value;
    }
</script>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3"><input id="check-all-operations" type="checkbox" aria-label="تحديد كل العمليات القابلة للترحيل"></th><th class="px-5 py-3">Booking / trip</th><th class="px-5 py-3">Assigned resource</th><th class="px-5 py-3">Driver</th><th class="px-5 py-3">Assignment date</th><th class="px-5 py-3 text-right">Operation amount</th><th class="px-5 py-3">Accounting</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @php($seenOperationIds = [])
            @forelse($assignments as $assignment)
                @php($isFirstAssignment = !in_array((int) $assignment->operation_id, $seenOperationIds, true))
                @php($isExternalOperation = in_array((int) $assignment->operation_id, $externalOperationIds, true))
                @if($isFirstAssignment) @php($seenOperationIds[] = (int) $assignment->operation_id) @endif
                <tr class="hover:bg-slate-50/70">
                    <td class="px-5 py-4">
                        @if($isFirstAssignment && !$assignment->accounting_entry_id && $assignment->operation_status === 'complete' && (float) $assignment->totalamount > 0)
                            <input class="operation-checkbox rounded border-slate-300 text-teal-700 focus:ring-teal-600" type="checkbox" form="accounting-form" name="operation_ids[]" value="{{ $assignment->operation_id }}" aria-label="تحديد العملية {{ $assignment->booking_id }}">
                        @elseif(!$isFirstAssignment)
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-5 py-4"><span class="font-semibold text-slate-900">{{ $assignment->booking_id }}</span><span class="mt-1 block text-xs text-slate-500">Trip {{ \Illuminate\Support\Carbon::parse($assignment->trip_date)->format('M j, Y') }} · {{ $assignment->time_pickup ?: $assignment->trip_time }}</span></td>
                    <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $assignment->type === 'boat' ? 'bg-sky-50 text-sky-700' : 'bg-violet-50 text-violet-700' }}">{{ ucfirst($assignment->type) }}</span><span class="ml-2 font-medium text-slate-800">{{ $resourceNames[$assignment->type . ':' . $assignment->resource_id] ?? 'Resource #' . $assignment->resource_id }}</span>
                        @if($isExternalOperation)<span class="mt-2 block w-fit rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">مركب خارجي</span>@endif
                    </td>
                    <td class="px-5 py-4 text-slate-700">{{ $driverNames[$assignment->type][$assignment->driver_id] ?? 'Not assigned' }}</td>
                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $assignment->assign_date->format('M j, Y') }}</td>
                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-800">{{ number_format((float) $assignment->totalamount, 2) }} <span class="text-xs font-normal text-slate-500">EGP</span></td>
                    <td class="px-5 py-4">
                        @if($assignment->accounting_entry_id)
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">مرحّلة</span>
                            <span class="mt-1 block text-xs text-slate-500">قيد #{{ $assignment->accounting_entry_id }}</span>
                        @elseif(!$isFirstAssignment)
                            <span class="text-xs text-slate-500">عملية واحدة — مورد متعدد</span>
                        @elseif((float) $assignment->totalamount <= 0)
                            <span class="text-xs text-rose-700">لا يوجد مبلغ للترحيل</span>
                        @elseif($assignment->operation_status !== 'complete')
                            <span class="text-xs text-slate-500">العملية غير مكتملة</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">بانتظار المراجعة</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-5 py-4">
                        @if($assignment->accounting_entry_id)
                            <span class="text-xs text-slate-500">مقفلة بعد الترحيل</span>
                        @else
                            <div class="flex justify-end gap-2"><a href="{{ route('operation-assignments.edit', $assignment) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-teal-500 hover:text-teal-700">Edit</a><form method="POST" action="{{ route('operation-assignments.destroy', $assignment) }}" onsubmit="return confirm('Delete this assignment? This cannot be undone.')">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Delete</button></form></div>
                        @endif
                    </td>
                </tr>
            @empty<tr><td colspan="8" class="px-5 py-14 text-center"><div class="text-3xl">▤</div><p class="mt-3 font-semibold text-slate-800">No assignments found</p><p class="mt-1 text-sm text-slate-500">Create an assignment or adjust your filters.</p></td></tr>@endforelse
            </tbody>
        </table></div>
        @if($assignments->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $assignments->links() }}</div>@endif
    </div>
</div>
<script>
    document.getElementById('check-all-operations')?.addEventListener('change', event => {
        document.querySelectorAll('.operation-checkbox').forEach(checkbox => {
            checkbox.checked = event.target.checked;
        });
    });
</script>
@endsection
