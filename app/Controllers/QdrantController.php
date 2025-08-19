<?php
namespace app\controllers;

use \R;
use app\services\RequestService;
use app\services\DatabaseService;
use app\services\FileService;
use app\services\AccessService;

class ChablonController extends AppController {
    public $layout = 'chablon';
    public $view = 'test';

    /**
     * Универсальный метод для подготовки данных
     */
    private function prepare(array $data = []): array {
        return array_merge($_REQUEST, $data, [
            'parent_file' => __FILE__,
            'parent_func' => debug_backtrace()[1]['function']
        ]);
    }

    /**
     * Универсальный метод для подготовки представления
     */
    private function prepareView(string $viewName, string $layout = 'default_chablon', array $data = []): void {
        $data['parent_file'] = __FILE__;
        $data['parent_func'] = debug_backtrace()[1]['function'];

        if ($this->isAjax()) {
            addConsts(); // если есть глобальные константы
        }

        $this->layout = $layout;
        $this->view = $viewName;
        $this->set(['datas' => array_merge($_REQUEST, $data)]);
    }

    /**
     * Главная страница
     */
    public function indexAction(): void {
        // Параметры запроса
        $id_schem = RequestService::get('id_schem', 1);
        $sdb      = RequestService::get('sdb', 'bel');
        $stbl     = 'ins5';
        $page     = RequestService::get('page', 'a48a504117cef7f8c7d606cf4908e711');

        // Подключение к нужной БД через сервис
        DatabaseService::connect($sdb);

        // Проверка доступа
        $access = AccessService::hashAccess();
        debug(['$access', $access]);

        // Подготовка путей
        $uploadPath = PROJECT_ROOT . "/public/ajaxupload/{$sdb}/{$stbl}/{$id_schem}";
        FileService::makeDirs($uploadPath);

        // Поиск файлов
        $data['glob'] = FileService::searchFiles($uploadPath . '/' . ARRDOWN);
//        debug(['директория загрузки', $uploadPath . '/' . ARRDOWN]);

        // Данные из БД
        $books = R::findAll('elems', 'md5 LIKE ? ORDER BY grup DESC LIMIT 2', ['%' . $page . '%']);
//        debug(['$books', $books]);

        $data['elems'] = $books;

        // Остальные переменные
        $data += [
            'hi'          => 'hello world',
            'parent_file' => __FILE__,
            'parent_func' => __FUNCTION__,
            'sdb'         => $sdb,
            'id_schem'    => $id_schem
        ];

        // Шаблоны
        $this->loadCommonViews($data);
        $this->prepareView('checklist', 'default_chablon', $data);
    }

    /**
     * Отображение стандартных частей HTML
     */
    private function loadCommonViews(array $data): void {
        $this->loadView('htmlstart', $data);
        $this->loadView('htmldiv', $data);
    }

    /**
     * Просмотр отладочных данных
     */
    public function viewAction(): void {
        $this->view = 'debug';
        $data = $this->prepare();
        $this->set(['posts' => $data]);
    }
}
