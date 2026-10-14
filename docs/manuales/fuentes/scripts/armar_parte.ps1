param([int]$Parte, [ValidateSet('practicas', 'servicio')][string]$Proceso = 'practicas', [string]$WordDir, [string]$Trabajo)
# Vuelve a dar formato a una parte del tutorial (PASO N.docx en $WordDir) con su texto (partes\pN.txt):
# respalda el Word en $Trabajo\respaldos, lo descomprime y lo reescribe con armar_seccion.ps1 -RequireAll.
# Uso tipico: se cambia una captura en el Word o un texto en pN.txt -> armar_parte -> auditar.
$ErrorActionPreference = 'Stop'
$kit = Join-Path (Split-Path -Parent $PSScriptRoot) $Proceso
$cfgKit = Get-Content -LiteralPath (Join-Path $kit 'tutorial.json') -Raw -Encoding UTF8 | ConvertFrom-Json
if (-not $WordDir) { $WordDir = $cfgKit.word_dir }
if (-not $Trabajo) { $Trabajo = Join-Path $env:TEMP "manuales_$Proceso" }
$word = Get-ChildItem -LiteralPath $WordDir -Filter "*PASO $Parte.docx" | Select-Object -First 1
if (-not $word) { throw "No encuentro el Word de la parte $Parte en $WordDir" }
$contenido = Join-Path $kit "partes\p$Parte.txt"
if (-not (Test-Path $contenido)) { throw "No existe $contenido" }

$resp = Join-Path $Trabajo 'respaldos'
if (-not (Test-Path $resp)) { [void](New-Item -ItemType Directory -Force $resp) }
$copia = Join-Path $resp ('PASO {0} {1:yyyyMMdd-HHmmss}.docx' -f $Parte, (Get-Date))
Copy-Item -LiteralPath $word.FullName $copia

$unp = Join-Path $Trabajo "parte$Parte"
if (Test-Path $unp) { [System.IO.Directory]::Delete($unp, $true) }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::ExtractToDirectory($copia, $unp)

& (Join-Path $PSScriptRoot 'armar_seccion.ps1') -Unpacked $unp -Contenido $contenido -OutDocx $word.FullName -RequireAll
"Respaldo del Word anterior: $copia"
