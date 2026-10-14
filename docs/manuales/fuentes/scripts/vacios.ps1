param([string]$Docx, [string]$PidFile)
# Quita los renglones vacios que deja la union entre secciones de una misma parte
# (no toca el indice ni el renglon de cierre del indice, que lleva "salto de pagina anterior").
$ErrorActionPreference = 'Stop'
$before = @(Get-Process WINWORD -ErrorAction SilentlyContinue | ForEach-Object Id)
$wd = New-Object -ComObject Word.Application
$mine = @(Get-Process WINWORD -ErrorAction SilentlyContinue | Where-Object { $before -notcontains $_.Id } | ForEach-Object Id)
Set-Content -LiteralPath $PidFile -Value ($mine -join ',')
$wd.Visible = $false
$wd.DisplayAlerts = 0
try {
  $doc = $wd.Documents.Open($Docx, $false, $false)
  $finIndice = $doc.TablesOfContents.Item(1).Range.End
  $quitar = New-Object System.Collections.Generic.List[int]
  foreach ($p in $doc.Paragraphs) {
    $r = $p.Range
    if ($r.Start -le $finIndice + 1) { continue }
    if ($p.Format.PageBreakBefore -ne 0) { continue }
    if ($r.InlineShapes.Count -gt 0) { continue }
    if (($r.Text -replace "[\r\n\f\s\x07]", '') -ne '') { continue }
    $quitar.Add($r.Start)
  }
  $n = 0
  for ($i = $quitar.Count - 1; $i -ge 0; $i--) {
    $p = $doc.Range($quitar[$i], $quitar[$i]).Paragraphs.Item(1)
    try { [void]$p.Range.Delete(); $n++ } catch { }
  }
  $doc.TablesOfContents.Item(1).Update()
  $doc.Save()
  "Renglones vacios quitados: $n | Paginas: " + $doc.ComputeStatistics(2)
  $doc.Close(0)
} finally {
  $wd.Quit()
  [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($wd)
}
