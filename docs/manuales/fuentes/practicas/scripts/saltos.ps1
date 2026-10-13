param([string]$Docx, [string]$PidFile)
# Cambia los saltos de pagina manuales por "salto de pagina anterior" en el indice y en cada parte,
# para que no queden hojas en blanco cuando una pagina termina justo antes del salto.
$ErrorActionPreference = 'Stop'
$before = @(Get-Process WINWORD -ErrorAction SilentlyContinue | ForEach-Object Id)
$wd = New-Object -ComObject Word.Application
$mine = @(Get-Process WINWORD -ErrorAction SilentlyContinue | Where-Object { $before -notcontains $_.Id } | ForEach-Object Id)
Set-Content -LiteralPath $PidFile -Value ($mine -join ',')
$wd.Visible = $false
$wd.DisplayAlerts = 0
try {
  $doc = $wd.Documents.Open($Docx, $false, $false)

  # 1. Quitar todos los saltos de pagina manuales (^m)
  $f = $doc.Content.Find
  $f.ClearFormatting(); $f.Replacement.ClearFormatting()
  [void]$f.Execute('^m', $false, $false, $false, $false, $false, $true, 1, $false, '', 2)   # 2 = wdReplaceAll

  # 2. Ubicar el titulo "Contenido" (primer parrafo con ese texto) y los titulos de parte
  $inicios = New-Object System.Collections.Generic.List[int]
  $contenido = $null
  foreach ($p in $doc.Paragraphs) {
    $t = $p.Range.Text.Trim()
    if (-not $contenido -and $t -eq 'Contenido') { $contenido = $p.Range.Start }
    if ($p.OutlineLevel -eq 1) { $inicios.Add($p.Range.Start) }
  }
  if ($contenido -ne $null) { $inicios.Insert(0, $contenido) }

  # 3. De abajo hacia arriba: hoja nueva antes de cada uno y quitar renglones vacios previos
  $quitados = 0
  for ($k = $inicios.Count - 1; $k -ge 0; $k--) {
    $p = $doc.Range($inicios[$k], $inicios[$k]).Paragraphs.Item(1)
    $p.Format.PageBreakBefore = -1
    $prev = $p.Previous()
    while ($prev -ne $null) {
      $txt = $prev.Range.Text -replace "[\r\n\f\s]", ''
      if ($txt -ne '' -or $prev.Range.InlineShapes.Count -gt 0 -or $prev.OutlineLevel -eq 1) { break }
      # El ultimo renglon del indice es parte del campo y Word no deja borrarlo: se deja
      try { $prev.Range.Delete() | Out-Null } catch { break }
      $quitados++
      $prev = $p.Previous()
    }
  }

  foreach ($toc in $doc.TablesOfContents) { $toc.Update() }
  $doc.Save()
  "Titulos con hoja nueva: $($inicios.Count) | renglones vacios quitados: $quitados"
  "Paginas: " + $doc.ComputeStatistics(2)
  $doc.Close(0)
} finally {
  $wd.Quit()
  [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($wd)
}
