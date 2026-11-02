<h1>System Health</h1>
<div>
    <p>Critical: {{ $summary['critical_faults'] ?? 0 }}</p>
    <p>Failed Jobs: {{ $summary['failed_jobs'] ?? 0 }}</p>
</div>
