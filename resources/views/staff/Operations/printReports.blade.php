<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>Operations Report</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 18px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1e293b;
            font-size: 10px;
            margin: 0;
        }

        /* Each resource gets its own page */
        .resource-page {
            page-break-after: always;
        }

        .resource-page:last-child {
            page-break-after: auto;
        }

        /* Header */

        .header {
            border-bottom: 2px solid #0f766e;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            width: 70%;
        }

        .header-right {
            width: 30%;
            text-align: right;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 10px;
            color: #64748b;
        }

        .report-label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
        }

        .report-date {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 3px;
        }

        /* Resource Header */

        .resource-header {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            padding: 10px 12px;
            margin-bottom: 14px;
        }

        .resource-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }

        .resource-type {
            margin-top: 3px;
            color: #64748b;
            font-size: 9px;
        }

        /* Summary */

        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px 0;
            margin: 0 -7px 14px;
        }

        .summary td {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            width: 25%;
        }

        .summary-label {
            color: #64748b;
            font-size: 8px;
            text-transform: uppercase;
        }

        .summary-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: bold;
            margin-top: 3px;
        }

        /* Main table */

        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .report-table thead th {
            background: #0f172a;
            color: #ffffff;
            padding: 8px 5px;
            font-size: 8px;
            text-align: left;
            text-transform: uppercase;
            border: 1px solid #0f172a;
        }

        .report-table tbody td {
            padding: 7px 5px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
            font-size: 9px;
            word-wrap: break-word;
        }

        .report-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .report-table tbody tr {
            page-break-inside: avoid;
        }

        /* Columns */

        .col-no {
            width: 4%;
            text-align: center !important;
        }

        .col-booking {
            width: 14%;
        }

        .col-date {
            width: 10%;
        }

        .col-trip {
            width: 13%;
        }

        .col-hotel {
            width: 17%;
        }

        .col-pickup {
            width: 9%;
            text-align: center !important;
        }

        .col-driver {
            width: 13%;
        }

        .col-type {
            width: 6%;
            text-align: center !important;
        }

        /* Text */

        .booking {
            font-weight: bold;
            color: #0f766e;
        }

        .main-text {
            font-weight: bold;
            color: #334155;
        }

        .muted {
            color: #64748b;
            font-size: 8px;
        }

        /* Badges */

        .badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-boat {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-vehicle {
            background: #fef3c7;
            color: #92400e;
        }

        /* Footer */

        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 8px;
        }

        .footer-table {
            width: 100%;
        }

        .footer-right {
            text-align: right;
        }
    </style>
</head>

<body>

    @forelse($groupedAssignments as $resourceKey => $resourceAssignments)

        @php
            $firstAssignment = $resourceAssignments->first();

            $resourceName =
                $resourceNames[$resourceKey]
                ?? 'Not assigned';

            $resourceType =
                $firstAssignment->type === 'boat'
                    ? 'Boat'
                    : ($firstAssignment->type === 'vehicle'
                        ? 'Vehicle'
                        : ucfirst($firstAssignment->type));

            $driverIds = $resourceAssignments
                ->pluck('driver_id')
                ->filter()
                ->unique()
                ->values();

            $firstDriverName =
                $driverNames[$firstAssignment->type][$firstAssignment->driver_id]
                ?? 'No driver';
        @endphp


        {{-- =========================================================
             RESOURCE PAGE
        ========================================================== --}}

        <div class="resource-page">

            {{-- Header --}}

            <div class="header">

                <table class="header-table">

                    <tr>

                        <td class="header-left">

                            <div class="title">
                                Operations Assignment Report
                            </div>

                            <div class="subtitle">
                                Daily operations, resources and driver assignments
                            </div>

                        </td>

                        <td class="header-right">

                            <div class="report-label">
                                Generated
                            </div>

                            <div class="report-date">
                                {{ now()->format('d M Y, H:i') }}
                            </div>

                        </td>

                    </tr>

                </table>

            </div>


            {{-- Resource Information --}}

            <div class="resource-header">

                <div class="resource-name">
                    {{ $resourceName }}
                </div>

                <div class="resource-type">

                    {{ $resourceType }}

                    &nbsp; | &nbsp;

                    {{ $resourceAssignments->count() }}
                    {{ $resourceAssignments->count() === 1 ? 'Operation' : 'Operations' }}

                </div>

            </div>


            {{-- Summary --}}

            <table class="summary">

                <tr>

                    <td>

                        <div class="summary-label">
                            Resource
                        </div>

                        <div class="summary-value">
                            {{ $resourceName }}
                        </div>

                    </td>


                    <td>

                        <div class="summary-label">
                            Type
                        </div>

                        <div class="summary-value">
                            {{ $resourceType }}
                        </div>

                    </td>


                    <td>

                        <div class="summary-label">
                            Total Operations
                        </div>

                        <div class="summary-value">
                            {{ $resourceAssignments->count() }}
                        </div>

                    </td>


                    <td>

                        <div class="summary-label">
                            Assignment Date
                        </div>

                        <div class="summary-value">

                            {{ $firstAssignment->assign_date
                                ? \Carbon\Carbon::parse($firstAssignment->assign_date)->format('d M Y')
                                : 'All dates' }}

                        </div>

                    </td>

                </tr>

            </table>


            {{-- Main Report Table --}}

            <table class="report-table">

                <thead>

                    <tr>

                        <th class="col-no">
                            #
                        </th>

                        <th class="col-booking">
                            Booking
                        </th>

                        <th class="col-date">
                            Trip Date
                        </th>

                        <th class="col-trip">
                            Trip Time
                        </th>

                        <th class="col-hotel">
                            Hotel
                        </th>

                        <th class="col-pickup">
                            Pickup
                        </th>

                        <th class="col-driver">
                            Driver
                        </th>

                        <th class="col-type">
                            Type
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($resourceAssignments as $index => $assignment)

                        @php

                            $driverName =
                                $driverNames[$assignment->type][$assignment->driver_id]
                                ?? 'No driver';

                        @endphp


                        <tr>

                            {{-- Number --}}

                            <td class="col-no">

                                {{ $index + 1 }}

                            </td>


                            {{-- Booking --}}

                            <td>

                                <div class="booking">

                                    {{ $assignment->booking_id }}

                                </div>

                            </td>


                            {{-- Trip Date --}}

                            <td>

                                <div class="main-text">

                                    {{ $assignment->trip_date
                                        ? \Carbon\Carbon::parse($assignment->trip_date)->format('d M Y')
                                        : '-' }}

                                </div>

                            </td>


                            {{-- Trip Time --}}

                            <td>

                                <div class="main-text">

                                    {{ $assignment->trip_time ?: '-' }}

                                </div>

                                @if($assignment->time_pickup)

                                    <div class="muted">

                                        Pickup:
                                        {{ $assignment->time_pickup }}

                                    </div>

                                @endif

                            </td>


                            {{-- Hotel --}}

                            <td>

                                <div class="main-text">

                                    {{ $assignment->hotel_name ?: 'No hotel' }}

                                </div>

                            </td>


                            {{-- Pickup --}}

                            <td class="col-pickup">

                                <div class="main-text">

                                    {{ $assignment->pickup_time ?: '-' }}

                                </div>

                            </td>


                            {{-- Driver --}}

                            <td>

                                <div class="main-text">

                                    {{ $driverName }}

                                </div>

                                @if($assignment->driver_id)

                                    <div class="muted">

                                        ID:
                                        {{ $assignment->driver_id }}

                                    </div>

                                @endif

                            </td>


                            {{-- Type --}}

                            <td class="col-type">

                                @if($assignment->type === 'boat')

                                    <span class="badge badge-boat">
                                        Boat
                                    </span>

                                @elseif($assignment->type === 'vehicle')

                                    <span class="badge badge-vehicle">
                                        Vehicle
                                    </span>

                                @else

                                    <span class="badge">
                                        {{ $assignment->type }}
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>


            {{-- Footer --}}

            <div class="footer">

                <table class="footer-table">

                    <tr>

                        <td>

                            Operations Assignment Report
                            —
                            {{ $resourceName }}

                        </td>

                        <td class="footer-right">

                            Page generated
                            {{ now()->format('d M Y') }}

                        </td>

                    </tr>

                </table>

            </div>

        </div>

    @empty

        {{-- No Results --}}

        <div style="text-align: center; padding: 80px 20px;">

            <div style="font-size: 20px; font-weight: bold; color: #0f172a;">
                No operation assignments found.
            </div>

            <div style="margin-top: 8px; color: #64748b;">
                There are no operations matching the selected filters.
            </div>

        </div>

    @endforelse

</body>

</html>
