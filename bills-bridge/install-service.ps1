# Script para instalar BillsBridge como un servicio en Windows en segundo plano
# Debe ser ejecutado como Administrador.

$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$exePath = Join-Path $scriptPath "dist\billsbridge.exe"

# Si no existe en dist, buscar en el directorio actual
if (-not (Test-Path $exePath)) {
    $exePath = Join-Path $scriptPath "billsbridge.exe"
}

if (-not (Test-Path $exePath)) {
    Write-Error "No se encontró billsbridge.exe. Asegúrate de compilar la aplicación primero usando 'npm run build'."
    exit 1
}

# Verificar privilegios de administrador
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Warning "Este script requiere privilegios de Administrador para instalar el servicio en segundo plano."
    Write-Warning "Por favor, abre PowerShell como Administrador y vuelve a ejecutar este script."
    exit 1
}

Write-Host "Instalando BillsBridge desde: $exePath" -ForegroundColor Green

# Nombre de la tarea
$taskName = "BillsBridge"

# Crear la acción (ejecutar el exe)
$action = New-ScheduledTaskAction -Execute $exePath -WorkingDirectory $scriptPath

# Crear el disparador (al iniciar el equipo)
$trigger = New-ScheduledTaskTrigger -AtStartup

# Configuraciones (ejecutar con corriente y batería, reiniciar si falla)
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

# Registrar la tarea en el programador para que corra como SYSTEM (en segundo plano, invisible, sin requerir sesión iniciada)
Register-ScheduledTask -TaskName $taskName -Trigger $trigger -Action $action -Settings $settings -User "SYSTEM" -Force

# Iniciar la tarea inmediatamente
Start-ScheduledTask -TaskName $taskName

Write-Host "--------------------------------------------------------" -ForegroundColor Cyan
Write-Host "✅ ¡BillsBridge instalado con éxito!" -ForegroundColor Green
Write-Host "La aplicación ahora correrá en segundo plano y se iniciará"
Write-Host "automáticamente cada vez que la computadora se encienda."
Write-Host "--------------------------------------------------------" -ForegroundColor Cyan
Write-Host "Para verificar que está funcionando, abre en tu navegador:"
Write-Host "http://localhost:8080/status" -ForegroundColor Yellow
Write-Host "--------------------------------------------------------" -ForegroundColor Cyan
