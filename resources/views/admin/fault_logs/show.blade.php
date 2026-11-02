@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Fault Log #{{ $log->id }}</h1>

    <a href="{{ route('admin.fault_logs.index') }}" class="btn btn-secondary">Back</a>

    <table class="table mt-3">
        <tbody>
            <tr><th>Severity</th><td>{{ $log->severity }}</td></tr>
            <tr><th>Message</th><td><pre>{{ $log->message }}</pre></td></tr>
            <tr><th>Stack Trace</th><td><pre style="white-space:pre-wrap">{{ $log->stack_trace }}</pre></td></tr>
            <tr><th>File</th><td>{{ $log->file_path }}:{{ $log->line_number }}</td></tr>
            <tr><th>Root Cause</th><td>{{ $log->root_cause }}</td></tr>
            <tr><th>Recommended Fix</th><td>{{ $log->recommended_fix }}</td></tr>
            <tr><th>Status</th><td>{{ $log->status }}</td></tr>
        </tbody>
    </table>
</div>
@endsection
