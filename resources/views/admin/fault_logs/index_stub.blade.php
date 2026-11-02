<h1>Fault Logs</h1>
<ul>
@foreach($logs['data'] as $log)
    <li>{{ $log['message'] }}</li>
@endforeach
</ul>
