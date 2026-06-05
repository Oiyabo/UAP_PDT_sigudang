param(
    [string]$projectPath = "C:\laragon\www\UTP_PDT_sigudang",
    [string]$phpPath = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe",
    [int]$intervalMinutes = 3
)

$scriptName = "auto_backup.php"
$taskName = "SigudangAutoBackup"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Setup Task Scheduler - Backup Otomatis" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path $projectPath)) {
    Write-Host "ERROR: Project path tidak ditemukan" -ForegroundColor Red
    Write-Host "Path: $projectPath" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $phpPath)) {
    Write-Host "ERROR: PHP executable tidak ditemukan" -ForegroundColor Red
    Write-Host "Path: $phpPath" -ForegroundColor Red
    exit 1
}

$scriptPath = Join-Path $projectPath $scriptName
if (-not (Test-Path $scriptPath)) {
    Write-Host "ERROR: Backup script tidak ditemukan" -ForegroundColor Red
    Write-Host "Path: $scriptPath" -ForegroundColor Red
    exit 1
}

Write-Host "Validasi path: OK" -ForegroundColor Green
Write-Host ""

$task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($task) {
    Write-Host "Task sudah ada. Menghapus task lama..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction Stop
    Write-Host "Task lama dihapus." -ForegroundColor Green
    Write-Host ""
}

Write-Host "Membuat Task Scheduler baru..." -ForegroundColor Cyan

$action = New-ScheduledTaskAction -Execute $phpPath -Argument "`"$scriptPath`""

$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes $intervalMinutes)

$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

try {
    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -RunLevel Highest -Force | Out-Null
    Write-Host "[OK] Task berhasil dibuat!" -ForegroundColor Green
}
catch {
    Write-Host "[ERROR] Error: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Konfigurasi:" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Nama Task      : $taskName" -ForegroundColor White
Write-Host "Script Path    : $scriptPath" -ForegroundColor White
Write-Host "PHP Path       : $phpPath" -ForegroundColor White
Write-Host "Interval       : $intervalMinutes menit" -ForegroundColor White
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Setup Selesai!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Langkah Berikutnya:" -ForegroundColor Cyan
Write-Host "1. Buka web browser: http://localhost/sigudang-main/pages/backup.php" -ForegroundColor White
Write-Host "2. Login sebagai Admin" -ForegroundColor White
Write-Host "3. Klik tombol Mulai Backup Otomatis" -ForegroundColor White
Write-Host "4. Backup akan berjalan setiap $intervalMinutes menit" -ForegroundColor White
Write-Host ""
