param([string]$Docx, [string]$PidFile)
# El parrafo vacio que cierra el campo del indice no se puede borrar: se usa como inicio de la hoja
# de la parte 1 (salto de pagina anterior, 1 pt) y a "Parte 1" se le quita el salto.
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
  $p = $doc.Range($toc.Range.End, $toc.Range.End).Paragraphs.Item(1)
  if (($p.Range.Text -replace "[\r\x15\s]", '') -ne '') { throw 'El parrafo despues del indice no esta vacio' }
  $p.Format.PageBreakBefore = -1
  $p.Format.SpaceBefore = 0; $p.Format.SpaceAfter = 0
  $p.Format.LineSpacingRule = 4; $p.Format.LineSpacing = 1   # exacto, 1 pt
  $p.Range.Font.Size = 1
  $parte1 = $p.Next()
  if ($parte1.OutlineLevel -ne 1) { throw 'Despues del indice no sigue el titulo de la parte 1' }
  $parte1.Format.PageBreakBefore = 0
  $toc.Update()
  $doc.Save()
  "Parte 1 empieza en la pagina " + $parte1.Range.Information(3) + " | renglon de cierre en la pagina " + $p.Range.Information(3)
  "Paginas: " + $doc.ComputeStatistics(2)
  $doc.Close(0)
} finally {
  $wd.Quit()
  [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($wd)
}
