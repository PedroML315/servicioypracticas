param([string]$WordDir = 'C:\Users\Desarrollo\Desktop\SP', [string]$Trabajo = (Join-Path $env:TEMP 'manuales_pp'))
# Une las 10 partes (PASO 1..10.docx en $WordDir) en el tutorial completo de Practicas Profesionales,
# con portada, indice, numero de pagina y marcadores (textos de tutorial.json). Deja el Word y el PDF
# en $WordDir y el PDF en docs\manuales\practicas\tutorial_completo.pdf.
$ErrorActionPreference = 'Stop'
$sc = $PSScriptRoot
$kit = Split-Path -Parent $sc
$repo = (Resolve-Path (Join-Path $sc '..\..\..\..\..')).Path
$utf8 = New-Object System.Text.UTF8Encoding($false)
$dir = Join-Path $Trabajo 'tutorial'
if (Test-Path $dir) { [System.IO.Directory]::Delete($dir, $true) }
[void](New-Item -ItemType Directory -Force $dir)

$partes = foreach ($n in 1..10) {
  $w = Get-ChildItem -LiteralPath $WordDir -Filter "*PASO $n.docx" | Select-Object -First 1
  if (-not $w) { throw "No encuentro el Word de la parte $n en $WordDir" }
  $w.FullName
}
[System.IO.File]::WriteAllLines((Join-Path $dir 'partes.txt'), $partes, $utf8)
$docx = Join-Path $dir 'tutorial_completo.docx'
$pdf = Join-Path $dir 'tutorial_completo.pdf'
Copy-Item -LiteralPath $partes[0] $docx -Force
& (Join-Path $sc 'proceso_word.ps1') -Docx $docx -PartesTxt (Join-Path $dir 'partes.txt') -TextosJson (Join-Path $kit 'tutorial.json') -Pdf $pdf -Dir $dir

$nombre = 'PR' + [char]0x00C1 + 'CTICAS PROFESIONALES - Tutorial completo'
Copy-Item $docx (Join-Path $WordDir "$nombre.docx") -Force
Copy-Item $pdf (Join-Path $WordDir "$nombre.pdf") -Force
Copy-Item $pdf (Join-Path $repo 'docs\manuales\practicas\tutorial_completo.pdf') -Force
"Copiado a: $WordDir y docs\manuales\practicas\tutorial_completo.pdf"
