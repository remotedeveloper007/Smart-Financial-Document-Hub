param(
    [string]$TaskName = "LaravelQueueWorker",
    [string]$PhpPath = "php",
    [string]$ArtisanPath = "artisan",
    [string]$Arguments = "queue:work database --sleep=3 --tries=3 --queue=default,high,ocr,exports"
)

$action = New-ScheduledTaskAction -Execute $PhpPath -Argument ("$ArtisanPath " + $Arguments)
$trigger = New-ScheduledTaskTrigger -AtStartup
$principal = New-ScheduledTaskPrincipal -UserId "NT AUTHORITY\SYSTEM" -RunLevel Highest
Register-ScheduledTask -Action $action -Trigger $trigger -TaskName $TaskName -Principal $principal -Description "Start Laravel queue worker at startup"

Write-Output "Registered scheduled task '$TaskName' to run: $PhpPath $ArtisanPath $Arguments"
