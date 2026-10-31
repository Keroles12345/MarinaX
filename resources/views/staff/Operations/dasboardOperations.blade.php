@extends('staff.main')

@section('body')
    <!-- Header -->
    <!-- Main -->
    <main class="max-w-7xl mx-auto px-6 py-10">
{{-- Error Message --}}


<script>
    function closeAlert(id) {
        const alert = document.getElementById(id);

        if (!alert) return;

        alert.classList.add('opacity-0', 'translate-y-[-10px]');

        setTimeout(() => {
            alert.remove();
        }, 300);
    }


    // Hide alerts automatically after 5 seconds
    document.addEventListener('DOMContentLoaded', function () {

        const alerts = [
            'error-alert',
            'success-alert'
        ];

        alerts.forEach(function (id) {

            const alert = document.getElementById(id);

            if (alert) {

                setTimeout(function () {
                    closeAlert(id);
                }, 5000);

            }

        });

    });
</script>

        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800">
                Choose Your Trip
            </h2>

            <p class="text-gray-500 mt-2">
                Select the trip you want to work with.
            </p>
        </div>


        @if($trips->count())

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                @foreach ($trips as $item)

                    <div class="group bg-white rounded-2xl border border-gray-200
                                shadow-sm hover:shadow-xl hover:-translate-y-1
                                transition-all duration-300 overflow-hidden">

                        <!-- Card Top -->
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6">

                            <div class="flex items-center justify-between">

                                <div class="w-12 h-12 rounded-xl bg-white/20
                                            flex items-center justify-center">

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="w-6 h-6 text-white"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor">

                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>

                                </div>

                                <span class="text-xs font-semibold
                                             bg-white/20 text-white
                                             px-3 py-1 rounded-full">
                                    Active
                                </span>

                            </div>

                        </div>


                        <!-- Card Body -->
                        <div class="p-6">

                            <h3 class="text-xl font-bold text-gray-800 mb-2">
                                {{ $item->name }}
                            </h3>

                            <p class="text-sm text-gray-500 mb-5">
                                {{ $item->description ?? 'No description available' }}
                            </p>


                            <!-- Trip Time -->
                            <div class="flex items-center gap-3 mb-5">

                                <div class="w-10 h-10 rounded-lg bg-gray-100
                                            flex items-center justify-center">

                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="w-5 h-5 text-gray-600"
                                         fill="none"
                                         viewBox="0 0 24 24"
                                         stroke="currentColor">

                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>

                                </div>

                                <div>
                                    <p class="text-xs text-gray-400">
                                        Departure
                                    </p>

                                    <p class="font-semibold text-gray-700">
                                        {{ $item->time_trip ?? 'Not set' }}
                                    </p>
                                </div>

                            </div>


                            <!-- Select Button -->
                            <a href="{{ route('create.operations',$item->name) }}"
                               class="w-full inline-flex items-center justify-center
                                      gap-2 bg-gray-900 text-white
                                      py-3 rounded-xl font-semibold
                                      hover:bg-blue-600
                                      transition duration-200">

                                Select Trip

                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="w-5 h-5"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 5l7 7-7 7"/>

                                </svg>

                            </a>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <!-- Empty State -->
            <div class="bg-white rounded-2xl border border-gray-200
                        p-12 text-center">

                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100
                            flex items-center justify-center mb-5">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-8 h-8 text-gray-400"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414A2 2 0 0120 8.414V19a2 2 0 01-2 2z"/>

                    </svg>

                </div>

                <h3 class="text-xl font-bold text-gray-800">
                    No trips available
                </h3>

                <p class="text-gray-500 mt-2">
                    There are currently no trips available for operations.
                </p>

            </div>

        @endif

    </main>
@endsection
