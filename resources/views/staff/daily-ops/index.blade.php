@extends('staff.main')

@section('body')
    {{--
        Styling is 100% Tailwind utility classes (no custom <style> block).
        Requires Tailwind CSS v3.2+ (or v4) scanning resources/views/**/*.blade.php.
        Quick preview without a build step: uncomment the CDN line below.
    --}}
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}

    <div class="daily-ops -mx-4 px-3 py-6 text-slate-800 sm:-mx-6 sm:px-4 lg:-mx-8 lg:px-4" id="daily-ops"
         data-save-url="{{ route('daily-ops.save') }}" data-date="{{ $date }}">

        {{-- ================= Header / toolbar ================= --}}
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">Daily operations</h1>
                <p class="mt-0.5 text-sm text-slate-500">Drag bookings onto a boat or vehicle, choose a driver, then save the plan.</p>
            </div>

            <form class="flex flex-wrap items-end gap-3" method="GET" action="{{ route('daily-ops.index') }}">
                <label class="grid gap-1 text-xs font-medium text-slate-600">
                    Date
                    <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm transition focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
                </label>
                @systemPermission('daily_operations', 'update')
                <button type="button" id="save-planner"
                    class="inline-flex items-center gap-2 rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-600 focus-visible:ring-offset-2 active:translate-y-px disabled:cursor-wait disabled:opacity-60">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                    Save plan
                </button>
                @endsystemPermission
            </form>
        </div>

        <p id="planner-message" role="status" aria-live="polite" class="mb-4 hidden"></p>

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @systemPermission('daily_operations', 'create')
        <details class="mb-5 rounded-xl border border-slate-200 bg-white p-4">
            <summary class="cursor-pointer text-sm font-semibold text-slate-900">Add a boat</summary>
            <form method="POST" action="{{ route('daily-ops.boats.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <label class="grid gap-1 text-xs font-medium text-slate-600">Boat name
                    <input name="name" value="{{ old('name') }}" required maxlength="100" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="grid gap-1 text-xs font-medium text-slate-600">Code
                    <input name="code" value="{{ old('code') }}" required maxlength="50" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </label>
     
                <label class="grid gap-1 text-xs font-medium text-slate-600">Boat ownership
                    <select name="ownership_type" id="boat-ownership-type" required class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="internal" @selected(old('ownership_type', 'external') === 'internal')>Internal</option>
                        <option value="external" @selected(old('ownership_type', 'external') === 'external')>External</option>
                    </select>
                </label>
                <label class="boat-external-field grid gap-1 text-xs font-medium text-slate-600">Supplier
                    <select name="supplier_id" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Choose supplier</option>
                        @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>@endforeach
                    </select>
                </label>
                <label class="boat-external-field grid gap-1 text-xs font-medium text-slate-600">Supplier price
                    <input type="number" name="supplier_price" value="{{ old('supplier_price') }}" min="0" step="0.01" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="grid gap-1 text-xs font-medium text-slate-600">Capacity
                    <input type="number" name="capacity" value="{{ old('capacity') }}" min="1" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="grid gap-1 text-xs font-medium text-slate-600">Region
                    <select name="region_id" required class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Choose region</option>
                        @foreach ($regions as $region)<option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>@endforeach
                    </select>
                </label>
                <div class="flex items-end"><button class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Save boat</button></div>
            </form>
        </details>
        @endsystemPermission
        <script>
            (() => {
                const type = document.getElementById('boat-ownership-type');
                const syncFields = () => document.querySelectorAll('.boat-external-field').forEach((field) => {
                    const isExternal = type.value === 'external';
                    field.classList.toggle('hidden', !isExternal);
                    const input = field.querySelector('input, select');
                    input.required = isExternal;
                    input.disabled = !isExternal;
                });
                type.addEventListener('change', syncFields);
                syncFields();
            })();
        </script>

        <div class="grid items-start gap-5 xl:grid-cols-[300px_minmax(0,1fr)]">

            {{-- ================= Operations (source list) ================= --}}
            <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 xl:sticky xl:top-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Bookings</h2>
                    <span id="assigned-counter" class="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-semibold tabular-nums text-teal-700 ring-1 ring-teal-100">0/{{ count($operations) }} assigned</span>
                </div>

                <label class="sr-only" for="operation-search">Search bookings</label>
                <div class="relative mb-3">
                    <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input id="operation-search" type="search" placeholder="Search booking or hotel"
                        class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-8 pr-3 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20">
                </div>

                <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-sky-500"></span>On a boat</span>
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span>In a vehicle</span>
                    <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-violet-500"></span>Both</span>
                </div>

                <div id="operations-list" aria-label="Daily operations"
                     class="min-h-[90px] space-y-2 xl:max-h-[calc(100vh-14rem)] xl:overflow-y-auto xl:pr-1">
                    @forelse ($operations as $operation)
                        @include('staff.daily-ops.partials.card', ['operation' => $operation])
                    @empty
                        <p class="empty-hint rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">No complete operations for this date.</p>
                    @endforelse
                </div>
            </section>

            {{-- ================= Boats & Vehicles ================= --}}
            <div class="min-w-0 space-y-5">
                <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
                        Boats
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium tabular-nums text-slate-500 ring-1 ring-slate-200">{{ count($boats) }}</span>
                    </h2>
                    <div class="flex items-start gap-4 overflow-x-auto pb-2">
                        @forelse ($boats as $resource)
                            @include('staff.daily-ops.partials.resource-column', ['type' => 'boat', 'resource' => $resource])
                        @empty
                            <p class="empty-hint text-sm text-slate-400">No boats available.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
                        Vehicles
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-medium tabular-nums text-slate-500 ring-1 ring-slate-200">{{ count($vehicles) }}</span>
                    </h2>
                    <div class="flex items-start gap-4 overflow-x-auto pb-2">
                        @forelse ($vehicles as $resource)
                            @include('staff.daily-ops.partials.resource-column', ['type' => 'vehicle', 'resource' => $resource])
                        @empty
                            <p class="empty-hint text-sm text-slate-400">No vehicles available.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>

       @vite('resources/js/daily-ops.js')
@endsection
