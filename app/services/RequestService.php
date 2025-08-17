<?php
namespace app\services;

class RequestService
{
    protected static bool $autoDecodeJson = true;

    public static function enableJsonDecoding(bool $enable = true): void
    {
        self::$autoDecodeJson = $enable;
    }

    // Получение значения из $_REQUEST с дефолтом
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_REQUEST[$key] ?? $default;
    }

    // Фильтрация ключей с pipe '|'
    public static function onlyPipeKeys(): array
    {
        return array_filter(
            $_REQUEST,
            fn($value, $key) => str_contains($key, '|'),
            ARRAY_FILTER_USE_BOTH
        );
    }

    // Распаковка параметров с pipe-разделителями в $_REQUEST
    public static function unpackPipeKeysIntoRequest(): void
    {
        $pipeItems = self::onlyPipeKeys();

        foreach ($pipeItems as $key => $value) {
            $keys = array_filter(explode('|', $key));
            if (empty($keys)) continue;

            if ($keys[0] === 'tree') {
                $parsedValue = self::parseValue($value);
                self::assignNested($keys, $parsedValue);
            } else {
                $values = array_filter(explode('|', $value));
                $values = array_pad($values, count($keys), '');
                self::assignFlat($keys, $values);
            }
        }
    }

    protected static function assignNested(array $keys, mixed $value): void
    {
        $current = &$_REQUEST;
        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }
    }

    protected static function assignFlat(array $keys, array $values): void
    {
        foreach ($keys as $i => $k) {
            $_REQUEST[$k] = $values[$i] ?? '';
        }
    }

    // Парсинг строки с возможным JSON-декодированием
    protected static function parseValue(string $value): mixed
    {
        if (!self::$autoDecodeJson) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '') return '';

        $firstChar = $trimmed[0];
        $lastChar = $trimmed[-1];

        if (
            ($firstChar === '{' && $lastChar === '}') ||
            ($firstChar === '[' && $lastChar === ']')
        ) {
            $asArray = $firstChar === '[';
            $decoded = json_decode($trimmed, $asArray);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    // Определение констант из ключей $_REQUEST (из RequestHelper::addConsts)
    public static function addConsts(): array
    {
        foreach ($_REQUEST as $key => $value) {
            if (!defined($key)) {
                define($key, $key);
            }
        }

        $newRequest = [];
        foreach ($_REQUEST as $key => $val) {
            $keys = explode('|', $key);
            $vals = explode('|', $val);
            if (in_array('serialize', $keys)) {
                $unser = @unserialize($val);
                if (is_array($unser)) {
                    foreach ($unser as $k => $v) {
                        $newRequest[$k] = $v;
                    }
                }
                continue;
            }
            if (count($keys) === count($vals)) {
                foreach ($keys as $i => $k) {
                    if ($k) $newRequest[$k] = $vals[$i];
                }
            }
        }
        foreach ($newRequest as $k => $v) {
            $_REQUEST[$k] = $v;
        }
        return $_REQUEST;
    }

    // Подготовка массива данных с добавлением info для контроллера (из RequestHelper::prepare)
    public static function prepare(array $data = []): array
    {
        return array_merge($_REQUEST, $data, [
            'parent_file' => __FILE__,
            'parent_func' => debug_backtrace()[1]['function'] ?? null,
        ]);
    }
}
