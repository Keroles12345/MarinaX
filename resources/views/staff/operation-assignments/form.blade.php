@extends('staff.main')

@section('body')
<div class="mx-auto max-w-4xl py-6">
    <a href="{{ route('operation-assignments.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-900">← Back to assignments</a>
    <div class="mb-6 mt-4"><p class="text-sm font-semibold uppercase tracking-wider text-teal-700">Operations</p><h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $assignment->exists ? 'Edit assignment' : 'Create assignment' }}</h1><p class="mt-1 text-sm text-slate-500">Choose the operation, resource, driver and date for this assignment.</p></div>
    @if($errors->any())<div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><p class="font-semibold">Please review the highlighted information.</p><ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $assignment->exists ? route('operation-assignments.update', $assignment) : route('operation-assignments.store') }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @csrf @if($assignment->exists) @method('PUT') @endif
        <div class="grid gap-5 p-6 sm:grid-cols-2">
            <label class="grid gap-1.5 text-sm font-semibold text-slate-700 sm:col-span-2">Operation / booking
                <select name="operation_id" required class="rounded-lg border-slate-300 text-sm font-normal focus:border-teal-600 focus:ring-teal-600"><option value="">Choose a booking</option>@foreach($operations as $operation)<option value="{{ $operation->id }}" @selected(old('operation_id', $assignment->operation_id) == $operation->id)>{{ $operation->booking_id }} · {{ optional($operation->trip_date)->format('M j, Y') ?? $operation->trip_date }}</option>@endforeach</select>
            </label>
            <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Resource type<select id="assignment-type" name="type" required class="rounded-lg border-slate-300 text-sm font-normal focus:border-teal-600 focus:ring-teal-600"><option value="">Choose type</option><option value="boat" @selected(old('type', $assignment->type) === 'boat')>Boat</option><option value="vehicle" @selected(old('type', $assignment->type) === 'vehicle')>Vehicle</option></select></label>
            <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Resource<select id="resource-id" name="resource_id" required class="rounded-lg border-slate-300 text-sm font-normal focus:border-teal-600 focus:ring-teal-600"><option value="">Choose a type first</option></select></label>
            <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Driver <span class="font-normal text-slate-400">(optional)</span><select id="driver-id" name="driver_id" class="rounded-lg border-slate-300 text-sm font-normal focus:border-teal-600 focus:ring-teal-600"><option value="">No driver selected</option></select></label>
            <label class="grid gap-1.5 text-sm font-semibold text-slate-700">Assignment date<input type="date" name="assign_date" required value="{{ old('assign_date', optional($assignment->assign_date)->format('Y-m-d') ?? today()->toDateString()) }}" class="rounded-lg border-slate-300 text-sm font-normal focus:border-teal-600 focus:ring-teal-600"></label>
        </div>
        <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4"><a href="{{ route('operation-assignments.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a><button class="rounded-lg bg-teal-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">{{ $assignment->exists ? 'Save changes' : 'Create assignment' }}</button></div>
    </form>
</div>
<script>
(() => {
    const groups = {
        boat: @json($boats->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'code' => $item->code])->values()),
        vehicle: @json($vehicles->map(fn($item) => ['id' => $item->id, 'name' => $item->name, 'code' => $item->code])->values())
    };
    const type = document.getElementById('assignment-type');
    const resource = document.getElementById('resource-id');
    const driver = document.getElementById('driver-id');
    const resourceDrivers = @json($resourceDrivers);
    const driverNames = @json(collect($drivers)->map(fn($items) => $items->mapWithKeys(fn($item) => [$item->id => $item->name])));
    const selected = @json((string) old('resource_id', $assignment->resource_id));
    const selectedDriver = @json((string) old('driver_id', $assignment->driver_id));
    const fillDrivers = () => {
        const current = driver.value || selectedDriver;
        driver.replaceChildren(new Option('No driver selected', ''));
        ((resourceDrivers[type.value] || {})[resource.value] || []).forEach(id => {
            const option = new Option((driverNames[type.value] || {})[id] || `Driver #${id}`, id);
            option.selected = String(id) === String(current);
            driver.add(option);
        });
    };
    const fill = () => {
        resource.replaceChildren(new Option(type.value ? 'Choose a resource' : 'Choose a type first', ''));
        (groups[type.value] || []).forEach(item => {
            const label = item.code ? `${item.name} · ${item.code}` : item.name;
            const option = new Option(label, item.id);
            option.selected = String(item.id) === selected;
            resource.add(option);
        });
    };
    type.addEventListener('change', () => { fill(); fillDrivers(); });
    resource.addEventListener('change', fillDrivers);
    fill();
    fillDrivers();
})();
</script>
@endsection
