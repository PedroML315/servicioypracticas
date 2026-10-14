param([string]$Docx, [string]$Pdf, [string]$PidFile)
# Exporta el documento a PDF con Word (sin abrirlo para edicion).
$ErrorActionPreference = 'Stop'
$before = @(Get-Process WINWORD -ErrorAction SilentlyContinue | ForEach-Object Id)
$wd = New-Object -ComObject Word.Application
$mine = @(Get-Process WINWORD -ErrorAction SilentlyContinue | Where-Object { $before -notcontains $_.Id } | ForEach-Object Id)
Set-Content -LiteralPath $PidFile -Value ($mine -join ',')
$wd.Visible = $false
$wd.DisplayAlerts = 0
try {
  $doc = $wd.Documents.Open($Docx, $false, $true)
  # 17 = wdExportFormatPDF; 0 = optimizado para impresion; 0 = todo el documento;
  # 1 = crear marcadores desde los titulos (el PDF queda con su indice lateral)
  $doc.ExportAsFixedFormat($Pdf, 17, $false, 0, 0, 1, 1, 0, $true, $true, 1)
  $doc.Close(0)
  'PDF exportado'
} finally {
  $wd.Quit()
  [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($wd)
}
