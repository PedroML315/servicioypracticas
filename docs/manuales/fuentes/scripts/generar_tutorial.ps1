param([ValidateSet('practicas', 'servicio')][string]$Proceso = 'practicas', [string]$WordDir, [string]$Trabajo)
# Une las partes (PASO 1..N.docx en $WordDir) en el tutorial completo del proceso, con portada, indice,
# numero de pagina y marcadores (textos de <proceso>\tutorial.json). Deja el Word y el PDF en $WordDir
# y el PDF en docs\manuales\<carpeta>\tutorial_completo.pdf.
$ErrorActionPreference = 'Stop'
$sc = $PSScriptRoot
$kit = Join-Path (Split-Path -Parent $sc) $Proceso
$repo = (Resolve-Path (Join-Path $sc '..\..\..\..')).Path
$utf8 = New-Object System.Text.UTF8Encoding($false)
$cfgKit = Get-Content -LiteralPath (Join-Path $kit 'tutorial.json') -Raw -Encoding UTF8 | ConvertFrom-Json
if (-not $WordDir) { $WordDir = $cfgKit.word_dir }
if (-not $Trabajo) { $Trabajo = Join-Path $env:TEMP "manuales_$Proceso" }
$dir = Join-Path $Trabajo 'tutorial'
if (Test-Path $dir) { [System.IO.Directory]::Delete($dir, $true) }
[void](New-Item -ItemType Directory -Force $dir)

$total = @($cfgKit.partes.PSObject.Properties).Count
$partes = foreach ($n in 1..$total) {
  $w = Get-ChildItem -LiteralPath $WordDir -Filter "*PASO $n.docx" | Select-Object -First 1
  if (-not $w) { throw "No encuentro el Word de la parte $n en $WordDir" }
  $w.FullName
}
[System.IO.File]::WriteAllLines((Join-Path $dir 'partes.txt'), $partes, $utf8)
$docx = Join-Path $dir 'tutorial_completo.docx'
$pdf = Join-Path $dir 'tutorial_completo.pdf'
Copy-Item -LiteralPath $partes[0] $docx -Force
& (Join-Path $sc 'proceso_word.ps1') -Docx $docx -PartesTxt (Join-Path $dir 'partes.txt') -TextosJson (Join-Path $kit 'tutorial.json') -Pdf $pdf -Dir $dir

$nombre = $cfgKit.archivo
$destinoRepo = Join-Path $repo ('docs\manuales\' + $cfgKit.carpeta)
if (-not (Test-Path $destinoRepo)) { [void](New-Item -ItemType Directory $destinoRepo) }
Copy-Item $docx (Join-Path $WordDir "$nombre.docx") -Force
Copy-Item $pdf (Join-Path $WordDir "$nombre.pdf") -Force
Copy-Item $pdf (Join-Path $destinoRepo 'tutorial_completo.pdf') -Force
"Copiado a: $WordDir y docs\manuales\$($cfgKit.carpeta)\tutorial_completo.pdf"
