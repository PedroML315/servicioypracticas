param([string]$OutDocx, [string]$PartsList, [string]$TextosJson, [string]$Logo, [string]$PidFile)
# Une las partes del tutorial con Word: portada, indice, cada parte en hoja nueva y numero de pagina.
$ErrorActionPreference = 'Stop'
$cfg = Get-Content -LiteralPath $TextosJson -Raw -Encoding UTF8 | ConvertFrom-Json
# @(): con una sola parte, Get-Content devuelve un texto y $parts[0] seria su primera letra
$parts = @(Get-Content -LiteralPath $PartsList -Encoding UTF8)

function Rgb([string]$hex) { $r = [Convert]::ToInt32($hex.Substring(0, 2), 16); $g = [Convert]::ToInt32($hex.Substring(2, 2), 16); $b = [Convert]::ToInt32($hex.Substring(4, 2), 16); return $r + $g * 256 + $b * 65536 }

$before = @(Get-Process WINWORD -ErrorAction SilentlyContinue | ForEach-Object Id)
$wd = New-Object -ComObject Word.Application
$mine = @(Get-Process WINWORD -ErrorAction SilentlyContinue | Where-Object { $before -notcontains $_.Id } | ForEach-Object Id)
Set-Content -LiteralPath $PidFile -Value ($mine -join ',')
$wd.Visible = $false
$wd.DisplayAlerts = 0
try {
  # La base es una copia de la parte 1 (para conservar estilos, margenes y fuente); se vacia
  $doc = $wd.Documents.Open($OutDocx, $false, $false)
  $doc.Content.Delete()
  $normal = $doc.Styles.Item(-1)          # wdStyleNormal
  $sel = $wd.Selection
  $sel.HomeKey(6) | Out-Null               # wdStory
  $sel.Range.Style = $normal

  # Portada
  $sel.ParagraphFormat.Alignment = 1       # centrado
  $sel.ParagraphFormat.SpaceBefore = 60
  $pic = $sel.InlineShapes.AddPicture($Logo)
  $ratio = $pic.Height / $pic.Width
  $pic.Width = 200; $pic.Height = 200 * $ratio
  $sel.TypeParagraph()
  foreach ($l in $cfg.portada) {
    $sel.Range.Style = $normal
    $sel.ParagraphFormat.Alignment = 1
    $sel.ParagraphFormat.SpaceBefore = [double]$l.before
    $sel.ParagraphFormat.SpaceAfter = 0
    $sel.Font.Size = [double]$l.size
    $sel.Font.Bold = $(if ($l.bold) { -1 } else { 0 })
    $sel.Font.Color = Rgb $l.color
    $sel.TypeText([string]$l.t)
    $sel.TypeParagraph()
  }
  # Indice. Sin saltos de pagina manuales: "Contenido" y cada parte empiezan en hoja nueva con
  # "salto de pagina anterior" (un salto manual pegado al indice deja una hoja en blanco).
  $sel.Range.Style = $normal
  $sel.ParagraphFormat.Alignment = 0
  $sel.ParagraphFormat.SpaceBefore = 0
  $sel.ParagraphFormat.SpaceAfter = 6
  $sel.ParagraphFormat.PageBreakBefore = -1
  $sel.Font.Size = 20; $sel.Font.Bold = -1; $sel.Font.Color = Rgb '00204A'
  $sel.TypeText([string]$cfg.contenido)
  $sel.TypeParagraph()
  $sel.ParagraphFormat.PageBreakBefore = 0
  $sel.Font.Size = 11; $sel.Font.Bold = 0; $sel.Font.Color = Rgb '5C6B65'; $sel.Font.Italic = -1
  $sel.ParagraphFormat.SpaceAfter = 12
  $sel.TypeText([string]$cfg.nota)
  $sel.TypeParagraph()
  $sel.Font.Italic = 0; $sel.Font.Color = 0
  $sel.ParagraphFormat.SpaceAfter = 0
  $toc = $doc.TablesOfContents.Add($sel.Range, $true, 1, 2)

  # Partes (la hoja nueva la pone el titulo de cada parte, mas abajo)
  for ($i = 0; $i -lt $parts.Count; $i++) {
    $sel.EndKey(6) | Out-Null
    $sel.TypeParagraph()   # renglon propio: sin el, la parte 1 caeria dentro del campo del indice
    $sel.InsertFile($parts[$i])
  }

  # Titulos de cada parte, mas claros para el indice
  $cambiados = 0
  foreach ($p in $doc.Paragraphs) {
    if ($p.OutlineLevel -ne 1) { continue }
    $m = [regex]::Match($p.Range.Text, '\(parte (\d+)\)')
    if (-not $m.Success) { continue }
    $nuevo = [string]$cfg.partes.($m.Groups[1].Value)
    if (-not $nuevo) { continue }
    # Primero el formato: al cambiar el texto, Word invalida el objeto del parrafo
    $p.Format.PageBreakBefore = -1
    $r = $p.Range
    [void]$r.MoveEnd(1, -1)
    $r.Text = $nuevo
    $cambiados++
  }

  # Numero de pagina al pie (centrado), sin numero en la portada
  $sec = $doc.Sections.Item(1)
  $sec.PageSetup.DifferentFirstPageHeaderFooter = -1
  [void]$sec.Footers.Item(1).PageNumbers.Add(1, $false)

  $toc = $doc.TablesOfContents.Item(1)
  $toc.Update()
  $doc.Save()
  "Titulos de parte cambiados: $cambiados"
  "Paginas: " + $doc.ComputeStatistics(2) + " | Imagenes: " + $doc.InlineShapes.Count + " | Secciones: " + $doc.Sections.Count
  "Entradas del indice: " + $toc.Range.Paragraphs.Count
  $doc.Close(0)
} catch {
  "ERROR en la linea $($_.InvocationInfo.ScriptLineNumber): $($_.InvocationInfo.Line.Trim()) -> $($_.Exception.Message)"
  throw
} finally {
  $wd.Quit()
  [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($wd)
}
