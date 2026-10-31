
@extends('staff.main')

@section('body')
    <!-- Header -->


    <style>
        html, body {
            height: 100%;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 14px;
        }

        .booking-page {
            background-color: #f1f5f9;
            background-image:
                radial-gradient(circle at 8% 0%, rgba(14, 165, 233, 0.10), transparent 40%),
                radial-gradient(circle at 92% 10%, rgba(20, 184, 166, 0.10), transparent 40%);
        }

        .hero-block {
            background: linear-gradient(135deg, #075985 0%, #0e7490 55%, #0891b2 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-block::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 100% 0%, rgba(255,255,255,0.12), transparent 55%);
            pointer-events: none;
        }

        .form-label {
            display: block;
            margin-bottom: 0.2rem;
            font-size: 0.68rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.02em;
        }

        .form-control,
        .form-select {
            width: 100%;
            border-radius: 0.6rem;
            border: 2px solid #94a3b8;
            background-color: #ffffff;
            padding: 0.4rem 0.7rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f172a;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .form-control::placeholder {
            color: #64748b;
            font-weight: 600;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #0891b2;
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.25);
        }

        .form-control[readonly] {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
            cursor: not-allowed;
            border-color: #cbd5e1;
        }

        /* Readonly fields shown on the dark hero panel need their own
           light-on-dark treatment so they stay legible. */
        .hero-block .form-label {
            color: #cffafe;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .hero-block .form-control[readonly] {
            background-color: rgba(255, 255, 255, 0.12);
            border: 2px solid rgba(255, 255, 255, 0.35);
            color: #ffffff;
            font-weight: 800;
            box-shadow: none;
            padding: 0.3rem 0.6rem;
            font-size: 0.8rem;
        }

        .form-check-input {
            width: 1.1rem;
            height: 1.1rem;
            border-radius: 0.35rem;
            border: 2px solid #0891b2;
            accent-color: #0891b2;
            cursor: pointer;
        }

        .form-check-label {
            font-size: 0.85rem;
            font-weight: 800;
            color: #0f172a;
            cursor: pointer;
        }

        .step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.6rem;
            height: 1.6rem;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #0891b2, #075985);
            color: #ffffff;
            font-weight: 900;
            font-size: 0.75rem;
            flex-shrink: 0;
            box-shadow: 0 3px 8px rgba(8, 145, 178, 0.4);
        }

        .section-card {
            background-color: #ffffff;
            border: 1.5px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(2, 10, 20, 0.05);
        }

        .brand-strip {
            background: linear-gradient(90deg, #06b6d4, #0891b2, #0369a1);
        }

        /* Primary action buttons: always white text on a colored fill,
           for consistent contrast across the page. */
        .btn-primary {
            color: #ffffff !important;
        }

        /* Modal shell sized to fit a normal laptop viewport without the
           page itself needing to scroll. */
        .booking-modal {
            width: 100%;
            max-width: 78rem;
            max-height: 96vh;
        }

        .booking-modal-body {
            overflow-y: auto;
        }
    </style>
</head>

<body class="booking-page min-h-screen text-slate-900 antialiased">

{{-- Page behind the popup just shows a launcher; the whole booking flow lives in the modal below --}}
<div class="flex min-h-screen items-center justify-center p-4">
    <button
        type="button"
        onclick="openBookingModal()"
        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-600 to-sky-700 px-6 py-3 text-sm font-black text-white shadow-lg shadow-cyan-600/30 transition hover:from-cyan-700 hover:to-sky-800"
    >
        ⚓ New Boat Trip Booking
    </button>
</div>

{{-- ================= MAIN BOOKING MODAL (everything lives here) ================= --}}
<div
    id="bookingModal"
    class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/70 p-3"
>
    <div class="booking-modal flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-white shadow-[0_24px_70px_rgba(0,0,0,0.4)]">

        <div class="brand-strip h-1.5 w-full shrink-0"></div>

        <div class="booking-modal-body">

            {{-- ================= HEADER (unified block) ================= --}}
            <div class="hero-block relative shrink-0">

                {{-- Top row: title + booking id + close --}}
                <div class="relative flex flex-col gap-3 p-3 sm:flex-row sm:items-end sm:justify-between sm:p-4">
                    <div>
                        <p class="mb-1 flex items-center gap-2 text-[0.62rem] font-black uppercase tracking-[0.25em] text-cyan-100">
                            <span aria-hidden="true">⚓</span> Sea Trips Operations
                        </p>
                        <h2 class="text-xl font-black tracking-tight text-white sm:text-2xl">
                            New Boat Trip Booking
                        </h2>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="rounded-xl border border-white/30 bg-white/15 px-4 py-2 text-left backdrop-blur-sm">
                            <div class="text-[0.6rem] font-black uppercase tracking-widest text-cyan-50">
                                Booking ID
                            </div>
                            <div class="mt-0.5 text-lg font-black tracking-tight text-white">
                                {{ session('bookingId') }}
                            </div>
                        </div>

                        <button
                            type="submit"
                       onclick="closeCustomerModal()"
                           form="cancel-operation-form"
                            class="flex h-9 w-9 cursor-pointer  hover:bg-black text-white items-center justify-center rounded-full bg-white/15 text-2xl leading-none text-white transition hover:bg-white/25"
                            aria-label="Close"
                        >
                            &times;
                        </button>
                    </div>
                </div>

                {{-- Middle row: readonly trip context --}}
                <div class="relative grid grid-cols-1 gap-2 border-t border-white/20 px-3 py-2 sm:grid-cols-3 sm:px-4">

                    <div>
                        <label class="form-label">Trip Name</label>
                        <input type="text" name="trip_name" class="form-control" value="{{ session('tripName') }}" readonly>
                    </div>

                    <div>
                        <label class="form-label">By Whom</label>
                        <input type="text" name="created_by" class="form-control" value="{{ auth()->user()->name ?? '' }}" readonly>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="button"
                            onclick="openCustomerModal()"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-3 py-1.5 text-xs font-black text-cyan-800 shadow-md shadow-black/10 transition hover:bg-cyan-50 focus:outline-none focus:ring-4 focus:ring-white/40"
                        >
                            <span aria-hidden="true">＋</span> Add Customer
                        </button>
                    </div>

                </div>

                @if(session('success'))
                    <div class="relative border-t border-white/20 px-3 py-2 sm:px-4">
                        <div class="rounded-lg border border-emerald-300/50 bg-emerald-500/20 px-3 py-1.5 text-xs font-bold text-emerald-50">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif
            </div>

            {{-- ================= NESTED CUSTOMER MODAL ================= --}}
            <div
                id="customerModal"
                class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 px-4"
            >
                <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">

                    <div class="brand-strip h-1.5 w-full"></div>

                    <div class="p-5">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-black text-slate-950">
                                Add Customer
                            </h2>
                            <button
                                type="button"
                                onclick="closeCustomerModal()"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 text-xl leading-none"
                            >
                                &times;
                            </button>
                        </div>

                        @if (session('customer_form') && session('error'))
                            <div class="mb-4 flex items-center gap-3 rounded-xl border-2 border-red-500 bg-red-100 p-3 text-xs font-black text-red-800 shadow-sm" role="alert" aria-live="assertive">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-600 text-sm text-white" aria-hidden="true">!</span>
                                {{ session('error') }}
                            </div>
                        @endif

                        <form action="{{ route('store.Customer') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" required class="form-control @error('name') border-red-600 bg-red-50 @enderror" value="{{ old('name') }}" placeholder="Customer name">
                                @error('name')
                                    <p class="mt-1 rounded-md border border-red-200 bg-red-50 px-2 py-1 text-xs font-black text-red-700" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control @error('email') border-red-600 bg-red-50 @enderror" value="{{ old('email') }}" placeholder="customer@example.com">
                                @error('email')
                                    <p class="mt-1 rounded-md border border-red-200 bg-red-50 px-2 py-1 text-xs font-black text-red-700" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control @error('phone') border-red-600 bg-red-50 @enderror" value="{{ old('phone') }}" placeholder="Phone number">
                                @error('phone')
                                    <p class="mt-1 rounded-md border border-red-200 bg-red-50 px-2 py-1 text-xs font-black text-red-700" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" value="{{ old('address') }}" placeholder="Customer address">
                            </div>

                            <div class="mb-5">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="Notes...">{{ old('notes') }}</textarea>
                            </div>

                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    onclick="closeCustomerModal()"
                                    class="rounded-lg border-2 border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="btn-primary rounded-lg bg-cyan-600 px-4 py-2 text-xs font-black shadow-md shadow-cyan-600/25 transition hover:bg-cyan-700"
                                >
                                    Save Customer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
                function openCustomerModal() {
                    document.getElementById('customerModal').classList.remove('hidden');
                    document.getElementById('customerModal').classList.add('flex');
                }
                function closeCustomerModal() {
                    document.getElementById('customerModal').classList.add('hidden');
                    document.getElementById('customerModal').classList.remove('flex');
                }

                @if (session('customer_form'))
                    openCustomerModal();
                @endif
            </script>


@if(session('error'))
    <div class="mx-3 mt-3 rounded-lg border-2 border-red-300 bg-red-50 p-3 text-red-800 sm:mx-4">
        <div class="flex items-start gap-2">
            <span class="text-base">⚠️</span>

            <div>
                <h4 class="text-sm font-black">
                    Error
                </h4>

                <p class="mt-0.5 text-xs font-semibold">
                    {{ session('error') }}
                </p>
            </div>
        </div>
    </div>
@endif


@if($errors->any())
    <div class="mx-3 mt-3 rounded-lg border-2 border-red-300 bg-red-50 p-3 text-red-800 sm:mx-4">
        <h4 class="text-sm font-black">
            Please fix the following errors:
        </h4>

        <ul class="mt-1 list-inside list-disc text-xs font-semibold">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


<form
    action="{{ route('save.operations') }}"
    method="POST"
    class="space-y-3 p-3 sm:p-4"
>
    @csrf

    {{-- Two-column layout so both halves of the form share the width instead of stacking --}}
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">

        {{-- LEFT COLUMN --}}
        <div class="space-y-3">

            {{-- ================= TRIP INFO ================= --}}

            <div class="section-card rounded-xl p-3">

                <div class="mb-2 flex items-center gap-2">

                    <span class="step-badge">1</span>

                    <div>
                        <p class="text-[0.58rem] font-black uppercase tracking-[0.2em] text-cyan-700">
                            Step 1
                        </p>

                        <h3 class="text-sm font-black tracking-tight text-slate-950">
                            Trip Information
                        </h3>
                    </div>

                </div>


                <div class="grid grid-cols-1 gap-2 md:grid-cols-12">

                    {{-- Trip Date --}}
                    <div class="md:col-span-3">

                        <label class="form-label">
                            Trip Date
                        </label>

                        <input
                            type="date"
                            name="trip_date"
                            class="form-control"
                            value="{{ old('trip_date', date('Y-m-d')) }}"
                        >

                    </div>


                    {{-- Trip Time --}}
                    <div class="md:col-span-3">

                        <label class="form-label">
                            Trip Time
                        </label>

                        <input
                            type="time"
                            name="trip_time"
                            class="form-control"
                            value="{{ old('trip_time', date('H:i')) }}"
                        >

                    </div>


                    {{-- Company --}}
                    <div class="md:col-span-6">

                        <label
                            class="form-label"
                            for="company_id"
                        >
                            Company
                        </label>

                        <select
                            name="company_id"
                            id="company_id"
                            class="form-select"
                            required
                        >

                            @foreach(session('companies', []) as $company)

                                <option
                                    value="{{ data_get($company, 'id') }}"
                                    {{ (int) old('company_id', session('company_id', 1)) === (int) data_get($company, 'id') ? 'selected' : '' }}
                                >
                                    {{ data_get($company, 'name') }}
                                </option>

                            @endforeach

                        </select>

                        <p
                            id="company-error"
                            class="mt-1 hidden rounded-md border border-red-200 bg-red-50 px-2 py-1 text-xs font-black text-red-700"
                            role="alert"
                        ></p>

                    </div>

                </div>

            </div>



            {{-- ================= CUSTOMER ================= --}}

            <div class="section-card rounded-xl p-3">

                <div class="mb-2 flex items-center gap-2">

                    <span class="step-badge">2</span>

                    <div>
                        <p class="text-[0.58rem] font-black uppercase tracking-[0.2em] text-cyan-700">
                            Step 2
                        </p>

                        <h3 class="text-sm font-black tracking-tight text-slate-950">
                            Customer Information
                        </h3>
                    </div>

                </div>


                <div class="grid grid-cols-1 gap-2 md:grid-cols-12">

                    {{-- Adults --}}
                    <div class="md:col-span-4">

                        <label class="form-label">
                            Adults
                        </label>

                        <input
                            type="number"
                            name="adults"
                            class="form-control"
                            value="{{ old('adults', 1) }}"
                            min="0"
                        >

                    </div>


                    {{-- Children --}}
                    <div class="md:col-span-4">

                        <label class="form-label">
                            Children
                        </label>

                        <input
                            type="number"
                            name="children"
                            class="form-control"
                            value="{{ old('children', 0) }}"
                            min="0"
                        >

                    </div>


                    {{-- Infants --}}
                    <div class="md:col-span-4">

                        <label class="form-label">
                            Infants
                        </label>

                        <input
                            type="number"
                            name="info"
                            class="form-control"
                            value="{{ old('info', 0) }}"
                            min="0"
                        >

                    </div>

                </div>

            </div>



            {{-- ================= HOTEL ================= --}}

            <div class="section-card rounded-xl p-3">

                <div class="mb-2 flex items-center gap-2">

                    <span class="step-badge">3</span>

                    <div>
                        <p class="text-[0.58rem] font-black uppercase tracking-[0.2em] text-cyan-700">
                            Step 3
                        </p>

                        <h3 class="text-sm font-black tracking-tight text-slate-950">
                            Hotel &amp; Pickup
                        </h3>
                    </div>

                </div>


                <div class="grid grid-cols-1 gap-2 md:grid-cols-12">

                    {{-- Hotel --}}
                    <div class="md:col-span-6">

                        <label class="form-label">
                            Hotel
                        </label>

                        <input
                            type="text"
                            name="hotel_name"
                            class="form-control"
                            id="hotel_name"
                            list="hotel-options"
                            placeholder="Type hotel name"
                            autocomplete="off"
                            value="{{ old('hotel_name') }}"
                        >

                        <input
                            type="hidden"
                            name="hotel_id"
                            id="hotel_id"
                            value="{{ old('hotel_id') }}"
                        >


                        <datalist id="hotel-options">

                            @foreach(session('hotels', []) as $hotel)

                                <option
                                    value="{{ $hotel['name'] }}"
                                    data-hotel-id="{{ $hotel['id'] }}"
                                    data-pickup-time="{{ $hotel['pickup_time'] ?? '' }}"
                                ></option>

                            @endforeach

                        </datalist>

                    </div>


                    {{-- Pickup Time --}}
                    <div class="md:col-span-6">

                        <label class="form-label">
                            Pickup Time
                        </label>

                        <input
                            type="time"
                            name="pick_time"
                            class="form-control"
                            id="pick_time"
                            value="{{ old('pick_time') }}"
                        >

                    </div>

                </div>

            </div>

        </div>


        {{-- RIGHT COLUMN --}}
        <div class="space-y-3">

            {{-- ================= EXTRAS ================= --}}

            {{-- Step 4: Extra Services --}}
            <div class="section-card rounded-xl p-3">

                {{-- Header --}}
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">

                    <div class="flex items-center gap-2">

                        <span class="step-badge">4</span>

                        <div>
                            <p class="text-[0.58rem] font-black uppercase tracking-[0.2em] text-cyan-700">
                                Step 4
                            </p>

                            <h3 class="text-sm font-black tracking-tight text-slate-950">
                                Extra Services
                            </h3>
                        </div>

                    </div>

                    {{-- Enable Extras Toggle --}}
                    <label
                        for="enableExtras"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border-2 border-slate-200 bg-slate-50 px-3 py-1.5 transition hover:border-cyan-400 hover:bg-cyan-50"
                    >

                        <span class="text-xs font-bold text-slate-700">
                            Add Extras
                        </span>

                        {{-- Hidden value --}}
                        <input
                            type="hidden"
                            name="enable_extras"
                            value="0"
                        >

                        {{-- Checkbox --}}
                        <div class="relative">
                            <input
                                type="checkbox"
                                name="enable_extras"
                                value="1"
                                id="enableExtras"
                                class="peer sr-only"
                                {{ old('enable_extras') ? 'checked' : '' }}
                            >

                            <div class="h-6 w-10 rounded-full bg-slate-300 transition-all duration-300 peer-checked:bg-cyan-500 peer-focus:ring-4 peer-focus:ring-cyan-100">
                            </div>

                            <div class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white shadow-md transition-all duration-300 peer-checked:translate-x-4">
                            </div>
                        </div>

                    </label>

                </div>


                {{-- Extras Container --}}
                <div
                    id="extrasContainer"
                    class="{{ old('enable_extras') ? '' : 'hidden' }} max-h-28 overflow-y-auto border-t border-slate-200 pt-3"
                >

                    {{-- Extras Grid --}}
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">

                        @foreach(session('exterTrips', []) as $extra)

                            <label
                                for="extra{{ $extra['id'] }}"
                                class="group flex cursor-pointer items-center justify-between gap-2 rounded-lg border-2 border-slate-200 bg-slate-50 p-2 transition hover:border-cyan-400 hover:bg-cyan-50"
                            >

                                <div class="flex min-w-0 items-center gap-2">

                                    <input
                                        class="extra-checkbox h-4 w-4 cursor-pointer rounded-md border-2 border-slate-300 text-cyan-600 focus:ring-cyan-500"
                                        type="checkbox"
                                        name="extras[]"
                                        value="{{ $extra['id'] }}"
                                        data-extra="{{ $extra['price'] }}"
                                        id="extra{{ $extra['id'] }}"
                                        {{ in_array($extra['id'], old('extras', [])) ? 'checked' : '' }}
                                    >

                                    <p class="truncate text-xs font-bold text-slate-700 group-hover:text-cyan-700">
                                        {{ $extra['name'] }}
                                    </p>

                                </div>


                                <span class="shrink-0 rounded-md bg-white px-2 py-1 text-xs font-black text-cyan-600 ring-1 ring-slate-200">
                                    {{ number_format((float) $extra['price'], 2) }}
                                </span>

                            </label>

                        @endforeach

                    </div>

                </div>


                {{-- Currency --}}
                <div class="mt-3 border-t border-slate-200 pt-3">

                    <label
                        for="currency_id"
                        class="form-label"
                    >
                        Currency
                    </label>

                    <select
                        name="currency_id"
                        id="currency_id"
                        class="form-select md:w-1/2"
                    >

                        <option value="">
                            Select Currency
                        </option>

                        @foreach(session('currencies', []) as $currency)

                            <option
                                value="{{ $currency['id'] }}"
                                {{ (string) old(
                                    'currency_id',
                                    $currency['code'] === 'EGP'
                                        ? $currency['id']
                                        : ''
                                ) === (string) $currency['id']
                                    ? 'selected'
                                    : ''
                                }}
                            >
                                {{ $currency['name'] }}
                                ({{ $currency['code'] }})
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>



            {{-- ================= PRICE ================= --}}

            <div class="section-card rounded-xl p-3">

                <div class="mb-2 flex items-center gap-2">

                    <span class="step-badge">5</span>

                    <div>
                        <p class="text-[0.58rem] font-black uppercase tracking-[0.2em] text-cyan-700">
                            Step 5
                        </p>

                        <h3 class="text-sm font-black tracking-tight text-slate-950">
                            Price
                        </h3>
                    </div>

                </div>


                <div class="grid grid-cols-1 gap-2 md:grid-cols-12">

                    {{-- Price --}}
                    <div class="md:col-span-4">

                        <label class="form-label">
                            Price
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            class="form-control"
                            value="{{ old('price', 0) }}"
                            step="0.01"
                        >

                    </div>


                    {{-- Extra --}}
                    <div class="md:col-span-4">

                        <label class="form-label">
                            Extra
                        </label>

                        <input
                            type="number"
                            id="extra"
                            name="extra"
                            class="form-control"
                            value="{{ old('extra', 0) }}"
                            step="0.01"
                        >

                    </div>


                    {{-- Discount --}}
                    <div class="md:col-span-4">

                        <label class="form-label">
                            Discount
                        </label>

                        <input
                            type="number"
                            id="discount"
                            name="discount"
                            class="form-control"
                            value="{{ old('discount', 0) }}"
                            step="0.01"
                        >

                    </div>

                </div>


                <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-12">

                    {{-- Tax --}}
                    <div class="md:col-span-4">

                        <label
                            class="form-label"
                            for="rate_tax"
                        >
                            Tax (%)
                        </label>

                        <input
                            type="number"
                            id="rate_tax"
                            name="rate_tax"
                            class="form-control"
                            value="{{ old('rate_tax', 0) }}"
                            min="0"
                            step="0.01"
                        >

                    </div>


                    {{-- Payment Method --}}
                    <div class="md:col-span-4">

                        <label
                            class="form-label"
                            for="method_payment"
                        >
                            Payment Method
                        </label>

                        <select
                            name="method_payment"
                            id="method_payment"
                            class="form-select"
                            required
                        >

                            <option
                                value="cash"
                                {{ old('method_payment', 'cash') === 'cash' ? 'selected' : '' }}
                            >
                                Cash
                            </option>

                            <option
                                value="credit"
                                {{ old('method_payment') === 'credit' ? 'selected' : '' }}
                            >
                                Credit
                            </option>

                        </select>

                    </div>

                </div>

            </div>



            {{-- ================= TOTAL ================= --}}

            <div class="relative overflow-hidden rounded-xl border-2 border-cyan-300 bg-gradient-to-br from-cyan-50 to-sky-50 p-3">

                <svg
                    class="pointer-events-none absolute -bottom-4 -right-4 h-16 w-16 text-cyan-300/50"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                >
                    <path d="M12 2c-1 3-1 5 0 8 1-3 1-5 0-8zM3 13c4 3 14 3 18 0-1 5-8 9-9 9s-8-4-9-9z"/>
                </svg>


                <div class="relative grid grid-cols-1 items-center gap-3 sm:grid-cols-2">

                    {{-- Total --}}
                    <div>

                        <span class="text-[0.58rem] font-black uppercase tracking-[0.2em] text-cyan-800">
                            Total
                        </span>

                        <div class="mt-1 text-2xl font-black tracking-tight text-slate-950">

                            <span id="total">
                                {{ old('amount_total', '0.00') }}
                            </span>

                        </div>

                    </div>


                    {{-- Paid --}}
                    <div id="paid-wrapper">

                        <label class="form-label">
                            Paid
                        </label>

                        <input
                            type="number"
                            id="paid"
                            name="paid"
                            class="form-control"
                            value="{{ old('paid', 0) }}"
                            step="0.01"
                        >

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- ================= ACTIONS ================= --}}

    <div class="flex flex-col-reverse gap-2 border-t-2 border-slate-200 pt-3 sm:flex-row sm:justify-end">

        {{-- Cancel --}}
        <button
            type="submit"
            form="cancel-operation-form"
            class="rounded-lg border-2 border-slate-300 bg-white px-5 py-2 text-xs font-black text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
        >
            Cancel
        </button>


        {{-- Save --}}
        <button
            type="submit"
            class="btn-primary rounded-lg bg-gradient-to-r from-cyan-600 to-sky-700 px-6 py-2 text-xs font-black shadow-md shadow-cyan-600/30 transition hover:from-cyan-700 hover:to-sky-800 focus:outline-none focus:ring-4 focus:ring-cyan-300/40"
        >
            ⚓ Save Booking
        </button>

    </div>

</form>

            <form id="cancel-operation-form" action="{{ route('cancel.operations') }}" method="POST" class="hidden">
                @csrf
            </form>

        </div>
        {{-- /.booking-modal-body --}}

    </div>
    {{-- /.booking-modal --}}

</div>
{{-- /#bookingModal --}}


<script>
    function openBookingModal() {
        document.getElementById('bookingModal').classList.remove('hidden');
        document.getElementById('bookingModal').classList.add('flex');
    }
    function closeBookingModal() {
        document.getElementById('bookingModal').classList.add('hidden');
        document.getElementById('bookingModal').classList.remove('flex');
    }

    document.addEventListener('DOMContentLoaded', function () {

        // Open automatically — this page's whole purpose is the booking popup,
        // and it also needs to reopen itself after a validation error or
        // after the "Add Customer" sub-form redirects back.
        openBookingModal();

        /*
        |--------------------------------------------------------------------------
        | Extras
        |--------------------------------------------------------------------------
        */

        const enableExtras = document.getElementById('enableExtras');
        const extrasContainer = document.getElementById('extrasContainer');

        if (enableExtras && extrasContainer) {

            function toggleExtras() {

                if (enableExtras.checked) {

                    extrasContainer.classList.remove('hidden');

                } else {

                    extrasContainer.classList.add('hidden');

                }

            }

            enableExtras.addEventListener('change', toggleExtras);

            // Restore state after validation/error
            toggleExtras();
        }


        /*
        |--------------------------------------------------------------------------
        | Hotel
        |--------------------------------------------------------------------------
        */

        const hotelName = document.getElementById('hotel_name');
        const hotelId = document.getElementById('hotel_id');
        const pickupTime = document.getElementById('pick_time');

        const hotelOptions = document.querySelectorAll(
            '#hotel-options option'
        );

        if (hotelName) {

            hotelName.addEventListener('input', function () {

                const selectedHotel = Array.from(hotelOptions)
                    .find(option => option.value === this.value);

                if (selectedHotel) {

                    hotelId.value =
                        selectedHotel.dataset.hotelId || '';

                    if (selectedHotel.dataset.pickupTime) {

                        pickupTime.value =
                            selectedHotel.dataset.pickupTime;

                    }

                } else {

                    hotelId.value = '';

                }

            });


            /*
            |--------------------------------------------------------------------------
            | Restore hotel ID after error
            |--------------------------------------------------------------------------
            */

            const currentHotel = Array.from(hotelOptions)
                .find(option => option.value === hotelName.value);

            if (currentHotel) {

                hotelId.value =
                    currentHotel.dataset.hotelId || '';

            }
        }

    });
</script>

@php
    $pricesCompany = session('prices_company');

    $bookingPrices = [
        'adult' => (float) data_get($pricesCompany, 'price_adult', 0),
        'child' => (float) data_get($pricesCompany, 'price_child', 0),
        'infant' => (float) data_get($pricesCompany, 'price_infant', 0),
    ];
@endphp

<script>
    window.bookingPrices = @json($bookingPrices);
    window.currencies = @json(session('currencies', []));
    window.companyPricesUrl = @json(route('company.prices'));
    console.log('BOOKING PRICES:', window.bookingPrices);
</script>

<script src="{{ asset('js/main.js') }}"></script>

@endsection
