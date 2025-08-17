<?php
namespace app\services;

class AccessService
{
public static function hashAccess(): string
{
    $user = \R::findOne('sesuser', 'user = ?', [1]);

    if (!$user) {
        // Можно возвращать пустой хэш или какой-то дефолт
        return hash('sha256', 'default_access');
        // или просто бросать ошибку, если нужно строгое поведение
        // throw new \RuntimeException('User with ID 1 not found in sesuser table');
    }

    return hash('sha256', json_encode($user->export()));
}

}
