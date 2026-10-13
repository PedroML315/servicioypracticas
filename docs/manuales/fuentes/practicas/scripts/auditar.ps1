param([string]$WordDir = 'C:\Users\Desarrollo\Desktop\SP', [string]$Trabajo = (Join-Path $env:TEMP 'manuales_pp'))
# Revisa el formato de las partes (PASO N.docx): pagina, estilos, numeracion de pasos, capturas
# (centradas, con borde, a escala), renglones vacios y emojis. Deja el reporte en $Trabajo\auditoria.txt.
$ErrorActionPreference = 'Stop'
$work = $Trabajo
if (-not (Test-Path $work)) { [void](New-Item -ItemType Directory $work) }
$root = Join-Path $work 'auditoria'
if (Test-Path $root) { [System.IO.Directory]::Delete($root, $true) }
[void](New-Item -ItemType Directory $root)
Add-Type -AssemblyName System.IO.Compression.FileSystem, System.Drawing
$utf8 = New-Object System.Text.UTF8Encoding($false)
$out = New-Object System.Collections.Generic.List[string]
$EMU = 360000.0

$docs = Get-ChildItem -LiteralPath $WordDir -Filter '*PASO *.docx' | Where-Object { $_.Name -match 'PASO \d+\.docx$' } | Sort-Object { [int]([regex]::Match($_.Name, 'PASO (\d+)').Groups[1].Value) }
foreach ($d in $docs) {
  $n = ([regex]::Match($d.Name, 'PASO (\d+)')).Groups[1].Value
  if (-not $n) { $n = '0' }
  $dir = Join-Path $root "p$n"
  $copy = Join-Path $root "p$n.docx"
  $in = [System.IO.File]::Open($d.FullName, 'Open', 'Read', 'ReadWrite'); $o = [System.IO.File]::Create($copy); $in.CopyTo($o); $o.Close(); $in.Close()
  [System.IO.Compression.ZipFile]::ExtractToDirectory($copy, $dir)

  $xml = [System.IO.File]::ReadAllText((Join-Path $dir 'word\document.xml'), $utf8)
  $rels = [System.IO.File]::ReadAllText((Join-Path $dir 'word\_rels\document.xml.rels'), $utf8)
  $styles = [System.IO.File]::ReadAllText((Join-Path $dir 'word\styles.xml'), $utf8)
  $themePath = Join-Path $dir 'word\theme\theme1.xml'
  $theme = if (Test-Path $themePath) { [System.IO.File]::ReadAllText($themePath, $utf8) } else { '' }
  $rmap = @{}
  foreach ($m in [regex]::Matches($rels, '<Relationship [^>]*Id="([^"]+)"[^>]*Target="([^"]+)"')) { $rmap[$m.Groups[1].Value] = $m.Groups[2].Value }

  $body = $xml.Substring($xml.IndexOf('<w:body>') + 8)
  $paras = [regex]::Matches($body, '<w:p\b[^>]*/>|<w:p\b[^>]*>.*?</w:p>', 'Singleline') | ForEach-Object { $_.Value }

  $out.Add("==================== $($d.Name)")
  $sect = [regex]::Match($xml, '<w:sectPr.*?</w:sectPr>', 'Singleline').Value
  $pg = [regex]::Match($sect, '<w:pgSz w:w="(\d+)" w:h="(\d+)"')
  $mar = [regex]::Match($sect, '<w:pgMar w:top="(\d+)" w:right="(\d+)" w:bottom="(\d+)" w:left="(\d+)"')
  $out.Add(('Pagina {0}x{1} twips | margenes sup {2:N2} der {3:N2} inf {4:N2} izq {5:N2} cm' -f $pg.Groups[1].Value, $pg.Groups[2].Value, ([int]$mar.Groups[1].Value / 567.0), ([int]$mar.Groups[2].Value / 567.0), ([int]$mar.Groups[3].Value / 567.0), ([int]$mar.Groups[4].Value / 567.0)))
  $minor = [regex]::Match($theme, '<a:minorFont>\s*<a:latin typeface="([^"]+)"').Groups[1].Value
  $major = [regex]::Match($theme, '<a:majorFont>\s*<a:latin typeface="([^"]+)"').Groups[1].Value
  $out.Add("Fuente del tema: texto '$minor' | titulos '$major'")
  foreach ($sid in 'Ttulo1', 'Ttulo2') {
    $st = [regex]::Match($styles, "<w:style [^>]*w:styleId=`"$sid`".*?</w:style>", 'Singleline').Value
    $sz = [regex]::Match($st, '<w:sz w:val="(\d+)"').Groups[1].Value
    $col = [regex]::Match($st, '<w:color w:val="([0-9A-Fa-f]+)"').Groups[1].Value
    $out.Add("Estilo $sid : tam $([int]$sz / 2) pt | color de estilo $col")
  }

  $empty = 0; $emoji = 0; $imgs = 0; $bad = New-Object System.Collections.Generic.List[string]
  $pasos = New-Object System.Collections.Generic.List[string]
  $i = 0; $last = ''
  foreach ($p in $paras) {
    $i++
    $txt = (([regex]::Matches($p, '<w:t[^>]*>([^<]*)</w:t>') | ForEach-Object { $_.Groups[1].Value }) -join '')
    $hasImg = $p -match '<w:drawing>'
    if ($txt.Trim() -eq '' -and -not $hasImg) { $empty++ }
    if ($txt -match '[\uD83C-\uDBFF]|\u2705|\u270D|\u27A1') { $emoji++ }
    if ($p -match 'w:pStyle w:val="Ttulo1"') { $out.Add("H1: $txt") }
    if ($p -match 'w:pStyle w:val="Ttulo2"') { $pasos.Add($txt) }
    if ($txt.Trim() -ne '') { $last = $txt }
    if ($hasImg) {
      foreach ($dr in [regex]::Matches($p, '<w:drawing>.*?</w:drawing>', 'Singleline')) {
        $imgs++
        $v = $dr.Value
        $file = $rmap[[regex]::Match($v, 'r:embed="([^"]+)"').Groups[1].Value]
        $ext = [regex]::Match($v, '<wp:extent cx="(\d+)" cy="(\d+)"/>')
        $wcm = [double]$ext.Groups[1].Value / $EMU; $hcm = [double]$ext.Groups[2].Value / $EMU
        $img = [System.Drawing.Image]::FromFile((Join-Path $dir ('word\' + $file))); $pw = $img.Width; $ph = $img.Height; $img.Dispose()
        $expW = [Math]::Min(15.6, [Math]::Max(8.0, $pw * 0.02)); $expH = $expW * $ph / $pw
        if ($expH -gt 15.0) { $expH = 15.0; $expW = $expH * $pw / $ph }
        $probs = @()
        if ($v -match '<wp:anchor') { $probs += 'flotante' }
        if ($p -notmatch '<w:jc w:val="center"/>') { $probs += 'no centrada' }
        if ($v -notmatch 'CFD8D5') { $probs += 'sin borde' }
        if ([Math]::Abs($wcm - $expW) -gt 0.15 -or [Math]::Abs($hcm - $expH) -gt 0.15) { $probs += ('tamano {0:N1}x{1:N1} (esperado {2:N1}x{3:N1})' -f $wcm, $hcm, $expW, $expH) }
        if ($p -notmatch 'w:before="60" w:after="240"') { $probs += 'espaciado distinto' }
        if ($txt.Trim() -ne '') { $probs += 'comparte renglon con texto' }
        if ($probs.Count) { $bad.Add(('  p{0} {1}: {2}' -f $i, (Split-Path $file -Leaf), ($probs -join ', '))) }
      }
    }
  }
  $nums = @($pasos | ForEach-Object { $m = [regex]::Match($_, 'Paso (\d+)'); if ($m.Success) { [int]$m.Groups[1].Value } })
  $out.Add("Pasos: " + ($pasos -join ' | '))
  if ($nums.Count -eq 0) { $out.Add('Numeracion: sin pasos todavia') }
  else {
    $seq = 'ok'
    for ($k = 1; $k -lt $nums.Count; $k++) { if ($nums[$k] -ne $nums[$k - 1] + 1) { $seq = "salto entre $($nums[$k-1]) y $($nums[$k])" } }
    $out.Add("Numeracion: $($nums[0])-$($nums[-1]) ($seq)")
  }
  $out.Add("Imagenes: $imgs | con problemas: $($bad.Count) | renglones vacios: $empty | emojis: $emoji")
  foreach ($b in $bad) { $out.Add($b) }
  $out.Add("Cierre: $last")
}
[System.IO.File]::WriteAllLines((Join-Path $work 'auditoria.txt'), $out, $utf8)
$out
"Listo: $($docs.Count) documentos (reporte en $work\auditoria.txt)"
