@foreach($entries as $entry)
  <tr>
    <td>{{ $entry->entry_date }}</td>
    <td>{{ $entry->booking_id ?? '—' }}</td>
    <td>{{ $entry->movement_no }}</td>
    <td>{{ $entry->description ?: '—' }}</td>
    <td>{{ number_format((float) $entry->revenue_amount, 2) }} ج.م</td>
  </tr>
@endforeach
