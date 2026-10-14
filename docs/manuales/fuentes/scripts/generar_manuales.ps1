param([string]$Id = 'todos', [ValidateSet('practicas', 'servicio')][string]$Proceso = 'practicas', [string]$WordDir, [string]$Trabajo)
# Genera los manuales por perfil definidos en <proceso>\manuales.json (o solo -Id <id>) a partir de las
# partes con formato (PASO N.docx en $WordDir) y sus textos (partes\pN.txt).
# Cada manual lleva solo los pasos de su perfil, renumerados desde 1; la linea "Lo hace" se quita salvo
# en los pasos de dos perfiles. Deja el Word y el PDF en "$WordDir\Manuales por perfil" y el PDF en
# docs\manuales\<carpeta> (el que se sube al repositorio y entrega controller/manuales.php).
$ErrorActionPreference = 'Stop'
$sc = $PSScriptRoot
$kit = Join-Path (Split-Path -Parent $sc) $Proceso
$repo = (Resolve-Path (Join-Path $sc '..\..\..\..')).Path
$utf8 = New-Object System.Text.UTF8Encoding($false)
Add-Type -AssemblyName System.IO.Compression.FileSystem
$cfgKit = Get-Content -LiteralPath (Join-Path $kit 'tutorial.json') -Raw -Encoding UTF8 | ConvertFrom-Json
if (-not $WordDir) { $WordDir = $cfgKit.word_dir }
if (-not $Trabajo) { $Trabajo = Join-Path $env:TEMP "manuales_$Proceso" }
$cfg = Get-Content -LiteralPath (Join-Path $kit 'manuales.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$lista = @(if ($Id -eq 'todos') { $cfg.manuales } else { $cfg.manuales | Where-Object { $_.id -eq $Id } })
if (-not $lista.Count) { throw "No existe el manual $Id" }

# 1. Copia de trabajo de cada parte (los manuales toman de ahi sus capturas)
$src = Join-Path $Trabajo 'src'
if (Test-Path $src) { [System.IO.Directory]::Delete($src, $true) }
[void](New-Item -ItemType Directory -Force $src)
foreach ($p in $cfg.comunes.fuentes.PSObject.Properties) {
  $w = Get-ChildItem -LiteralPath $WordDir -Filter "*PASO $($p.Name).docx" | Select-Object -First 1
  if (-not $w) { throw "No encuentro el Word de la parte $($p.Name) en $WordDir" }
  $zip = Join-Path $src "p$($p.Name).docx"
  Copy-Item -LiteralPath $w.FullName $zip
  [System.IO.Compression.ZipFile]::ExtractToDirectory($zip, (Join-Path $src "p$($p.Name)"))
}

# 2. Pasos de cada parte: numero -> lineas (desde su H hasta antes del siguiente H, H1 o F)
$pasos = @{}
foreach ($p in $cfg.comunes.fuentes.PSObject.Properties) {
  $mapa = @{}; $actual = $null
  foreach ($l in [System.IO.File]::ReadAllLines((Join-Path $kit $p.Value), $utf8)) {
    if ($l.Trim() -eq '') { continue }
    if ($l -match '^H\|Paso (\d+) ') { $actual = [int]$Matches[1]; $mapa[$actual] = New-Object System.Collections.Generic.List[string]; $mapa[$actual].Add($l); continue }
    if ($l -match '^(H1|F)\|') { $actual = $null; continue }
    if ($actual -ne $null) { $mapa[$actual].Add($l) }
  }
  $pasos[[int]$p.Name] = $mapa
}

$destino = Join-Path $WordDir 'Manuales por perfil'
if (-not (Test-Path $destino)) { [void](New-Item -ItemType Directory $destino) }
$destinoRepo = Join-Path $repo ('docs\manuales\' + $cfgKit.carpeta)
if (-not (Test-Path $destinoRepo)) { [void](New-Item -ItemType Directory $destinoRepo) }
$base = (Get-ChildItem -LiteralPath $WordDir -Filter '*PASO 1.docx' | Select-Object -First 1).FullName

foreach ($man in $lista) {
  "######## Manual: $($man.id)"
  $dir = Join-Path $Trabajo $man.id
  if (Test-Path $dir) { [System.IO.Directory]::Delete($dir, $true) }
  [void](New-Item -ItemType Directory (Join-Path $dir 'secciones'))
  $k = 0; $n = 0; $docs = @(); $titulos = [ordered]@{}
  $secciones = @($man.secciones)
  for ($si = 0; $si -lt $secciones.Count; $si++) {
    $sec = $secciones[$si]
    $titulos[[string]($si + 1)] = $sec.titulo
    $bloques = @($sec.bloques)
    for ($bi = 0; $bi -lt $bloques.Count; $bi++) {
      $b = $bloques[$bi]
      $lineas = New-Object System.Collections.Generic.List[string]
      if ($bi -eq 0) {
        $lineas.Add('H1|' + $cfgKit.proceso + ' (parte ' + ($si + 1) + ')')
        $lineas.Add('F|' + $sec.intro)
      }
      foreach ($num in @($b.pasos)) {
        $bloque = $pasos[[int]$b.parte][[int]$num]
        if (-not $bloque) { throw "No encuentro el paso $num en la parte $($b.parte)" }
        $k++
        foreach ($l in $bloque) {
          if ($l -match '^H\|Paso \d+ ') { $lineas.Add([regex]::Replace($l, '^H\|Paso \d+ ', "H|Paso $k ")); continue }
          if ($l -match '^Q\|' -and $l -notmatch ' y ') { continue }
          $t = $l
          foreach ($r in @($man.reemplazos)) { if ($r) { $t = $t.Replace($r.de, $r.a) } }
          $lineas.Add($t)
        }
      }
      if ($si -eq $secciones.Count - 1 -and $bi -eq $bloques.Count - 1) { $lineas.Add('F|' + $man.cierre) }
      $n++
      $c = Join-Path $dir ('secciones\{0:D2}.txt' -f $n)
      $o = Join-Path $dir ('secciones\{0:D2}.docx' -f $n)
      [System.IO.File]::WriteAllLines($c, $lineas, $utf8)
      & (Join-Path $sc 'armar_seccion.ps1') -Unpacked (Join-Path $src "p$($b.parte)") -Contenido $c -OutDocx $o
      $docs += $o
    }
  }
  [System.IO.File]::WriteAllLines((Join-Path $dir 'partes.txt'), $docs, $utf8)
  $portada = @(
    [ordered]@{ t = $cfgKit.proceso; size = 30; bold = $true; color = '00204A'; before = 36 },
    [ordered]@{ t = $man.subtitulo; size = 20; bold = $false; color = '01643D'; before = 6 },
    [ordered]@{ t = $man.descripcion; size = 12; bold = $false; color = '5C6B65'; before = 24 },
    [ordered]@{ t = $cfg.comunes.pie1; size = 14; bold = $true; color = '00204A'; before = 180 },
    [ordered]@{ t = $cfg.comunes.pie2; size = 11; bold = $false; color = '5C6B65'; before = 4 }
  )
  $textos = [ordered]@{ portada = $portada; contenido = $cfg.comunes.contenido; nota = $man.nota; partes = $titulos }
  [System.IO.File]::WriteAllText((Join-Path $dir 'textos.json'), ($textos | ConvertTo-Json -Depth 5), $utf8)
  "$k pasos en $($secciones.Count) partes ($n secciones)"

  $docx = Join-Path $dir 'manual.docx'
  $pdf = Join-Path $dir 'manual.pdf'
  Copy-Item -LiteralPath $base $docx -Force
  & (Join-Path $sc 'proceso_word.ps1') -Docx $docx -PartesTxt (Join-Path $dir 'partes.txt') -TextosJson (Join-Path $dir 'textos.json') -Pdf $pdf -Dir $dir
  Copy-Item $docx (Join-Path $destino ($man.archivo + '.docx')) -Force
  Copy-Item $pdf (Join-Path $destino ($man.archivo + '.pdf')) -Force
  Copy-Item $pdf (Join-Path $destinoRepo $man.pdf) -Force
  "Copiado a: $destino y docs\manuales\$($cfgKit.carpeta)\$($man.pdf)"
}
