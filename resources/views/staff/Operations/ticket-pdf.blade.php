
    <!-- Header -->


    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 30px;
            background: #eef1f4;
            color: #17324a;
        }

        .wrapper {
            width: 100%;
            max-width: 1050px;
            margin: auto;
        }

        .ticket-card {
            display: table;
            width: 100%;
            table-layout: fixed;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.10);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .ticket-row {
            display: table-row;
        }

        /* DomPDF doesn't support linear-gradient(), so use a solid color.
           Pass $backgroundImage (base64 data URI) to layer a real photo on top. */
        .photo-panel {
            display: table-cell;
            width: 23%;
            vertical-align: bottom;
            position: relative;
            background-color: #0f5f89;
            @if(!empty($backgroundImage))
            background-image: url("{{ $backgroundImage }}");
            background-size: cover;
            background-position: center;
            @endif
            padding: 24px 20px;
            height: 300px;
        }

        .brand-logo { color: #ffffff; }
        .brand-logo .palm-icon { margin-bottom: 8px; }
        .brand-logo .brand-name { font-size: 17px; font-weight: bold; letter-spacing: 0.3px; }
        .brand-logo .brand-tagline { font-size: 9px; letter-spacing: 1.2px; text-transform: uppercase; opacity: 0.85; margin-top: 2px; }

        .info-panel {
            display: table-cell;
            width: 42%;
            vertical-align: top;
            padding: 24px 24px;
            border-right: 2px dashed #cbd5e0;
        }

        .eyebrow { font-size: 11px; font-weight: bold; letter-spacing: 1.5px; color: #0b6e99; text-transform: uppercase; margin-bottom: 6px; }
        .trip-title { font-size: 20px; font-weight: bold; color: #0f2e4a; margin: 0 0 4px; line-height: 1.25; }
        .trip-subtitle { font-size: 11px; color: #7c8a9a; margin-bottom: 16px; }

        .detail-rows { display: table; width: 100%; }
        .detail-row { display: table-row; }
        .detail-icon-cell { display: table-cell; width: 24px; padding: 8px 0; vertical-align: top; }
        .detail-text-cell { display: table-cell; padding: 8px 0 8px 8px; vertical-align: top; }
        .detail-label { display: block; font-size: 9px; color: #8a94a3; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-value { font-size: 13px; font-weight: bold; color: #17324a; }

        .enjoy-banner { margin-top: 14px; background: #eef6fb; border-radius: 20px; padding: 9px 14px; font-size: 11px; font-weight: bold; color: #0b6e99; text-align: center; }
        .enjoy-banner img { vertical-align: middle; margin-right: 4px; }

        .meta-panel { display: table-cell; width: 20%; vertical-align: top; padding: 24px 16px; }
        .meta-block { margin-bottom: 14px; }
        .meta-label { font-size: 9px; color: #8a94a3; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .meta-value { font-size: 12px; font-weight: bold; color: #0b6e99; word-wrap: break-word; }

        .qr-panel { display: table-cell; width: 15%; vertical-align: middle; text-align: center; background: #eaf3fa; padding: 20px 14px; position: relative; }
        .qr-eyebrow { font-size: 10px; font-weight: bold; letter-spacing: 0.5px; color: #0b6e99; text-transform: uppercase; margin-bottom: 12px; }
        .qr-frame { display: inline-block; background: #ffffff; padding: 8px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08); }
        .qr-frame img { display: block; width: 100px; height: 100px; }
        .qr-code-text { margin-top: 10px; font-size: 10px; font-weight: bold; color: #17324a; letter-spacing: 0.3px; word-break: break-all; }

        .notch { position: absolute; width: 20px; height: 20px; background: #eef1f4; border-radius: 50%; z-index: 2; }
        .notch-top { top: -10px; left: -10px; }
        .notch-bottom { bottom: -10px; left: -10px; }

        .footer-note { text-align: center; font-size: 11px; color: #8a94a3; margin-top: 14px; }
    </style>


<body>

@php
    $tripDate = $ticket->operation->trip_date
        ? \Carbon\Carbon::parse($ticket->operation->trip_date)->format('d M Y')
        : '';
    $tripTime = $ticket->operation->trip_time
        ? \Carbon\Carbon::parse($ticket->operation->trip_time)->format('h:i A')
        : '';

    $guestsParts = [];
    $guestsParts[] = $ticket->operation->adults . ' Adult' . ($ticket->operation->adults > 1 ? 's' : '');
    if ($ticket->operation->children > 0) {
        $guestsParts[] = $ticket->operation->children . ' Child' . ($ticket->operation->children > 1 ? 'ren' : '');
    }
    if ($ticket->operation->infants > 0) {
        $guestsParts[] = $ticket->operation->infants . ' Infant' . ($ticket->operation->infants > 1 ? 's' : '');
    }
    $guestsText = implode(', ', $guestsParts);
@endphp

<div class="wrapper">

    <div class="ticket-card">
        <div class="ticket-row">

            {{-- Left: destination photo + brand --}}
            <div class="photo-panel">
                <div class="brand-logo">
                    <div class="palm-icon">
                        <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiBmaWxsPSJub25lIiBzdHJva2U9IiNmZmZmZmYiIHN0cm9rZS13aWR0aD0iMS42IiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiPjxwYXRoIGQ9Ik0xMiAyMlYxMiIvPjxwYXRoIGQ9Ik0xMiAxMmMwLTQtMy02LTctNiAwIDQgMyA2IDcgNnoiLz48cGF0aCBkPSJNMTIgMTJjMC00IDMtNiA3LTYgMCA0LTMgNi03IDZ6Ii8+PHBhdGggZD0iTTEyIDEyYzAtNS0yLTgtNS05IDMtMSA2IDEgNSA5eiIvPjxwYXRoIGQ9Ik0xMiAxMmMwLTUgMi04IDUtOS0zLTEtNiAxLTUgOXoiLz48L3N2Zz4=" width="30" height="30" alt="">
                    </div>
                    <div class="brand-name">{{ $companyName ?? 'Sun Travel' }}</div>
                    <div class="brand-tagline">Explore the World</div>
                </div>
            </div>

            {{-- Middle: trip details --}}
            <div class="info-panel">
                <div class="eyebrow">Tour Ticket</div>
                <div class="trip-title">{{ $ticket->operation->trip_name ?? 'Hurghada Speed Trip' }}</div>
                <div class="trip-subtitle">Discover the beauty of the Red Sea</div>

                <div class="detail-rows">
                    <div class="detail-row">
                        <div class="detail-icon-cell">
                            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjE4IiBoZWlnaHQ9IjE4IiBmaWxsPSJub25lIiBzdHJva2U9IiMwYjZlOTkiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cmVjdCB4PSIzIiB5PSI0IiB3aWR0aD0iMTgiIGhlaWdodD0iMTgiIHJ4PSIyIi8+PGxpbmUgeDE9IjE2IiB5MT0iMiIgeDI9IjE2IiB5Mj0iNiIvPjxsaW5lIHgxPSI4IiB5MT0iMiIgeDI9IjgiIHkyPSI2Ii8+PGxpbmUgeDE9IjMiIHkxPSIxMCIgeDI9IjIxIiB5Mj0iMTAiLz48L3N2Zz4=" width="16" height="16" alt="">
                        </div>
                        <div class="detail-text-cell">
                            <span class="detail-label">Date</span>
                            <span class="detail-value">{{ $tripDate }}</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon-cell">
                            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjE4IiBoZWlnaHQ9IjE4IiBmaWxsPSJub25lIiBzdHJva2U9IiMwYjZlOTkiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48Y2lyY2xlIGN4PSIxMiIgY3k9IjEyIiByPSI5Ii8+PHBvbHlsaW5lIHBvaW50cz0iMTIgNyAxMiAxMiAxNS41IDE0Ii8+PC9zdmc+" width="16" height="16" alt="">
                        </div>
                        <div class="detail-text-cell">
                            <span class="detail-label">Time</span>
                            <span class="detail-value">{{ $tripTime }}</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon-cell">
                            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjE4IiBoZWlnaHQ9IjE4IiBmaWxsPSJub25lIiBzdHJva2U9IiMwYjZlOTkiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMjEgMTBjMCA2LTkgMTItOSAxMnMtOS02LTktMTJhOSA5IDAgMCAxIDE4IDB6Ii8+PGNpcmNsZSBjeD0iMTIiIGN5PSIxMCIgcj0iMyIvPjwvc3ZnPg==" width="16" height="16" alt="">
                        </div>
                        <div class="detail-text-cell">
                            <span class="detail-label">Location</span>
                            <span class="detail-value">{{ $ticket->operation->location ?? 'Hurghada Marina' }}</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-icon-cell">
                            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjE4IiBoZWlnaHQ9IjE4IiBmaWxsPSJub25lIiBzdHJva2U9IiMwYjZlOTkiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMTcgMjF2LTJhNCA0IDAgMCAwLTQtNEg1YTQgNCAwIDAgMC00IDR2MiIvPjxjaXJjbGUgY3g9IjkiIGN5PSI3IiByPSI0Ii8+PHBhdGggZD0iTTIzIDIxdi0yYTQgNCAwIDAgMC0zLTMuODciLz48cGF0aCBkPSJNMTYgMy4xM2E0IDQgMCAwIDEgMCA3Ljc1Ii8+PC9zdmc+" width="16" height="16" alt="">
                        </div>
                        <div class="detail-text-cell">
                            <span class="detail-label">Guests</span>
                            <span class="detail-value">{{ $guestsText }}</span>
                        </div>
                    </div>
                </div>

                <div class="enjoy-banner">
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBmaWxsPSJub25lIiBzdHJva2U9IiMwYjZlOTkiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMiAyMGEyLjQgMi40IDAgMCAwIDIgMSAyLjQgMi40IDAgMCAwIDItMSAyLjQgMi40IDAgMCAxIDItMSAyLjQgMi40IDAgMCAxIDIgMSAyLjQgMi40IDAgMCAwIDIgMSAyLjQgMi40IDAgMCAwIDItMSAyLjQgMi40IDAgMCAxIDItMSAyLjQgMi40IDAgMCAxIDIgMSAyLjQgMi40IDAgMCAwIDIgMSAyLjQgMi40IDAgMCAwIDItMSIvPjxwYXRoIGQ9Ik00IDE4bC0xLTRoMThsLTIgNCIvPjxwYXRoIGQ9Ik01IDE0bDEtOGg2djgiLz48cGF0aCBkPSJNMTIgNmg1bDMgOCIvPjwvc3ZnPg==" width="14" height="14" alt="">
                    Enjoy your trip!
                </div>
            </div>

            {{-- Booking meta --}}
            <div class="meta-panel">
                <div class="meta-block">
                    <div class="meta-label">Booking No.</div>
                    <div class="meta-value">{{ $ticket->ticket_code }}</div>
                </div>
                <div class="meta-block">
                    <div class="meta-label">Company</div>
                    <div class="meta-value">{{ $companyName ?? 'Sun Travel' }}</div>
                </div>
                <div class="meta-block">
                    <div class="meta-label">Trip</div>
                    <div class="meta-value">{{ $ticket->operation->trip_name ?? 'Hurghada Speed Trip' }}</div>
                </div>
            </div>

            {{-- Right: QR code --}}
            <div class="qr-panel">
                <div class="notch notch-top"></div>

                <div class="qr-eyebrow">Scan to Verify</div>
                <div class="qr-frame">
                    <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="Ticket verification QR code">
                </div>
                <div class="qr-code-text">{{ $ticket->ticket_code }}</div>

                <div class="notch notch-bottom"></div>
            </div>

        </div>
    </div>

    <div class="footer-note">Please keep this ticket and show it when requested.</div>

</div>


