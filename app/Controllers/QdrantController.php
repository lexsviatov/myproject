<?php
namespace app\controllers;

use \R;
use app\services\RequestService;
use app\services\DatabaseService;
use app\services\FileService;
use app\services\AccessService;
use app\services\QdrantService;

class QdrantController extends AppController {
    public $layout = 'chablon';
    public $view = 'test';

    private QdrantService $qdrant;

    public function __construct() {
        parent::__construct();
        $this->qdrant = new QdrantService("http://localhost:6333", ""); // если api-key есть — передай
    }

    public function indexAction(): void {
        // создаем коллекцию
        $result = $this->qdrant->createCollection("mvc_code", 1024);

        // добавляем фиктивные данные
        $this->qdrant->addPoints("mvc_code", [
            [
                "id" => 1,
                "vector" => array_fill(0, 1024, 0.5),
                "payload" => [
                    "file" => "QdrantController.php",
                    "code" => file_get_contents(__FILE__)
                ]
            ]
        ]);

        // ищем похожее
        $search = $this->qdrant->search("mvc_code", array_fill(0, 1024, 0.5), 3);

        $data = [
            'collection' => $result,
            'search'     => $search
        ];

        $this->prepareView('debug', 'default_chablon', $data);
    }
}
