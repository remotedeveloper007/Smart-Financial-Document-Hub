**ClamAV Integration**

- **Purpose:** Provide a local and CI ClamAV service for virus-scanning uploaded files.
- **Business reason:** Ensure uploaded files are scanned for malware before processing.

Setup
----

1. Start ClamAV locally:

```bash
docker-compose -f docker-compose.clamav.yml up -d
```

2. Enable scanning in your environment (for local dev or CI):

Add to `.env` or export:

```
CLAMAV_ENABLED=true
```

Running tests
-------------

- The repository includes a GitHub Actions workflow `.github/workflows/clamav-integration.yml` which starts the ClamAV service and runs integration tests that exercise the scanning job.

Developer notes
---------------

- The docker-compose service exposes `3310` (clamd) on localhost. The app's `ScanUploadedFileJob` calls `clamscan`/`clamdscan` binaries; in CI we rely on the container providing the daemon on TCP.
- If your local environment lacks the `clamdscan` binary, you can still run the container and use `clamdscan --fdpass` inside the container or adapt the job to use `clamd` socket.

Security
--------

- Do not enable `CLAMAV_ENABLED` in developer branches without the service running, otherwise the job will fallback to heuristic scanning.

Troubleshooting
---------------

- If the workflow times out waiting for port 3310, check runner networking or container logs:

```bash
docker-compose -f docker-compose.clamav.yml logs clamav
```
