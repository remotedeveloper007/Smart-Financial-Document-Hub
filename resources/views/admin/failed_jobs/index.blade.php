@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Failed Jobs</h1>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Connection</th>
                <th>Queue</th>
                <th>Exception</th>
                <th>Failed At</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($jobs as $job)
            <tr>
                <td>{{ $job->id }}</td>
                <td>{{ $job->connection }}</td>
                <td>{{ $job->queue }}</td>
                <td>{{ Str::limit($job->exception, 80) }}</td>
                <td>{{ $job->failed_at }}</td>
                <td>
                    <a href="{{ route('admin.failed_jobs.show', $job->id) }}" class="btn btn-sm btn-primary">View</a>
                    <form method="POST" action="{{ route('admin.failed_jobs.retry', $job->id) }}" style="display:inline">
                        @csrf
                        <button class="btn btn-sm btn-success">Retry</button>
                    </form>
                    <form method="POST" action="{{ route('admin.failed_jobs.destroy', $job->id) }}" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this failed job?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{ $jobs->links() }}
</div>
@endsection
