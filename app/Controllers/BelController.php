<?php
namespace app\controllers;
use \R;
use app\services\{RequestService, DatabaseService, FileService, AccessService};
require 'nf_pp_2.php'; //   Обычный Без строки поиска
class BelController extends AppController {
    public $layout = 'chablon_bel';    public $view = 'test';
    
    
        public function actionCachePost2Options(): void
    {
        SelectCacheService::generateUniversalCacheFile([
            'table' => 'post2',
            'valueTemplate' => '{id}',
            'labelTemplate' => '{id_user_name} — {created_fmt} ({shift_label})',
            'orderBy' => 'created DESC',
            'file' => 'data_post2_options.html',
            'optgroupField' => 'id_user_name',
        ]);
    }

    


public function enrichVars(array $data): array {
    // Гарантируем, что 'vars' существует
    $data['vars'] ??= [];
    // Автоматически копируем все НЕ массивы и НЕ зарезервированные ключи в 'vars'
    $reserved = ['vars', 'defined_vars'];
    foreach ($data as $key => $val) {
        if (!in_array($key, $reserved, true) && !isset($data['vars'][$key]) && (is_scalar($val) || $val === null)) {
            $data['vars'][$key] = $val;
        }
    }
    // Устанавливаем имя view, если не задано
    $data['view'] ??= 'index';

    // Упрощённый блок defined_vars (если используешь extract() во view)
    $data['defined_vars'] = [
        'view' => $data['view'],
        'vars' => $data['vars'],
    ];
    return $data;
}

protected function enrichRowWithRelations(array $row, array $customFields = []): array {
    foreach ($row as $key => $value) {
        if (preg_match('/^id_([a-zA-Z0-9_]+)$/', $key, $matches)) {
            $relatedTable = $matches[1];
            if ($value) {
                $relatedRow = \R::findOne($relatedTable, 'id = ?', [$value]);
                if ($relatedRow) {
                    $row[$relatedTable] = $relatedRow->export();
                }
            }
        }
    }

    if (isset($row['created'])) {
        $createdTime = strtotime($row['created']);
        if ($createdTime !== false) {
            $hour = (int)date('H', $createdTime);
            $isFirst = $hour >= 8 && $hour < 20;
            $row['smena'] = match ($customFields['smena'] ?? 'arabic') {
                'roman'  => $isFirst ? 'I' : 'II',
                'label'  => $isFirst ? '(I смена)' : '(II смена)',
                default  => $isFirst ? '1' : '2',
            };
            if (!empty($customFields['created_fmt'])) {
                $row['created_fmt'] = date($customFields['created_fmt'], $createdTime);
            }
        }
    }
    return $row;
}



public function generateUniversalCacheFile(array|string $config, ...$args): string
{
    // Поддержка старого формата вызова
    if (is_string($config)) {
        $keys = ['db', 'table', 'filter', 'groupBy', 'valueTemplate', 'labelTemplate', 'orderBy', 'limit', 'opts'];
        $config = array_combine($keys, array_merge([$config], $args));
    }

    // Значения по умолчанию
    $defaults = [
        'db' => 'bel',
        'table' => '',
        'filter' => [],
        'groupBy' => null,
        'optgroup' => null,
        'valueTemplate' => '{id}',
        'labelTemplate' => '{title}',
        'orderBy' => 'id ASC',
        'limit' => 0,
        'opts' => [],
    ];
    $config = array_merge($defaults, $config);
    extract($config);

    \app\services\DatabaseService::connect($db);

    // Сбор SQL-запроса
    $sql = "SELECT * FROM `$table`";
    $params = [];

    if (!empty($filter)) {
        $clauses = [];
        foreach ($filter as $field => $condition) {
            if (is_array($condition)) {
                $operator = strtoupper(trim($condition[0]));
                $value = $condition[1] ?? null;
                if (in_array($operator, ['IS', 'IS NOT'])) {
                    $clauses[] = "$field $operator NULL";
                } else {
                    $clauses[] = "$field $operator ?";
                    $params[] = $value;
                }
            } else {
                $clauses[] = "$field = ?";
                $params[] = $condition;
            }
        }
        $sql .= ' WHERE ' . implode(' AND ', $clauses);
    }

    if (!empty($orderBy)) {
        $sql .= " ORDER BY $orderBy";
    }

    if (!empty($limit)) {
        $sql .= " LIMIT " . intval($limit);
    }

    $rows = \R::getAll($sql, $params);

    // Обогащение данных
    foreach ($rows as &$row) {
        // Форматированная дата и смена
        if (!empty($row['created'])) {
            $timestamp = strtotime($row['created']);
            $row['created_fmt'] = date('Y-m-d', $timestamp);
            $hour = (int)date('H', $timestamp);
            $row['shift'] = ($hour >= 20 || $hour < 8) ? 2 : 1;
            $row['shift_roman'] = $row['shift'] === 1 ? 'I' : 'II';
            $row['shift_label'] = $row['shift'] === 1 ? '1 смена' : '2 смена';
        }

        // Автоматическая подгрузка связанных таблиц
        foreach ($row as $k => $v) {
            if (preg_match('/^id_([a-z_]+)$/', $k, $m)) {
                $relatedTable = $m[1];
                $name = \R::getCell("SELECT title FROM $relatedTable WHERE id = ? LIMIT 1", [$v]) ?:
                        \R::getCell("SELECT name FROM $relatedTable WHERE id = ? LIMIT 1", [$v]) ?: '';
                $row["{$relatedTable}_name"] = $name;
            }
        }
    }

    // Группировка
    $optgroups = [];
    foreach ($rows as $row) {
        $groupLabel = '';

        // 1. Приоритет optgroup
        if ($optgroup && isset($row[$optgroup])) {
            $groupLabel = $row[$optgroup];

        // 2. Если groupBy = id_xxx — вытаскиваем связанный name
        } elseif ($groupBy && preg_match('/^id_([a-z_]+)$/', $groupBy, $m)) {
            $relatedTable = $m[1];
            $relatedId = $row[$groupBy] ?? null;
            if ($relatedId) {
                $groupLabel = \R::getCell("SELECT title FROM $relatedTable WHERE id = ? LIMIT 1", [$relatedId]) ?:
                              \R::getCell("SELECT name FROM $relatedTable WHERE id = ? LIMIT 1", [$relatedId]) ?:
                              "ID $relatedId";
            }

        // 3. Просто значение поля groupBy
        } elseif ($groupBy && isset($row[$groupBy])) {
            $groupLabel = $row[$groupBy];
        }

        $value = $this->applyTemplate($valueTemplate, $row);
        $label = $this->applyTemplate($labelTemplate, $row);

        if ($groupLabel) {
            $optgroups[$groupLabel][] = "<option value=\"$value\">$label</option>";
        } else {
            $optgroups[''][] = "<option value=\"$value\">$label</option>";
        }
    }

    // Сбор HTML
    $html = '';
    foreach ($optgroups as $label => $options) {
        if ($label !== '') {
            $html .= "<optgroup label=\"" . htmlspecialchars($label) . "\">\n" . implode("\n", $options) . "\n</optgroup>\n";
        } else {
            $html .= implode("\n", $options) . "\n";
        }
    }

    // Сохраняем файл
    $fileName = "{$db}_{$table}.html";
    $filePath = PROJECT_ROOT . "/tmp/cache/select/{$fileName}";

    if (!is_dir(dirname($filePath))) {
        mkdir(dirname($filePath), 0777, true);
    }

        file_put_contents($filePath, $html);
unset($rows, $row, $params, $sql, $optgroups, $label, $value, $groupLabel);

    return $html;
    
}


public function generateUniversalCacheFile_ytytyty(array|string $config, ...$args): string
{
    // Совместимость со старым форматом вызова
    if (is_string($config)) {
        $keys = ['db', 'table', 'filter', 'groupBy', 'valueTemplate', 'labelTemplate', 'orderBy', 'limit', 'opts'];
        $config = array_combine($keys, array_merge([$config], $args));
    }

    // Установка значений по умолчанию
    $defaults = [
        'db' => 'bel',
        'table' => '',
        'filter' => null,
        'groupBy' => null,
        'valueTemplate' => '{id}',
        'labelTemplate' => '{title}',
        'orderBy' => 'id ASC',
        'limit' => 0,
        'opts' => [],
    ];
    $config = array_merge($defaults, $config);
    extract($config);
    \app\services\DatabaseService::connect($db);
    $sql = "SELECT * FROM `$table`";
    $params = [];

    if (!empty($filter) && is_array($filter)) {
        $conditions = [];
        foreach ($filter as $field => $value) {
            $conditions[] = "`$field` = ?";
            $params[] = $value;
        }
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    if (!empty($orderBy)) {
        $sql .= " ORDER BY $orderBy";
    }

    if (!empty($limit)) {
        $sql .= " LIMIT " . intval($limit);
    }

    $rows = \R::getAll($sql, $params);

    // Дополнительно: преобразовать даты и смены
    foreach ($rows as &$row) {
        // Добавляем форматированную дату
        if (!empty($row['created'])) {
            $timestamp = strtotime($row['created']);
            $row['created_fmt'] = date('Y-m-d', $timestamp);
            $hour = (int)date('H', $timestamp);
            $row['shift'] = $hour >= 20 || $hour < 8 ? 2 : 1;
            $row['shift_roman'] = $row['shift'] === 1 ? 'I' : 'II';
            $row['shift_label'] = $row['shift'] === 1 ? '1 смена' : '2 смена';
        }

        // Автозагрузка связанных названий по id_*
        foreach ($row as $k => $v) {
            if (preg_match('/^id_([a-z_]+)$/', $k, $m)) {
                $relatedTable = $m[1];
                $name = \R::getCell("SELECT title FROM `$relatedTable` WHERE id = ? LIMIT 1", [$v]) ?: 
                        \R::getCell("SELECT name FROM `$relatedTable` WHERE id = ? LIMIT 1", [$v]) ?: '';
                $row["{$relatedTable}_name"] = $name;
            }
        }
    }

    // Группировка
    $grouped = [];
    foreach ($rows as $row) {
        $groupKey = $groupBy ? $row[$groupBy] ?? '—' : null;
        $grouped[$groupKey ?? ''][] = $row;
    }

    // Генерация HTML
    $html = '';
    foreach ($grouped as $group => $items) {
        if ($groupBy !== null) {
            $html .= '<optgroup label="' . htmlspecialchars($group) . '">' . PHP_EOL;
        }

        foreach ($items as $row) {
            $value = $this->applyTemplate($valueTemplate, $row);
            $label = $this->applyTemplate($labelTemplate, $row);
            $html .= '<option value="' . $value . '">' . $label . '</option>' . PHP_EOL;
        }

        if ($groupBy !== null) {
            $html .= '</optgroup>' . PHP_EOL;
        }
    }

    return $html;
}


protected function normalizeRowData(array $row): array {
    // Формат даты
    if (isset($row['created'])) {
        $row['created_fmt'] = date('Y-m-d', strtotime($row['created']));
    }

    // Смена
    if (isset($row['shift'])) {
        $shift = (int)$row['shift'];
        $romanMap = [1 => 'I', 2 => 'II', 3 => 'III'];
        $row['shift'] = [
            'arabic' => $shift,
            'roman' => $romanMap[$shift] ?? (string)$shift,
            'label' => $shift . ' смена',
        ];
    }

    // Автозагрузка связанных полей по ключу вида id_xxx
    foreach ($row as $key => $value) {
        if (preg_match('/^id_([a-z_]+)$/', $key, $m)) {
            $table = $m[1];
            try {
                $related = \R::load($table, (int)$value);
                if ($related && $related->id) {
                    $row[$table] = [
                        'name'  => $related->name  ?? null,
                        'title' => $related->title ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                // Просто пропускаем, если таблицы нет
            }
        }
    }

    return $row;
}

protected function applyTemplate(string $template, array $row): string {
    return preg_replace_callback('/\{([a-z0-9_.]+)\}/i', function ($matches) use ($row) {
        $key = $matches[1];

        // Специальные переменные
        if ($key === 'created_fmt' && !empty($row['created'])) {
            return htmlspecialchars(date('Y-m-d', strtotime($row['created'])), ENT_QUOTES);
        }

        if (str_starts_with($key, 'shift_')) {
            $format = match ($key) {
                'shift_roman'  => 'roman',
                'shift_label'  => 'label',
                'shift_arabic' => 'arabic',
                default        => 'arabic',
            };
            return htmlspecialchars($this->formatShift($row['created'] ?? '', $format), ENT_QUOTES);
        }

        // Доступ к вложенным значениям (например: {user.name}, {avto.title})
        $path = explode('.', $key);
        $value = $row;
        foreach ($path as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return ''; // значение не найдено
            }
        }

        return htmlspecialchars((string)$value, ENT_QUOTES);
    }, $template);
}


protected function formatShift(string $created, string $format = 'roman'): string {
    if (!$created) return '';

    $hour = (int)date('H', strtotime($created));
    $shift = ($hour >= 20 || $hour < 8) ? 2 : 1;

    return match ($format) {
        'roman'  => $shift === 1 ? 'I' : 'II',
        'label'  => $shift === 1 ? '1 смена' : '2 смена',
        'arabic' => (string)$shift,
        default  => (string)$shift,
    };
}




     /**
     * Проверка, что запрос AJAX
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }   
    
    public function addConsts() {
        foreach ($_REQUEST as $key => $value) {
            $this->arr[$key] = $value;
            if (!defined($key)) define($key, $key);
        }

        $newRequest = [];
        foreach ($_REQUEST as $key => $val) {
            $keys = explode('|', $key);
            $vals = explode('|', $val);
            if (in_array('serialize', $keys)) {
                $unser = @unserialize($val);
                if (is_array($unser)) foreach ($unser as $k => $v) $newRequest[$k] = $v;
                continue;
            }
            if (count($keys) === count($vals)) {
                foreach ($keys as $i => $k) if ($k) $newRequest[$k] = $vals[$i];
            } else {
                error_log("⚠️ Несовпадение ключей/значений: $key = $val");
            }
        }
        foreach ($newRequest as $k => $v) $_REQUEST[$k] = $v;
        return $_REQUEST;
    }

    public function totableAction() {
        $debug = false;
        if ($debug) debug(['__FILE__' => __FILE__, '$_REQUEST' => $_REQUEST]);

        $dbName = $_REQUEST['dbname'] ?? 'bel';
        DatabaseService::connect($dbName);
        $this->layout = 'default_workz';

        $posts = $_REQUEST;
        $posts['dbname'] = $dbName;
        $posts['tablname'] = $_REQUEST['tablname'] ?? 'post2';
        $posts['cell'] = 'ok';

        $redsInput = $_REQUEST['reds'] ?? '';
        $reds = !empty($redsInput)
            ? array_unique(array_filter(array_map('trim', preg_split('/[,|]/', $redsInput))))
            : ['name', 'tos', 'num'];

        if (!in_array('name', $reds)) $reds[] = 'name';
        if (!empty($_REQUEST['column']) && !in_array($_REQUEST['column'], $reds)) {
            $reds[] = $_REQUEST['column'];
            $posts['column'] = $_REQUEST['column'];
        }

        array_unshift($reds, 'id');
        $posts['reds'] = $reds;
//        if ($debug) {debug(['$posts' => $posts]);}
        $data = $this->prepare();
        $this->loadCommonViews($data);
        $this->prepareView('totable', 'default_pers', ['posts' => $posts]);
    }

    public function updatecellAction() {
        if ($this->isAjax()) addConsts();
        $post = $_REQUEST;
        $post['pst'] = $_REQUEST;
        $this->prepareView('updatecell', 'default_shajax_bel', $post);
    }

    public function debugersAction() {
        $this->arr = $this->addConsts();
        $this->view = 'debuger';
        $data = $this->prepare();
        $this->prepareView('diagnostics', 'chablon_bel', $data);
    }
    public function parsAction() {
        $this->arr = $this->addConsts();
        $this->view = 'debuger';
        $data = $this->prepare();
        $this->prepareView('pars', 'chablon_bel_pars', $data);
    }


    public function setavechecklistAction() {
        $this->arr = $this->addConsts();
        $this->view = 'debuger';
        $data = $this->prepare();
        $this->prepareView('debuger', 'chablon_bel', $data);
    }


    public function debugerAction() {
        $this->arr = $this->addConsts();
        $this->view = 'debuger';
        $data = $this->prepare();
//        $this->set(['posts' => $data]);
                $this->prepareView('debug', 'chablon_bel', $data);

    }    
    
    public function debugAction() {
        $this->arr = $this->addConsts();
        $this->view = 'debuger';
        $data = $this->prepare();
//      $this->set(['posts' => $data]);
        $this->prepareView('debug', 'chablon_bel', $data);
    }    
    
    
    
    public function checklistAction() {
    $this->arr = $this->addConsts();
    $this->view = 'debuger';
    $data = $this->prepare();

    if ($data['parent_func'] == 'indexAction') {
        $loadViews = 'htmlstart';
    } else {
        $loadViews = 'htmlstart_' . explode("Action", $data['parent_func'])[0];
    }
    $data['loadCommonViews'] = [$loadViews, 'htmldiv', 'index'];
    debug($data);
    // 🟢 Сначала задаём переменные
    $this->prepareView('checklist', 'chablon_bel', $data);

    // 🔄 Потом грузим представления
    $this->loadCommonViews($data);
}





    private function prepare(array $data = []): array {
        return array_merge($_REQUEST, $data, [
            'parent_file' => __FILE__,
            'parent_func' => debug_backtrace()[1]['function']
        ]);
    }

    private function prepareView(string $viewName, string $layout = 'default_chablon', array $data = []): void {
        $data['parent_file'] = __FILE__;
        $data['parent_func'] = debug_backtrace()[1]['function'];
//        $data['_source'] = $data['parent_func'] ?? 'unknown';
    $data['_source'] = 'prepareView'; // явно, откуда данные

    // Делаем доступным для шаблонов напрямую через $_REQUEST
//    $_REQUEST['_source'] = $data['_source'];
    
    if ($this->isAjax()) addConsts();
        $this->layout = $layout;
        $this->view = $viewName;
        $this->set(['datas' => array_merge($_REQUEST, $data)]);

    }

    public function viewAction(): void {
        $this->view = 'debug';
        $data = $this->prepare();
        $this->set(['posts' => $data]);
    }
    
            public function indexAction(): void {
//    debug($_SESSION);
        $id_schem = RequestService::get('id_schem', 1);
        $sdb = RequestService::get('sdb', 'bel');
        $stbl = 'ins5';
        $page = RequestService::get('page', 'a48a504117cef7f8c7d606cf4908e711');
        DatabaseService::connect($sdb);
        $access = AccessService::hashAccess();
        $uploadPath = PROJECT_ROOT . "/public/ajaxupload/{$sdb}/{$stbl}/{$id_schem}";
        FileService::makeDirs($uploadPath);
        $data['glob'] = FileService::searchFiles($uploadPath . '/' . ARRDOWN);
//$this->generateUniversalCacheFile(
//    'bel',
//    'post2',
//    null,
//    'num',
//    '{id}|{post}|{num}',
//    '[{created_fmt}] => {smena} => {ur.name} => {tos} => {name}',
//    'created DESC',
//    0,
//    [
//        'created' => ['IS NOT', 'NULL'],
//        'id' => ['>', 0]
//    ],
//    [
//        'smena' => 'roman', // или 'label'
//        'created_fmt' => 'd.m.Y'
//    ]
//);
//$this->generateUniversalCacheFile([
//    'db' => 'bel',
//    'table' => 'post2',
//    'groupBy' => 'num',
//    'valueTemplate' => '{id}|{user_name}|{num}',
//    'labelTemplate' => '[{created_fmt}/({shift_roman})] => {user_name} => {tema} => {short}',
//    'orderBy' => 'created DESC',
//]);


$this->generateUniversalCacheFile([
    'table' => 'post2',
    'valueTemplate' => '{id}||{num}',
    'groupBy' => 'num',
    'labelTemplate' => '[{created_fmt}] №{num} / ТО: {tos}  => {ur_name} =>  {name}',
    'filter' => [
        'created' => ['IS NOT', 'NULL'],
    ],
    'dateField' => 'created',
    'formatDateFieldAs' => 'Y-m-d',
    'orderBy' => 'created DESC',

]);

//        $data['elems'] = R::findAll('elems', 'md5 LIKE ? ORDER BY grup DESC LIMIT 2', ['%' . $page . '%']);
        $data += ['hi' => 'hello world', 'data_inc' => 'data_inc_bel', 'parent_file' => __FILE__, 'parent_func' => __FUNCTION__, 'sdb' => $sdb, 'id_schem' => $id_schem];
        //  debug($data);
        $this->loadCommonViews($data);
        $this->prepareView('index', 'chablon_bel', $data);
//        $data['data_inc']='ur';
//        $this->prepareView('index', 'chablon_bel', $data);
    }    
    
private function loadCommonViews(array $data): void {
    // Устанавливаем источник, если не указан
    $data['_source'] ??= $data['parent_func'] ?? 'indexAction';
    // Загружаем шапку в зависимости от действия
    if (($data['parent_func'] ?? '') === 'indexAction') {
        $this->loadView('htmlstart', $data);
    } else {
        $base = explode("Action", $data['parent_func'])[0] ?? 'unknown';
        $this->loadView('htmlstart_' . $base, $data);
    }
    // Загружаем дополнительный div
    $this->loadView('htmldiv', $data);
    // Первый набор данных и вызов index с data_inc = 'data_inc_bel_ur'
    $data1 = $data;
    $data1['data_inc'] = 'data_inc_bel_ur';
    $data1['data-placeholder'] = 'Выберите наладчика';
    $data1['onchange'] = 'off';
    $data1 = $this->enrichVars($data1);
//    $this->loadView('select', $data1);
    
    // Первый набор данных и вызов index с data_inc = 'data_inc_bel_ur'
//    $this->generateUniversalCacheFile(
//    'bel',
//    'avto',
//    null,
//    'num',
//    '{id}',
//    '{title}',
//    'created DESC',
//    0,
//    [
//        'title' => ['IS NOT', 'NULL'],
//        'id' => ['>', 0]
//    ]
//);
    
$this->generateUniversalCacheFile([
    'db' => 'bel',
    'table' => 'avto',
    'groupBy' => null,
    'valueTemplate' => '{id}',
    'labelTemplate' => '[{id})] => {title}',
    'orderBy' => 'id DESC',
]);
    
    $data3 = $data;
    $data3['data_inc'] = 'data_inc_bel_avto';
    $data3['data-placeholder'] = 'Выберите машину';
    $data3['onchange'] = 'off';
    $data3 = $this->enrichVars($data3);
//    $this->loadView('select', $data3);
// Второй набор данных и вызов index с data_inc = 'data_inc_bel_post2'
//    $data2 = $data;
//    $data2['data_inc'] = 'data_inc_bel_post2';
//    $data2 = $this->enrichVars($data2);
//    $this->loadView('index', $data2);
}    
    
    
}
