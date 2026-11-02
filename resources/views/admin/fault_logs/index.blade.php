@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Fault Logs</h1>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Severity</th>
                <th>Message</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->id }}</td>
                <td>{{ $log->severity }}</td>
                <td>{{ Str::limit($log->message, 80) }}</td>
                <td>{{ $log->created_at }}</td>
                <td>
                    <a href="{{ route('admin.fault_logs.show', $log->id) }}" class="btn btn-sm btn-primary">View</a>
                    <form method="POST" action="{{ route('admin.fault_logs.destroy', $log->id) }}" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this fault log?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $logs->links() }}
</div>
@endsection
