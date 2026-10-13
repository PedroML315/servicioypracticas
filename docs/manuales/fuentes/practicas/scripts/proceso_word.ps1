param([string]$Docx, [string]$PartesTxt, [string]$TextosJson, [string]$Pdf, [string]$Dir)
# Une varios Word en uno ($Docx, que debe ser una copia de una parte para heredar estilos) y exporta el PDF.
# Cada paso corre en un Word oculto con limite de tiempo (correr.ps1):
# unir -> saltos -> fin_indice -> ajuste_parte1 -> vacios -> hojas -> pdf.
$ErrorActionPreference = 'Stop'
$sc = $PSScriptRoot
$repo = (Resolve-Path (Join-Path $sc '..\..\..\..\..')).Path
$logo = Join-Path $repo 'view\assets\images\logo-color.png'
if (Test-Path $Pdf) { [System.IO.File]::Delete($Pdf) }

function Paso([string]$nombre, [string]$script, [object[]]$args2, [int]$t = 560) {
  "== $nombre"
  $pf = Join-Path $Dir "w_$nombre.pid"
  & (Join-Path $sc 'correr.ps1') -Script (Join-Path $sc $script) -Args2 ($args2 + $pf) -Timeout $t -PidFile $pf
}
Paso 'unir' 'unir.ps1' @($Docx, $PartesTxt, $TextosJson, $logo)
Paso 'saltos' 'saltos.ps1' @($Docx)
Paso 'fin_indice' 'fin_indice.ps1' @($Docx)
Paso 'ajuste_parte1' 'ajuste_parte1.ps1' @($Docx)
Paso 'vacios' 'vacios.ps1' @($Docx)
Paso 'hojas' 'hojas.ps1' @($Docx)
Paso 'pdf' 'pdf.ps1' @($Docx, $Pdf)
if (-not (Test-Path $Pdf)) { throw 'No se genero el PDF' }
$t = [System.Text.Encoding]::ASCII.GetString([System.IO.File]::ReadAllBytes($Pdf))
"PDF {0:N1} MB | {1} paginas | {2} marcadores" -f ((Get-Item $Pdf).Length / 1MB), ([regex]::Matches($t, '/Type\s*/Page[^s]')).Count, ([regex]::Matches($t, '/Title')).Count
