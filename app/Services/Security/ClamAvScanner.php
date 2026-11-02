<?php

namespace App\Services\Security;

use App\Contracts\MalwareScanner;
use RuntimeException;

final class ClamAvScanner implements MalwareScanner
{
    public function scan(string $filePath): string
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException('The uploaded file is unavailable to the malware scanner.');
        }

        $host = (string) config('services.clamav.host');
        $port = (int) config('services.clamav.port');
        $timeout = (float) config('services.clamav.timeout', 10);
        $socket = @stream_socket_client(
            sprintf('tcp://%s:%d', $host, $port),
            $errorCode,
            $errorMessage,
            $timeout,
        );

        if (! is_resource($socket)) {
            throw new RuntimeException('The malware scanning service is unavailable.');
        }

        stream_set_timeout($socket, (int) ceil($timeout));
        $file = fopen($filePath, 'rb');

        if (! is_resource($file)) {
            fclose($socket);
            throw new RuntimeException('The uploaded file could not be opened for scanning.');
        }

        try {
            $this->writeAll($socket, "INSTREAM\n");

            while (! feof($file)) {
                $chunk = fread($file, 8192);

                if ($chunk === false) {
                    throw new RuntimeException('The uploaded file could not be read completely by the scanner.');
                }

                if ($chunk !== '') {
                    $this->writeAll($socket, pack('N', strlen($chunk)).$chunk);
                }
            }

            $this->writeAll($socket, pack('N', 0));
            stream_socket_shutdown($socket, STREAM_SHUT_WR);
            $response = fgets($socket, 4096);

            if ($response === false || (stream_get_meta_data($socket)['timed_out'] ?? false)) {
                throw new RuntimeException('The malware scanner did not return a result.');
            }

            if (preg_match('/:\s+OK\s*$/', trim($response)) === 1) {
                return 'clean';
            }

            if (preg_match('/:\s+.+\sFOUND\s*$/', trim($response)) === 1) {
                return 'infected';
            }

            throw new RuntimeException('The malware scanner returned an unrecognized result.');
        } finally {
            fclose($file);
            fclose($socket);
        }
    }

    private function writeAll(mixed $stream, string $data): void
    {
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            $written = fwrite($stream, substr($data, $offset));

            if ($written === false || $written === 0) {
                throw new RuntimeException('The malware scanner connection ended unexpectedly.');
            }

            $offset += $written;
        }
    }
}
