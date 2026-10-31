@extends('staff.main')

@section('title', 'لوحة الموظف')
@section('page-title', 'لوحة الموظف')

@section('body')
    <div class="quick-actions">
        <a href="{{ route('daily-ops.index') }}" class="btn btn-teal">📋 {{ __('العمليات اليومية') }}</a>
        @can('operations.edit')
            <a href="{{ route('operatoins.dashboard') }}" class="btn btn-gold">🧭 {{ __('اختيار الرحلة') }}</a>
        @endcan
        <a href="{{ route('operation-assignments.index') }}" class="btn btn-dark">👥 {{ __('توزيع العمليات') }}</a>
    </div>

    <div class="cards-grid">
        <div class="card teal">
            <div class="lbl">{{ __('إجمالي الحجوزات في مناطقك') }}</div>
            <div class="val teal">{{ number_format($operationCount) }}</div>
        </div>
        <div class="card">
            <div class="lbl">{{ __('المناطق المسموح بها') }}</div>
            <div class="val gold">{{ number_format($regions->count()) }}</div>
        </div>
    </div>

    @if($regions->isEmpty())
        <div class="panel">
            <div class="panel-body hint">
                {{ __('لا توجد مناطق مرتبطة بحسابك حتى الآن. تواصل مع الإدارة لتحديد المناطق المسموح لك بالعمل عليها.') }}
            </div>
        </div>
    @else
        <div class="grid-2 staff-dashboard-grid">
            <section class="panel">
                <div class="panel-head">
                    <h3>🗺️ {{ __('المناطق المسموح بها') }}</h3>
                </div>
                <div class="panel-body staff-region-list">
                    @foreach($regions as $region)
                        <span class="badge region">{{ $region->name }}</span>
                    @endforeach
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <h3>📋 {{ __('الحجوزات في مناطقك') }}</h3>
                    <span class="hint">{{ __('إجمالي الحجوزات:') }} {{ number_format($operationCount) }}</span>
                </div>
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>{{ __('رقم الحجز') }}</th>
                                <th>{{ __('الفندق') }}</th>
                                <th>{{ __('المنطقة') }}</th>
                                <th>{{ __('تاريخ الرحلة') }}</th>
                                <th>{{ __('الحالة') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($operations as $operation)
                                <tr>
                                    <td class="num">{{ $operation->booking_id }}</td>
                                    <td>{{ $operation->tripHotel?->hotel?->name ?? '—' }}</td>
                                    <td>{{ $operation->tripHotel?->hotel?->region?->name ?? '—' }}</td>
                                    <td class="num">{{ $operation->trip_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ $operation->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <div class="big">📭</div>
                                        {{ __('لا توجد حجوزات في مناطقك حاليًا.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    @endif

    <style>
        .staff-dashboard-grid{grid-template-columns:minmax(240px,.7fr) minmax(0,1.3fr)}
        .staff-region-list{display:flex;flex-wrap:wrap;gap:8px}
        @media(max-width:900px){.staff-dashboard-grid{grid-template-columns:1fr}}
    </style>
@endsection
