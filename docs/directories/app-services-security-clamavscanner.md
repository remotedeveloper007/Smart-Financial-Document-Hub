# `app/Services/Security/ClamAvScanner.php`

## Purpose

Streams a local private file to a ClamAV daemon using the TCP `INSTREAM` protocol.

## Dependencies

- `App\Contracts\MalwareScanner` - implementation boundary.
- PHP stream and file functions - TCP socket, file access, and length-prefixed chunks.
- `services.clamav.host`, `services.clamav.port`, and `services.clamav.timeout` - daemon settings from `config/services.php`.

## Input and output

- Input: absolute path readable by the queue worker.
- Output: exactly `clean` or `infected`.
- Failure: throws `RuntimeException` for unavailable files, connection errors, incomplete writes/reads, timeouts, or unrecognized daemon output.

## Usage

Laravel resolves it through the `MalwareScanner` interface. Configure network access for the queue worker, not only the web process. Do not expose the ClamAV daemon publicly.
