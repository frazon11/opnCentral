param([string]$RepoPath = ".")

$ErrorActionPreference = "Stop"
$header = Join-Path $RepoPath "app\inc\header.php"

if (-not (Test-Path $header)) {
    throw "File not found: $header"
}

$content = Get-Content -Raw -Encoding UTF8 $header

if ($content -match 'opncentral-version') {
    Write-Host "Version display already exists."
    exit 0
}

$backup = "$header.before-v0.1.9"
Copy-Item $header $backup -Force

$versionHtml = '<span class="opncentral-version" style="display:block;font-size:11px;line-height:1.2;opacity:.65;margin-top:2px;">v0.1.9</span>'

$patterns = @(
    '(?<title>>\s*opnCentral\s*</a>)',
    '(?<title>>\s*OpnCentral\s*</a>)',
    '(?<title>>\s*opnCentral\s*</div>)',
    '(?<title>>\s*OpnCentral\s*</div>)',
    '(?<title>>\s*opnCentral\s*</h1>)',
    '(?<title>>\s*OpnCentral\s*</h1>)'
)

$changed = $false

foreach ($pattern in $patterns) {
    if ($content -match $pattern) {
        $content = [regex]::Replace(
            $content,
            $pattern,
            { param($m) $m.Groups["title"].Value + $versionHtml },
            1
        )
        $changed = $true
        break
    }
}

if (-not $changed) {
    throw "The opnCentral title was not found. header.php was not changed."
}

Set-Content -Path $header -Value $content -Encoding UTF8 -NoNewline
Write-Host "Added v0.1.9 below the opnCentral title."
Write-Host "Backup: $backup"
