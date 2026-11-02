@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Failed Job #{{ $job->id }}</h1>

    <a href="{{ route('admin.failed_jobs.index') }}" class="btn btn-secondary">Back</a>

    <table class="table mt-3">
        <tbody>
            <tr><th>Connection</th><td>{{ $job->connection }}</td></tr>
            <tr><th>Queue</th><td>{{ $job->queue }}</td></tr>
            <tr><th>Payload</th><td><pre style="white-space:pre-wrap">{{ $job->payload }}</pre></td></tr>
            <tr><th>Exception</th><td><pre style="white-space:pre-wrap">{{ $job->exception }}</pre></td></tr>
            <tr><th>Failed At</th><td>{{ $job->failed_at }}</td></tr>
        </tbody>
    </table>

    <form method="POST" action="{{ route('admin.failed_jobs.retry', $job->id) }}">
        @csrf
        <button class="btn btn-success">Retry</button>
    </form>

</div>
@endsection
