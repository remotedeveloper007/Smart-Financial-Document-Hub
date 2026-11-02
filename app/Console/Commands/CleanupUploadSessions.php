<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Team;

class CleanupUploadSessions extends Command
{
    protected $signature = 'uploads:cleanup {--hours=24}';

    protected $description = 'Cleanup stale upload sessions and temporary chunks older than given hours';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = Carbon::now()->subHours($hours);

        $teams = Team::all();
        foreach ($teams as $team) {
            $prefix = "tenant-{$team->id}/uploads/sessions";
            if (! Storage::disk('local')->exists($prefix)) {
                continue;
            }
            $files = Storage::disk('local')->files($prefix);
            foreach ($files as $file) {
                $path = storage_path('app/' . $file);
                if (! file_exists($path)) {
                    continue;
                }
                $mtime = Carbon::createFromTimestamp(filemtime($path));
                if ($mtime->lessThan($cutoff)) {
                    $this->info("Cleaning session: {$file}");
                    $sessionId = pathinfo($file, PATHINFO_FILENAME);
                    // remove tmp dir for this tenant/session
                    Storage::disk('local')->deleteDirectory("tenant-{$team->id}/uploads/tmp/{$sessionId}");
                    // remove assembled file(s) for this session
                    $assembledDir = "tenant-{$team->id}/uploads/assembled";
                    $all = Storage::disk('local')->files($assembledDir);
                    foreach ($all as $a) {
                        if (str_contains($a, $sessionId . '_')) {
                            Storage::disk('local')->delete($a);
                        }
                    }
                    // remove session file
                    Storage::disk('local')->delete($file);
                }
            }
        }

        return 0;
    }
}
