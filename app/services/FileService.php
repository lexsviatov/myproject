<?php
namespace app\services;

class FileService
{
    public static function makeDirs(string ...$paths): void
    {
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }

    public static function searchFiles(string $dir, string $pattern = '*'): array
    {
        return glob($dir . DIRECTORY_SEPARATOR . $pattern, GLOB_BRACE);
    }
}
