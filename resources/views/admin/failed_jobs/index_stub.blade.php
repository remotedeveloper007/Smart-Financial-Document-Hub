<h1>Failed Jobs</h1>
<ul>
@foreach($jobs['data'] as $job)
    <li>{{ $job['exception'] }}</li>
@endforeach
</ul>
