@extends('staff.main')

@section('title', 'تحميل التذكرة')

@section('body')
    <section dir="rtl" class="mx-auto flex min-h-[75vh] max-w-5xl items-center justify-center px-2 py-8 sm:px-6">
        <div class="w-full overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_80px_-28px_rgba(15,23,42,0.28)]">
            <div class="relative overflow-hidden bg-gradient-to-l from-slate-950 via-cyan-950 to-teal-800 px-6 py-8 text-white sm:px-12 sm:py-10">
                <div class="absolute -left-12 -top-24 h-64 w-64 rounded-full bg-white/5"></div>
                <div class="absolute -bottom-32 right-1/3 h-64 w-64 rounded-full bg-teal-300/10 blur-2xl"></div>
                <div class="relative flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 shadow-lg backdrop-blur">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14v4.5A1.5 1.5 0 0 0 6.5 20h11a1.5 1.5 0 0 0 1.5-1.5V14"/>
                        </svg>
                    </div>
                    <div>
                        <p class="mb-1 text-sm font-semibold text-teal-100">اكتمل الحجز بنجاح</p>
                        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">تذكرتك جاهزة للتحميل</h1>
                    </div>
                </div>
            </div>

            <div class="grid gap-8 p-6 sm:p-10 md:grid-cols-[1fr_280px] md:items-center">
                <div>
                    <div id="ticket-loading" role="status" aria-live="polite">
                        <div class="mb-5 flex items-center gap-3">
                            <span class="relative flex h-3 w-3">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-500 opacity-50"></span>
                                <span class="relative inline-flex h-3 w-3 rounded-full bg-cyan-600"></span>
                            </span>
                            <span class="text-sm font-bold text-cyan-800">جارٍ تجهيز ملف التذكرة</span>
                        </div>
                        <h2 class="text-xl font-extrabold text-slate-900 sm:text-2xl">لحظات وتكون التذكرة عندك</h2>
                        <p class="mt-3 max-w-xl leading-7 text-slate-500">نجهّز الآن نسخة PDF من التذكرة. لا تغلق الصفحة، سيتم تحويلك تلقائيًا إلى لوحة العمليات بعد بدء التحميل.</p>
                        <div class="mt-7 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="ticket-progress h-full w-1/3 rounded-full bg-gradient-to-l from-cyan-600 to-teal-400"></div>
                        </div>
                        <p class="mt-3 text-xs font-medium text-slate-400">يستغرق ذلك عادةً بضع ثوانٍ</p>
                    </div>

                    <div id="ticket-ready" class="hidden" role="status" aria-live="polite">
                        <div class="mb-5 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-bold text-emerald-700">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.1 7.16a1 1 0 0 1-1.42.006l-3.9-3.84a1 1 0 1 1 1.404-1.426l3.19 3.141 6.394-6.45a1 1 0 0 1 1.426-.005Z" clip-rule="evenodd"/>
                            </svg>
                            بدأ تحميل الملف
                        </div>
                        <h2 class="text-xl font-extrabold text-slate-900 sm:text-2xl">تم تجهيز تذكرتك بنجاح</h2>
                        <p class="mt-3 leading-7 text-slate-500">سيتم تحويلك إلى لوحة العمليات خلال <span id="redirect-countdown" class="font-extrabold text-slate-800">3</span> ثوانٍ.</p>
                    </div>

                    <div id="ticket-error" class="hidden" role="alert">
                        <div class="mb-5 inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1.5 text-sm font-bold text-rose-700">
                            <span aria-hidden="true">!</span>
                            لم يكتمل التحميل
                        </div>
                        <h2 class="text-xl font-extrabold text-slate-900 sm:text-2xl">تعذر تجهيز التذكرة</h2>
                        <p class="mt-3 leading-7 text-slate-500">تحقق من اتصالك بالإنترنت، ثم حاول تنزيل التذكرة مرة أخرى.</p>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <button id="ticket-retry-button" type="button" class="hidden items-center justify-center gap-2 rounded-xl bg-cyan-700 px-5 py-3 font-bold text-white shadow-lg shadow-cyan-900/10 transition hover:-translate-y-0.5 hover:bg-cyan-800 focus:outline-none focus:ring-4 focus:ring-cyan-200">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M5.6 15a7 7 0 1 0 .4-7"/>
                            </svg>
                            حاول التحميل مرة أخرى
                        </button>
                        <a href="{{ route('operatoins.dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 font-bold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100">
                            العودة إلى لوحة العمليات
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.78 15.53a.75.75 0 0 1-1.06 0l-5-5a.75.75 0 0 1 0-1.06l5-5a.75.75 0 1 1 1.06 1.06L8.31 10l4.47 4.47a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <aside class="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
                    <div class="mb-5 flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-600">ملخص التذكرة</span>
                        <span class="rounded-lg bg-white px-2.5 py-1 text-xs font-extrabold text-cyan-800 shadow-sm">PDF</span>
                    </div>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4">
                        <p class="text-xs font-semibold text-slate-400">رقم الحجز</p>
                        <p class="mt-2 break-all font-mono text-lg font-extrabold tracking-wide text-slate-900">{{ $ticket->ticket_code }}</p>
                    </div>
                    <div class="mt-4 flex items-center gap-3 text-sm text-slate-500">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-cyan-700 shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l5 5v11.5H7a2 2 0 0 1-2-2v-12a2 2 0 0 1 2-2.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v5h5M8.5 14h7m-7 3.5h7"/>
                            </svg>
                        </span>
                        <span>نسخة PDF جاهزة للحفظ والطباعة</span>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <style>
        @keyframes ticket-progress {
            0% { transform: translateX(120%); }
            100% { transform: translateX(-320%); }
        }
        .ticket-progress { animation: ticket-progress 1.5s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) {
            .ticket-progress { animation: none; width: 100%; }
        }
    </style>

    <script>
        (() => {
            const loading = document.getElementById('ticket-loading');
            const ready = document.getElementById('ticket-ready');
            const error = document.getElementById('ticket-error');
            const retryButton = document.getElementById('ticket-retry-button');
            const countdown = document.getElementById('redirect-countdown');
            const pdfUrl = @json($pdfUrl);
            const dashboardUrl = @json(route('operatoins.dashboard'));
            const filename = @json($ticket->ticket_code.'.pdf');
            let downloadInProgress = false;
            let redirectTimer;

            const showState = (state) => {
                loading.classList.toggle('hidden', state !== 'loading');
                ready.classList.toggle('hidden', state !== 'ready');
                error.classList.toggle('hidden', state !== 'error');
                retryButton.classList.toggle('hidden', state !== 'error');
                retryButton.classList.toggle('inline-flex', state === 'error');
            };

            const downloadTicket = async () => {
                if (downloadInProgress) return;

                downloadInProgress = true;
                window.clearInterval(redirectTimer);
                showState('loading');

                try {
                    const response = await fetch(pdfUrl, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/pdf' },
                    });

                    if (!response.ok) {
                        throw new Error(`Ticket download failed with status ${response.status}`);
                    }

                    const ticketFile = await response.blob();
                    if (ticketFile.size === 0 || (ticketFile.type && !ticketFile.type.includes('pdf'))) {
                        throw new Error('The ticket response was empty or was not a PDF.');
                    }

                    const objectUrl = URL.createObjectURL(ticketFile);
                    const link = document.createElement('a');
                    link.href = objectUrl;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    window.setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);

                    showState('ready');
                    let secondsRemaining = 3;
                    countdown.textContent = secondsRemaining;
                    redirectTimer = window.setInterval(() => {
                        secondsRemaining -= 1;
                        countdown.textContent = secondsRemaining;
                        if (secondsRemaining <= 0) {
                            window.clearInterval(redirectTimer);
                            window.location.assign(dashboardUrl);
                        }
                    }, 1000);
                } catch (downloadError) {
                    console.error('Unable to download ticket.', downloadError);
                    showState('error');
                } finally {
                    downloadInProgress = false;
                }
            };

            retryButton.addEventListener('click', downloadTicket);
            downloadTicket();
        })();
    </script>
@endsection
