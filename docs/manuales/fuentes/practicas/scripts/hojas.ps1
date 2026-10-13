param([string]$Docx, [string]$PidFile)
# Revisa hoja por hoja (solo lectura): marca las que no tienen texto ni imagenes y muestra
# como empieza el indice y en que hojas cae.
$ErrorActionPreference = 'Stop'
$before = @(Get-Process WINWORD -ErrorAction SilentlyContinue | ForEach-Object Id)
$wd = New-Object -ComObject Word.Application
$mine = @(Get-Process WINWORD -ErrorAction SilentlyContinue | Where-Object { $before -notcontains $_.Id } | ForEach-Object Id)
Set-Content -LiteralPath $PidFile -Value ($mine -join ',')
$wd.Visible = $false
$wd.DisplayAlerts = 0
try {
  $doc = $wd.Documents.Open($Docx, $false, $true)
  $n = $doc.ComputeStatistics(2)
  $vacias = @()
  for ($pg = 1; $pg -le $n; $pg++) {
    $ini = $doc.GoTo(1, 1, $pg).Start
    $fin = if ($pg -lt $n) { $doc.GoTo(1, 1, $pg + 1).Start } else { $doc.Content.End }
    $r = $doc.Range($ini, $fin)
    $txt = ($r.Text -replace "[\s\r\n\f\x07\x13\x14\x15]", '')
    if ($txt.Length -lt 3 -and $r.InlineShapes.Count -eq 0) { $vacias += $pg }
    if ($pg -le 5) { "Hoja ${pg}: " + $txt.Length + " caracteres, " + $r.InlineShapes.Count + " imagenes | " + ($r.Text -replace "[\r\n\t\f\x07\x13\x14\x15]", ' ').Trim().Substring(0, [Math]::Min(90, $txt.Length)) }
  }
  "Hojas: $n | hojas vacias: " + $(if ($vacias.Count) { $vacias -join ', ' } else { 'ninguna' })
  $toc = $doc.TablesOfContents.Item(1)
  "Indice: hojas " + $toc.Range.Characters.First.Information(3) + " a " + $toc.Range.Characters.Last.Information(3)
  $doc.Close(0)
} finally {
  $wd.Quit()
  [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($wd)
}
