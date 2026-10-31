@php
    $columnAssignments = $assignments->get($type . ':' . $resource->id, collect());
    $selectedDriverId = $columnAssignments->first()?->driver_id;
    $drivers = $type === 'boat' ? $resource->boatDrivers : $resource->vehicleDrivers;
@endphp
{{-- JS hooks: .resource-column[data-type][data-resource-id][data-capacity], .resource-heading h3, .capacity-counter, .driver-select, .assignment-list, .drop-hint, .capacity-exceeded --}}
<section
    class="resource-column group min-w-[18rem] flex-[1_0_18rem] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm
           [&.capacity-exceeded]:border-red-300 [&.capacity-exceeded]:ring-1 [&.capacity-exceeded]:ring-red-200
           {{ $type }}-column"
    data-type="{{ $type }}" data-resource-id="{{ $resource->id }}" data-region-id="{{ $resource->region_id }}"
    @if ($type === 'boat' && $resource->capacity > 0) data-capacity="{{ $resource->capacity }}" @endif>

    <div class="h-1 {{ $type === 'boat' ? 'bg-sky-500' : 'bg-amber-500' }}"></div>

    <header class="space-y-2.5 border-b border-slate-100 p-3">
        <div class="resource-heading flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-2">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md {{ $type === 'boat' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700' }}">
                    @if ($type === 'boat')
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v10M12 5l6 8h-6M3 17l2 3h14l2-3H3z"/></svg>
                    @else
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 17h2a1 1 0 0 0 1-1v-3a2 2 0 0 0-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5a1 1 0 0 0-.9.6L2.2 11A4 4 0 0 0 2 12v4a1 1 0 0 0 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/></svg>
                    @endif
                </span>
                <h3 class="truncate text-sm font-semibold text-slate-900">{{ $resource->name }}</h3>
            </div>
            <span class="text-[10px] text-slate-500">{{ $resource->region?->name ?? 'No region' }}</span>
            @if ($type === 'boat')
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $resource->ownership_type === 'external' ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-600' }}">
                    {{ $resource->ownership_type === 'external' ? 'External' : 'Internal' }}
                </span>
            @endif
            @if ($type === 'boat' && $resource->capacity > 0)
                <span class="capacity-counter whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-600
                             group-[.capacity-exceeded]:bg-red-100 group-[.capacity-exceeded]:text-red-700">0/{{ $resource->capacity }}</span>
            @endif
        </div>

        <label class="sr-only" for="driver-{{ $type }}-{{ $resource->id }}">Driver for {{ $resource->name }}</label>
        <select id="driver-{{ $type }}-{{ $resource->id }}"
            class="driver-select w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-slate-700 shadow-sm transition focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
            <option value="">No driver selected</option>
            @foreach ($drivers as $resourceDriver)
                @php($driverId = $type === 'boat' ? $resourceDriver->id : $resourceDriver->driver_id)
                <option value="{{ $driverId }}" @selected((int) $selectedDriverId === (int) $driverId)>
                    {{ $resourceDriver->driver?->name ?? $resourceDriver->name ?? 'Unnamed driver' }}
                </option>
            @endforeach
        </select>
    </header>

    <div class="relative">
        <p class="drop-hint pointer-events-none absolute inset-2 grid place-items-center rounded-lg border border-dashed border-slate-200 px-4 text-center text-xs text-slate-400 {{ $columnAssignments->isNotEmpty() ? 'hidden' : '' }}">
            Drag a booking here
        </p>
        <div class="assignment-list relative min-h-[120px] space-y-2 p-2" aria-label="{{ $resource->name }} assignments">
            @foreach ($columnAssignments as $assignment)
                @if ($operation = $operations->get($assignment->operation_id))
                    @include('staff.daily-ops.partials.card', ['operation' => $operation, 'showRemove' => true, 'assignmentType' => $type])
                @endif
            @endforeach
        </div>
    </div>
</section>

