# Перейти в корень репозитория
cd D:\Projects\Work\myproject

# Папка для экспорта (не под контролем git)
$EXPORT_DIR = "repo_exports"
if (-not (Test-Path $EXPORT_DIR)) {
    New-Item -ItemType Directory -Path $EXPORT_DIR | Out-Null
}

# Имя файла экспорта с таймстампом
$OUTPUT_FILE = "$EXPORT_DIR/repo_export_$(Get-Date -Format 'yyyyMMdd_HHmmss').json"

# Получаем все ветки
git fetch --all
$branches = git branch -r | Where-Object { $_ -notmatch 'HEAD' } | ForEach-Object { $_.Trim() }

# Создаём список для результата
$result = [System.Collections.Generic.List[Object]]::new()

foreach ($branch in $branches) {
    # Список изменённых файлов относительно main
    $diff = git diff --name-status main $branch 2>$null

    $added = @()
    $modified = @()
    $deleted = @()
    $unique = [PSCustomObject]@{
        php_controllers = @()
        nodejs          = @()
        configs         = @()
        php_services    = @()
        other           = @()
        php_models      = @()
        views           = @()
    }

    foreach ($line in $diff) {
        if ($line -match "^\s*(A|M|D)\s+(.*)$") {
            $status = $matches[1]
            $file = $matches[2]

            # Пропускаем лишние папки
            if ($file -match "node_modules|logs") { continue }

            switch ($status) {
                "A" {
                    $added += $file
                    $unique_category = $file.Split('/')[0]
                    if ($unique.PSObject.Properties.Name -contains $unique_category) {
                        $unique.$unique_category += $file
                    } else {
                        $unique.other += $file
                    }
                }
                "M" { $modified += $file }
                "D" { $deleted += $file }
            }
        }
    }

    # Убираем пустые категории
    $unique_nonempty = @{}
    foreach ($cat in $unique.PSObject.Properties.Name) {
        if ($unique.$cat.Count -gt 0) {
            $unique_nonempty[$cat] = $unique.$cat -join " "
        }
    }

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

# Создаём словарь различий между ветками
$branchDiffs = @{}
foreach ($b1 in $result) {
    $diffs = @{}
    foreach ($b2 in $result) {
        if ($b1.branch -ne $b2.branch) {
            $uniqueFiles = @{}
            foreach ($cat in $b1.unique_files.PSObject.Properties.Name) {
                if ($b1.unique_files.$cat -and $b1.unique_files.$cat -ne $b2.unique_files.$cat) {
                    $uniqueFiles[$cat] = $b1.unique_files.$cat
                }
            }
            if ($uniqueFiles.Count -gt 0) { $diffs[$b2.branch] = $uniqueFiles }
        }
    }
    $branchDiffs[$b1.branch] = $diffs
}

# Итоговый объект
$finalResult = [PSCustomObject]@{
    branches = $result
    branch_differences = $branchDiffs
}

# Сериализация в JSON с минимизацией
$finalResult | ConvertTo-Json -Compress -Depth 6 | Out-File $OUTPUT_FILE -Encoding UTF8

Write-Output "Export with branch differences saved to $OUTPUT_FILE"
