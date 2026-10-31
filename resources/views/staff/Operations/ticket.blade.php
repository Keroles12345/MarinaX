

    <style>
        body {
            margin: 0;
            padding: 24px;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, sans-serif;
        }

        .ticket {
            width: min(100%, 620px);
            margin: 0 auto;
            padding: 28px;
            box-sizing: border-box;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.10);
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #0891b2;
            padding-bottom: 18px;
            margin-bottom: 18px;
        }

        .header h1 {
            margin: 0 0 8px;
            color: #075985;
            font-size: 24px;
        }

        .ticket-code {
            font-weight: bold;
            color: #334155;
        }

  .status {
    margin-bottom: 18px;
    padding: 10px 14px;
    border-radius: 8px;
    font-weight: bold;
    text-align: center;
}

.status.active {
    background: #dcfce7;
    color: #166534;
}

.status.inactive {
    background: #fee2e2;
    color: #991b1b;
}
        .row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 11px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .label {
            font-weight: bold;
            color: #475569;
        }

        .qr {
            margin-top: 26px;
            text-align: center;
        }

        .qr img {
            display: block;
            width: 200px;
            height: 200px;
            margin: 0 auto 10px;
        }

        .qr p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }
    </style>


    <main class="ticket">
        <header class="header">
            <h1>HURGHADA SPEED TRIP</h1>
            <div class="ticket-code">Ticket: {{ $ticket->ticket_code }}</div>
        </header>
<div class="status {{ strtolower($ticket->status) === 'active' ? 'active' : 'inactive' }}">
    Ticket status: {{ ucfirst($ticket->status) }}
</div>

        <div class="row">
            <span class="label">Operation ID</span>
            <span>{{ $ticket->operation->id }}</span>
        </div>
        <div class="row">
            <span class="label">Trip date</span>
            <span>{{ $ticket->operation->trip_date }}</span>
        </div>
        <div class="row">
            <span class="label">Trip time</span>
            <span>{{ $ticket->operation->trip_time }}</span>
        </div>
        <div class="row">
            <span class="label">Adults</span>
            <span>{{ $ticket->operation->adults }}</span>
        </div>
        <div class="row">
            <span class="label">Children</span>
            <span>{{ $ticket->operation->children }}</span>
        </div>
        <div class="row">
            <span class="label">Infants</span>
            <span>{{ $ticket->operation->infants }}</span>
        </div>
        <div class="row">
            <span class="label">Total</span>
            <span>{{ $ticket->operation->amount_total }}</span>
        </div>

        <div class="qr">
            <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="Ticket verification QR code">
            <p>Scan this QR code to verify the ticket.</p>
        </div>
    </main>

