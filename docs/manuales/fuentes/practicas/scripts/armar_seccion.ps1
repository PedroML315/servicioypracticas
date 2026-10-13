param([string]$Unpacked, [string]$Contenido, [string]$OutDocx, [switch]$RequireAll)
# Arma un Word con formato a partir de un Word con capturas (descomprimido en $Unpacked) y un
# contenido (H1/H/Q/T/F/B/I, ver README). Sin -RequireAll arma una seccion con solo algunos pasos
# y quita del archivo las capturas que no se usan; con -RequireAll (parte completa) exige usarlas todas.
$ErrorActionPreference = 'Stop'
$build = $OutDocx + '.build'
$utf8  = New-Object System.Text.UTF8Encoding($false)
Add-Type -AssemblyName System.Drawing

$xml    = [System.IO.File]::ReadAllText((Join-Path $Unpacked 'word\document.xml'), $utf8)
$rels   = [System.IO.File]::ReadAllText((Join-Path $Unpacked 'word\_rels\document.xml.rels'), $utf8)
$styles = [System.IO.File]::ReadAllText((Join-Path $Unpacked 'word\styles.xml'), $utf8)
foreach ($sid in 'Ttulo1', 'Ttulo2') { if ($styles -notmatch "w:styleId=`"$sid`"") { throw "Falta el estilo $sid" } }

$bulletNumId = $null
$numPath = Join-Path $Unpacked 'word\numbering.xml'
if (Test-Path $numPath) {
  $numXml = [System.IO.File]::ReadAllText($numPath, $utf8)
  $bulletAbs = @{}
  foreach ($m in [regex]::Matches($numXml, '<w:abstractNum [^>]*w:abstractNumId="(\d+)"[^>]*>(.*?)</w:abstractNum>', 'Singleline')) {
    $lvl0 = [regex]::Match($m.Groups[2].Value, '<w:lvl w:ilvl="0"[^>]*>.*?</w:lvl>', 'Singleline').Value
    if ($lvl0 -match '<w:numFmt w:val="bullet"/>') { $bulletAbs[$m.Groups[1].Value] = $true }
  }
  foreach ($m in [regex]::Matches($numXml, '<w:num w:numId="(\d+)"[^>]*>\s*<w:abstractNumId w:val="(\d+)"/>', 'Singleline')) {
    if ($bulletAbs.ContainsKey($m.Groups[2].Value)) { $bulletNumId = $m.Groups[1].Value; break }
  }
}

$rmap = @{}
foreach ($m in [regex]::Matches($rels, '<Relationship [^>]*Id="([^"]+)"[^>]*Target="([^"]+)"')) { $rmap[$m.Groups[1].Value] = Split-Path $m.Groups[2].Value -Leaf }

$bStart = $xml.IndexOf('<w:body>') + '<w:body>'.Length
$sIdx   = $xml.IndexOf('<w:sectPr')
$inner  = $xml.Substring($bStart, $sIdx - $bStart)
$paras  = [regex]::Matches($inner, '<w:p\b[^>]*/>|<w:p\b[^>]*>.*?</w:p>', 'Singleline') | ForEach-Object { $_.Value }
if (($paras -join '') -ne $inner) { throw 'El cuerpo tiene elementos que no son parrafos; no se toca.' }

$CM_PER_PX = 0.02; $MAX_W = 15.6; $MIN_W = 8.0; $MAX_H = 15.0; $EMU = 360000
function Fit-Image([string]$p, [string]$file) {
  $img = [System.Drawing.Image]::FromFile((Join-Path $Unpacked ('word\media\' + $file)))
  $pw = $img.Width; $ph = $img.Height; $img.Dispose()
  $w = [Math]::Min($MAX_W, [Math]::Max($MIN_W, $pw * $CM_PER_PX))
  $h = $w * $ph / $pw
  if ($h -gt $MAX_H) { $h = $MAX_H; $w = $h * $pw / $ph }
  $cx = [long]($w * $EMU); $cy = [long]($h * $EMU)
  $p = [regex]::Replace($p, '<wp:extent cx="\d+" cy="\d+"/>', "<wp:extent cx=`"$cx`" cy=`"$cy`"/>")
  $p = [regex]::Replace($p, '<a:ext cx="\d+" cy="\d+"/>', "<a:ext cx=`"$cx`" cy=`"$cy`"/>")
  $p = $p.Replace('<a:ln><a:noFill/></a:ln>', '')
  if ($p -notmatch '<a:ln[ >]') { $p = $p.Replace('</pic:spPr>', '<a:ln w="9525"><a:solidFill><a:srgbClr val="CFD8D5"/></a:solidFill></a:ln></pic:spPr>') }
  $ppr = '<w:pPr><w:spacing w:before="60" w:after="240"/><w:jc w:val="center"/></w:pPr>'
  if ($p -match '<w:pPr>.*?</w:pPr>') { $p = [regex]::Replace($p, '<w:pPr>.*?</w:pPr>', $ppr, 'Singleline') }
  elseif ($p -match '<w:pPr/>') { $p = $p.Replace('<w:pPr/>', $ppr) }
  else { $p = [regex]::Replace($p, '^(<w:p\b[^>]*>)', ('$1' + $ppr)) }
  return $p
}

$imgPara = @{}
foreach ($p in $paras) {
  $e = [regex]::Match($p, 'r:embed="([^"]+)"')
  if ($e.Success) { $imgPara[$rmap[$e.Groups[1].Value]] = $p }
}

function Esc([string]$s) { $s.Replace('&', '&amp;').Replace('<', '&lt;').Replace('>', '&gt;') }
$lq = [string][char]0x00AB; $rq = [string][char]0x00BB
function Runs([string]$t) {
  $sb = ''
  foreach ($part in [regex]::Split($t, "($lq[^$rq]*$rq)")) {
    if ($part -eq '') { continue }
    if ($part.StartsWith($lq)) {
      $label = [char]0x201C + $part.Substring(1, $part.Length - 2) + [char]0x201D
      $sb += '<w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t xml:space="preserve">' + (Esc $label) + '</w:t></w:r>'
    } else {
      $sb += '<w:r><w:t xml:space="preserve">' + (Esc $part) + '</w:t></w:r>'
    }
  }
  $sb
}

$used = @{}
$new  = New-Object System.Text.StringBuilder
$lines = @([System.IO.File]::ReadAllLines($Contenido, $utf8) | Where-Object { $_.Trim() -ne '' })
for ($li = 0; $li -lt $lines.Count; $li++) {
  $line = $lines[$li]
  $kind, $val = $line.Split('|', 2)
  $sigEsTitulo = ($li + 1 -lt $lines.Count) -and ($lines[$li + 1] -match '^(H|H1)\|')
  switch ($kind) {
    'H1' { [void]$new.Append('<w:p><w:pPr><w:pStyle w:val="Ttulo1"/></w:pPr><w:r><w:rPr><w:color w:val="00204A"/></w:rPr><w:t xml:space="preserve">' + (Esc $val) + '</w:t></w:r></w:p>') }
    'H'  { [void]$new.Append('<w:p><w:pPr><w:pStyle w:val="Ttulo2"/><w:spacing w:before="360" w:after="60"/></w:pPr><w:r><w:rPr><w:color w:val="01643D"/></w:rPr><w:t xml:space="preserve">' + (Esc $val) + '</w:t></w:r></w:p>') }
    'Q'  { [void]$new.Append('<w:p><w:pPr><w:keepNext/><w:spacing w:before="0" w:after="160"/></w:pPr><w:r><w:rPr><w:i/><w:iCs/><w:color w:val="5C6B65"/><w:sz w:val="22"/></w:rPr><w:t xml:space="preserve">' + (Esc $val) + '</w:t></w:r></w:p>') }
    'T'  { [void]$new.Append('<w:p><w:pPr><w:keepNext/><w:spacing w:before="120" w:after="120"/></w:pPr>' + (Runs $val) + '</w:p>') }
    'F'  { [void]$new.Append('<w:p><w:pPr><w:spacing w:before="120" w:after="200"/></w:pPr>' + (Runs $val) + '</w:p>') }
    'B'  {
      if (-not $bulletNumId) { throw 'El documento no tiene lista con vinetas para reutilizar' }
      $kn = if ($sigEsTitulo) { '' } else { '<w:keepNext/>' }
      [void]$new.Append('<w:p><w:pPr><w:pStyle w:val="Prrafodelista"/>' + $kn + '<w:numPr><w:ilvl w:val="0"/><w:numId w:val="' + $bulletNumId + '"/></w:numPr><w:spacing w:before="0" w:after="60"/></w:pPr>' + (Runs $val) + '</w:p>')
    }
    'I'  {
      if (-not $imgPara.ContainsKey($val)) { throw "No encuentro la imagen $val" }
      if ($used.ContainsKey($val)) { throw "Imagen repetida $val" }
      $used[$val] = $true
      [void]$new.Append((Fit-Image $imgPara[$val] $val))
    }
    default { throw "Linea no reconocida: $line" }
  }
}
if ($RequireAll) {
  $sinUsar = $imgPara.Keys | Where-Object { -not $used.ContainsKey($_) }
  if ($sinUsar) { throw ('Capturas del Word que no estan en el contenido: ' + ($sinUsar -join ', ')) }
}

$newXml = $xml.Substring(0, $bStart) + $new.ToString() + $xml.Substring($sIdx)
[xml]$check = $newXml

if (Test-Path $build) { [System.IO.Directory]::Delete($build, $true) }
Copy-Item $Unpacked $build -Recurse
[System.IO.File]::WriteAllText((Join-Path $build 'word\document.xml'), $newXml, $utf8)

# Quitar las capturas que esta seccion no usa (y sus relaciones)
$relsPath = Join-Path $build 'word\_rels\document.xml.rels'
$relsTxt = [System.IO.File]::ReadAllText($relsPath, $utf8)
$enUso = @{}
foreach ($m in [regex]::Matches($newXml, 'r:embed="([^"]+)"')) { $enUso[$m.Groups[1].Value] = $true }
$quitadas = 0
foreach ($m in [regex]::Matches($relsTxt, '<Relationship [^>]*Id="([^"]+)"[^>]*relationships/image"[^>]*Target="([^"]+)"[^>]*/>|<Relationship [^>]*Id="([^"]+)"[^>]*Target="([^"]+)"[^>]*relationships/image"[^>]*/>')) {
  $id = if ($m.Groups[1].Success) { $m.Groups[1].Value } else { $m.Groups[3].Value }
  $tg = if ($m.Groups[2].Success) { $m.Groups[2].Value } else { $m.Groups[4].Value }
  if (-not $enUso.ContainsKey($id)) {
    $relsTxt = $relsTxt.Replace($m.Value, '')
    $f = Join-Path $build ('word\' + $tg.Replace('/', '\'))
    if (Test-Path $f) { [System.IO.File]::Delete($f); $quitadas++ }
  }
}
[xml]$chk = $relsTxt
[System.IO.File]::WriteAllText($relsPath, $relsTxt, $utf8)

Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem
if (Test-Path -LiteralPath $OutDocx) { [System.IO.File]::Delete($OutDocx) }
$zip = [System.IO.Compression.ZipFile]::Open($OutDocx, 'Create')
try {
  # Ruta real de la carpeta: si llega con nombre corto (DESARR~1), FullName la devuelve larga
  $buildFull = (Get-Item -LiteralPath $build).FullName.TrimEnd('\')
  $files = Get-ChildItem -LiteralPath $buildFull -Recurse -File | Sort-Object { if ($_.Name -eq '[Content_Types].xml') { 0 } else { 1 } }, FullName
  foreach ($f in $files) {
    $entry = $f.FullName.Substring($buildFull.Length + 1).Replace('\', '/')
    [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $f.FullName, $entry, 'Optimal')
  }
} finally { $zip.Dispose() }
[System.IO.Directory]::Delete($build, $true)
"{0}: {1} capturas usadas, {2} quitadas" -f (Split-Path $OutDocx -Leaf), $used.Count, $quitadas
