$ErrorActionPreference = 'Stop'
$env:PATH = 'C:\xampp\mysql\bin;' + $env:PATH
$wpCli = Join-Path $PSScriptRoot '..\.local\tools\wp-cli.phar'
& 'C:\xampp\php\php.exe' '-d' 'memory_limit=512M' $wpCli '--path=C:/xampp/htdocs/wp' @args
exit $LASTEXITCODE
