$ErrorActionPreference = 'Stop'
$repo = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$stage = Join-Path $repo '.local\migration\live-files'
$target = 'C:\xampp\htdocs\wp'
$manifest = Join-Path $repo '.local\migration\download-failures.json'
if (-not (Test-Path -LiteralPath $manifest) -or @((Get-Content -LiteralPath $manifest -Raw | ConvertFrom-Json)).Count -ne 0) {
    throw 'Complete and verify every live file download before preparing XAMPP.'
}
$config = Get-Content -LiteralPath (Join-Path $target 'wp-config.php') -Raw
if ($config -notmatch "DB_NAME'\s*,\s*'helanak_local'" -or $config -notmatch "WP_ENVIRONMENT_TYPE'\s*,\s*'local'") {
    throw 'The XAMPP target is not configured as the dedicated local clone.'
}
foreach ($item in Get-ChildItem -LiteralPath $stage -Force) {
    if ($item.Name -in @('wp-config.php', '.htaccess')) { continue }
    $destination = Join-Path $target $item.Name
    if ($item.PSIsContainer) {
        & robocopy $item.FullName $destination /E /COPY:DAT /R:1 /W:1 /NFL /NDL /NJH /NJS /NP
        if ($LASTEXITCODE -ge 8) { throw "Copy failed: $($item.Name)" }
    } else {
        Copy-Item -LiteralPath $item.FullName -Destination $destination -Force
    }
}
$originals = Join-Path $repo '.local\migration\runtime-components-before'
New-Item -ItemType Directory -Path $originals -Force | Out-Null
foreach ($component in @(
    @{ Relative='wp-content\themes\wp-helanak-kids-theme'; Source='wp-helanak-kids-theme' },
    @{ Relative='wp-content\plugins\helanak-core-plugin'; Source='helanak-core-plugin' }
)) {
    $componentPath = [IO.Path]::GetFullPath((Join-Path $target $component.Relative))
    if (-not $componentPath.StartsWith($target + '\', [StringComparison]::OrdinalIgnoreCase)) {
        throw 'Component path escaped the explicitly targeted local WordPress root.'
    }
    if (Test-Path -LiteralPath $componentPath) {
        $entry = Get-Item -LiteralPath $componentPath -Force
        if ($entry.Attributes -band [IO.FileAttributes]::ReparsePoint) {
            throw 'A component junction already exists; inspect it before re-running migration.'
        }
        $backup = Join-Path $originals $component.Source
        if (Test-Path -LiteralPath $backup) { throw 'Original component backup already exists.' }
        Move-Item -LiteralPath $componentPath -Destination $backup
    }
    New-Item -ItemType Junction -Path $componentPath -Target (Join-Path $repo $component.Source) | Out-Null
}
Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'local-development-guard.php') -Destination (Join-Path $target 'wp-content\mu-plugins\helanak-local-development.php') -Force
Write-Output 'Live files copied to XAMPP; theme and core plugin now use the repository through local junctions.'
exit 0
