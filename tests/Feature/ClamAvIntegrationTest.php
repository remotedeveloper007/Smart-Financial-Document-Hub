<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ScanUploadedFileJob;
use App\Services\Security\ClamAvScanner;

class ClamAvIntegrationTest extends TestCase
{
    public function test_eicar_detected_by_clamd()
    {
        if (! $this->canConnectClamd()) {
            $this->markTestSkipped('clamd not available on localhost:3310');
        }

        Storage::fake('local');
        config(['services.clamav.enabled' => true]);

        $eicar = 'X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';
        $path = 'tenant-1/uploads/assembled/eicar.txt';
        Storage::disk('local')->put($path, $eicar);

        // Run the job synchronously
        $job = new ScanUploadedFileJob($path);
        app()->call([$job, 'handle']);

        $this->assertTrue(Storage::disk('local')->exists($path . '.scan.json'));
        $meta = json_decode(Storage::disk('local')->get($path . '.scan.json'), true);
        $this->assertNotNull($meta);
        $this->assertEquals('quarantined', $meta['status']);
    }

    private function canConnectClamd(): bool
    {
        $fp = @stream_socket_client('tcp://127.0.0.1:3310', $errno, $errstr, 1);
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    }
}
