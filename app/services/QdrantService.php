<?php
namespace app\services;

use \R;

class DatabaseService
{
    protected static array $connections = [];

public static function connect(string $dbName = 'bel'): void
{
    $dbName = strtolower($dbName); // 👈 Добавить это
    if (!isset(self::$connections[$dbName])) {
        R::addDatabase($dbName, "mysql:host=localhost;dbname=$dbName", "root", PBD);
        self::$connections[$dbName] = true;
    }

    R::selectDatabase($dbName);
}

}
