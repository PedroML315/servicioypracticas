param([string]$Docx, [string]$PidFile)
# - "Parte 1" ya no es el primer renglon de su hoja (lo antecede el cierre del indice): sin espacio arriba.
# - La lista que va justo antes de un paso no debe quedar amarrada a el (si no, todo baja de hoja).
$ErrorActionPreference = 'Stop'
$before = @(Get-Process WINWORD -ErrorAction SilentlyContinue | ForEach-Object Id)
$wd = New-Object -ComObject Word.Application
$mine = @(Get-Process WINWORD -ErrorAction SilentlyContinue | Where-Object { $before -notcontains $_.Id } | ForEach-Object Id)
Set-Content -LiteralPath $PidFile -Value ($mine -join ',')
$wd.Visible = $false
$wd.DisplayAlerts = 0
try {
  $doc = $wd.Documents.Open($Docx, $false, $false)
  $toc = $doc.TablesOfContents.Item(1)
  $parte1 = $doc.Range($toc.Range.End, $toc.Range.End).Paragraphs.Item(1).Next()
  $parte1.Format.SpaceBefore = 0
  $sueltos = 0
  foreach ($p in $doc.Paragraphs) {
    if ($p.OutlineLevel -ne 2) { continue }
    $prev = $p.Previous()
    if ($prev -ne $null -and $prev.Range.ListFormat.ListType -ne 0 -and $prev.Format.KeepWithNext -ne 0) {
      $prev.Format.KeepWithNext = 0
      $sueltos++
    }
  }
  $toc.Update()
  $doc.Save()
  "Listas sueltas antes de un paso: $sueltos | Paginas: " + $doc.ComputeStatistics(2)
  $partes = foreach ($q in $doc.Paragraphs) { if ($q.OutlineLevel -eq 1) { $q.Range.Information(3) } }
  "Pagina donde empieza cada parte: " + ($partes -join ', ')
  $doc.Close(0)
} finally {
  $wd.Quit()
  [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($wd)
}
