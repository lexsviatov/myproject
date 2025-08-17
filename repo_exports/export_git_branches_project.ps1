# ------------------------------------------------------------
# export_git_branches_full.ps1 для AI-слияния
# ------------------------------------------------------------

cd D:\Projects\Work\myproject

$EXPORT_DIR = "repo_exports"
if (-not (Test-Path $EXPORT_DIR)) { New-Item -ItemType Directory -Path $EXPORT_DIR | Out-Null }

$timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$OUTPUT_FILE = "$EXPORT_DIR/repo_export_full_$timestamp.json"

# Бэкап последнего экспорта
$lastExport = Get-ChildItem $EXPORT_DIR -Filter "repo_export_*.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if ($lastExport) {
    $backupFile = "$EXPORT_DIR/backup_$($lastExport.Name)"
    Copy-Item $lastExport.FullName $backupFile
    Write-Output "Backup of last export saved to $backupFile"
}

# Получаем все ветки
git fetch --all
$branches = git branch -r | Where-Object { $_ -notmatch 'HEAD' } | ForEach-Object { $_.Trim() }

$result = [System.Collections.Generic.List[Object]]::new()

# Сравнение каждой ветки с base branch
$baseBranch = "origin/project-structure"

foreach ($branch in $branches) {
    $diff = git diff --name-status $baseBranch $branch 2>$null

    $added = @(); $modified = @(); $deleted = @()
    $unique = [PSCustomObject]@{
        php_controllers = @(); nodejs = @(); configs = @(); php_services = @(); other = @(); php_models = @(); views = @()
    }

    foreach ($line in $diff) {
        if ($line -match "^\s*(A|M|D)\s+(.*)$") {
            $status = $matches[1]; $file = $matches[2]
            if ($file -match "node_modules|logs") { continue }

            switch ($status) {
                "A" { $added += $file; $cat = $file.Split('/')[0]; if ($unique.PSObject.Properties.Name -contains $cat) { $unique.$cat += $file } else { $unique.other += $file } }
                "M" { $modified += $file }
                "D" { $deleted += $file }
            }
        }
    }

    $unique_nonempty = @{}
    foreach ($cat in $unique.PSObject.Properties.Name) { if ($unique.$cat.Count -gt 0) { $unique_nonempty[$cat] = $unique.$cat -join " " } }

    $branchObj = [PSCustomObject]@{
        branch       = $branch
        summary      = [PSCustomObject]@{
            added        = $added.Count
            modified     = $modified.Count
            deleted      = $deleted.Count
            unique_files = $unique_nonempty.Count
        }
        added        = $added -join " "
        modified     = $modified -join " "
        deleted      = $deleted -join " "
        unique_files = $unique_nonempty
    }

    $result.Add($branchObj)
}

# Подробные различия между ветками
$branchDiffs = @{}
foreach ($b1 in $result) {
    $diffs = @{}
    foreach ($b2 in $result) {
        if ($b1.branch -ne $b2.branch) {
            $uniqueFiles = @{}
            foreach ($cat in $b1.unique_files.PSObject.Properties.Name) {
                if ($b1.unique_files.$cat -and $b1.unique_files.$cat -ne $b2.unique_files.$cat) { $uniqueFiles[$cat] = $b1.unique_files.$cat }
            }
            if ($uniqueFiles.Count -gt 0) { $diffs[$b2.branch] = $uniqueFiles }
        }
    }
    $branchDiffs[$b1.branch] = $diffs
}

# Добавим общий diff для AI: base_branch → остальные ветки
$branchDiffsSummary = @{}
foreach ($branch in $branches) {
    if ($branch -ne $baseBranch) {
        $branchDiffsSummary[$branch] = git diff --name-status $baseBranch $branch | ForEach-Object { $_.Trim() }
    }
}

$finalResult = [PSCustomObject]@{
    base_branch        = $baseBranch
    branches           = $result
    branch_differences = $branchDiffs
    diff_summary       = $branchDiffsSummary
}

$finalResult | ConvertTo-Json -Compress -Depth 8 | Out-File $OUTPUT_FILE -Encoding UTF8
Write-Output "Full AI export saved to $OUTPUT_FILE"
