param([string]$Script, [string[]]$Args2, [int]$Timeout = 600, [string]$PidFile)
# Corre un script de Word en un Start-Job con limite de tiempo. Pase lo que pase (termina, falla o se
# agota el tiempo), cierra solo las instancias de Word ocultas que ese script abrio (anotadas en PidFile).
$job = Start-Job -ScriptBlock { param($sc, $a) & $sc @a } -ArgumentList $Script, $Args2
try {
  if (Wait-Job $job -Timeout $Timeout) {
    Receive-Job $job
  } else {
    'Tiempo agotado'
    Stop-Job $job
  }
} finally {
  Remove-Job $job -Force
  Start-Sleep -Seconds 2
  if (Test-Path $PidFile) {
    foreach ($id in ((Get-Content $PidFile) -split ',' | Where-Object { $_ })) {
      $pr = Get-Process -Id ([int]$id) -ErrorAction SilentlyContinue
      if ($pr -and $pr.MainWindowHandle -eq 0) { Stop-Process -Id $pr.Id -Force; "Cerrado Word oculto $id" }
    }
  }
}
