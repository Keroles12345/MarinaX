@foreach($logs as $log)
  <tr>
    <td class="num">{{ $log->at }}</td>
    <td>{{ $log->user }}</td>
    <td><span class="badge region">{{ $log->action }}</span></td>
    <td>{{ $log->details ?: '—' }}</td>
  </tr>
@endforeach
