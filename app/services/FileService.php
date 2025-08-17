<?php
namespace app\services;

class FileService
{
    /** Создает директории, если их нет */
    public static function makeDirs(string ...$paths): void
    {
        foreach ($paths as $p) is_dir($p) || mkdir($p, 0777, true);
    }

    /** Возвращает файлы по шаблону в директории */
    public static function searchFiles(string $dir, string $pattern = '*'): array
    {
        return glob($dir . DIRECTORY_SEPARATOR . $pattern, GLOB_BRACE) ?: [];
    }
}
