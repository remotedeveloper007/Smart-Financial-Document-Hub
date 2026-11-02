**Deployment: Worker Services and Local Helpers**

Purpose:
- Provide ready-to-use service unit files and Windows helper script to run Horizon and queue workers on low-resource hosts without Docker.

Files added:
- `deploy/systemd/horizon.service` — systemd unit to run `php artisan horizon`.
- `deploy/systemd/laravel-queue.service` — systemd unit to run `php artisan queue:work database`.
- `scripts/windows/register-laravel-queue-task.ps1` — PowerShell script to register a Task Scheduler entry to start the queue worker at system startup.

Usage (Linux)

1. Copy the files to `/etc/systemd/system/` on the server and reload systemd:

```bash
sudo cp deploy/systemd/horizon.service /etc/systemd/system/horizon.service
sudo cp deploy/systemd/laravel-queue.service /etc/systemd/system/laravel-queue.service
sudo systemctl daemon-reload
sudo systemctl enable horizon.service
sudo systemctl enable laravel-queue.service
sudo systemctl start horizon.service
sudo systemctl start laravel-queue.service
```

2. Check logs:

```bash
journalctl -u horizon.service -f
journalctl -u laravel-queue.service -f
```

Usage (Windows)

1. Edit `scripts/windows/register-laravel-queue-task.ps1` if needed to point to `php.exe` and artisan path.
2. Run as Administrator:

```powershell
.\scripts\windows\register-laravel-queue-task.ps1
```

3. Verify Task Scheduler has the `LaravelQueueWorker` task and check its history/logs.

Notes

- These are examples; tune `ExecStart` and user/group according to your environment (www-data is common on Linux servers).
- For Horizon, prefer running on Linux hosts due to required PHP extensions.
- For low-resource hosts, prefer `database` queue connection as documented in `docs/features/redis-horizon.md`.
