@php($seenOperationIds = $previousOperationId ? [(int) $previousOperationId] : [])
@foreach($assignments as $assignment)
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
                <div class="flex justify-end gap-2">
                    @systemPermission('operation_assignments', 'update')
                    <a href="{{ route('operation-assignments.edit', $assignment) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-teal-500 hover:text-teal-700">Edit</a>
                    @endsystemPermission
                    @systemPermission('operation_assignments', 'delete')
                    <form method="POST" action="{{ route('operation-assignments.destroy', $assignment) }}" onsubmit="return confirm('Delete this assignment? This cannot be undone.')">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Delete</button></form>
                    @endsystemPermission
                </div>
            @endif
        </td>
    </tr>
@endforeach
