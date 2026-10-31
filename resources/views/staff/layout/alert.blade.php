@if (session('error'))
    <div
        id="error-alert"
        class="mb-4 flex items-center justify-between gap-4 rounded-xl border border-red-300 bg-red-100 px-4 py-3 text-red-700 shadow-sm"
    >
        <div class="flex items-center gap-2">
            <span class="text-lg">⚠️</span>
            <span class="font-medium">
                {{ \App\Support\DashboardTemplateTranslator::translate((string) session('error')) }}
            </span>
        </div>

        <button
            type="button"
            onclick="closeAlert('error-alert')"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-lg font-bold text-red-600 transition hover:bg-red-200 hover:text-red-800"
            aria-label="Close"
        >
            ×
        </button>
    </div>
@endif


{{-- Success Message --}}
@if (session('success'))
    <div
        id="success-alert"
        class="mb-4 flex items-center justify-between gap-4 rounded-xl border border-green-300 bg-green-100 px-4 py-3 text-green-800 shadow-sm"
    >
        <div class="flex items-center gap-2">
            <span class="text-lg">✅</span>

            <span class="font-medium">
                {{ \App\Support\DashboardTemplateTranslator::translate((string) session('success')) }}
            </span>

        </div>

        <button
            type="button"
            onclick="closeAlert('success-alert')"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-lg font-bold text-green-600 transition hover:bg-green-200 hover:text-green-900"
            aria-label="Close"
        >
            ×
        </button>
    </div>


        @if ($errors->any())
            <div data-flash class="flex items-start justify-between gap-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 shadow-sm">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-5 w-5 shrink-0 text-red-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12V16.5Zm9-4.5a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <ul class="space-y-1 text-sm font-medium">
                        @foreach ($errors->all() as $message)
                            <li>{{ \App\Support\DashboardTemplateTranslator::translate((string) $message) }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" onclick="this.closest('[data-flash]').remove()" class="shrink-0 rounded-full p-1 text-red-500 hover:bg-red-100 hover:text-red-700" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

    </div>
@endif
