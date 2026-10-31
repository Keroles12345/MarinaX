@php
    $pickup = $operation->time_pickup ?: ($operation->trip_time ?: '—');
    $pax    = (int) $operation->adults + (int) $operation->children;
    $isCancelled = strtolower(trim((string) ($operation->status ?? ''))) === 'cancel';
@endphp
{{-- JS hooks: .operation-card, .card-topline, .remove-card, assigned-boat / assigned-vehicle (toggled from JS) --}}
<article
    class="operation-card rounded-lg border border-l-4 border-slate-200 border-l-slate-300 bg-white p-3 shadow-sm
           {{ $isCancelled ? 'cancelled-operation border-red-300 border-l-red-600 !bg-red-50 text-red-900 opacity-90' : 'cursor-grab' }}
           [&.sortable-ghost]:opacity-40 [&.sortable-chosen]:cursor-grabbing
           [&.assigned-boat]:border-l-sky-500 [&.assigned-boat]:bg-sky-50/70
           [&.assigned-vehicle]:border-l-amber-500 [&.assigned-vehicle]:bg-amber-50/70
           [&.assigned-boat.assigned-vehicle]:border-l-violet-500 [&.assigned-boat.assigned-vehicle]:bg-violet-50/70
           {{ isset($assignmentType) ? 'assigned-' . $assignmentType : '' }}"
    @if ($isCancelled) style="background-color:#fef2f2;border-color:#fecaca;border-left-color:#dc2626;color:#7f1d1d;pointer-events:none;cursor:not-allowed" @endif
    draggable="{{ $isCancelled ? 'false' : 'true' }}"
    data-operation-id="{{ $operation->id }}"
    data-region-id="{{ $operation->trip?->region_id }}"
    data-active="{{ $isCancelled ? '0' : '1' }}"
    data-adults="{{ (int) $operation->adults }}"
    data-children="{{ (int) $operation->children }}"
    data-search="{{ \Illuminate\Support\Str::lower('#' . $operation->booking_id . ' ' . ($operation->tripHotel?->hotel?->name ?? '') . ' ' . ($operation->time_pickup ?: $operation->trip_time ?: '')) }}">

    <div class="card-topline flex items-center justify-between gap-2">
        <strong class="whitespace-nowrap text-sm font-semibold text-slate-900">Booking #{{ $operation->booking_id }}</strong>
        @if ($isCancelled)
            <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-red-700">Cancelled</span>
        @endif
        @if (!$isCancelled && isset($showRemove) && $showRemove)
            <button type="button"
                class="remove-card -mr-1 grid h-6 w-6 place-items-center rounded-md text-lg leading-none text-slate-400 transition hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-300"
                aria-label="Remove booking #{{ $operation->booking_id }}">×</button>
        @endif
    </div>

    <div class="mt-2 space-y-1.5 text-xs text-slate-600">
        <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <span class="sr-only">Pickup:</span>{{ $operation->time_pickup ?: ($operation->trip_time ?: '?') }}
            </span>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ (int) $operation->adults + (int) $operation->children }} pax</span>
        </div>
        <div class="flex min-w-0 items-start gap-1.5">
            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 21V5a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v16"/><path d="M15 9h4a1 1 0 0 1 1 1v11"/><path d="M8 8h3M8 12h3M8 16h3M3 21h18"/></svg>
            <span class="sr-only">Hotel:</span><span class="min-w-0 break-words">{{ $operation->tripHotel?->hotel?->name ?? '?' }}</span>
        </div>
    </div>
</article>

