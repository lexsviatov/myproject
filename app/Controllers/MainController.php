<?php
namespace app\controllers;
use app\models\Main;
use vendor\fw\core\App;
use vendor\fw\core\base\View;
use vendor\fw\libs\Pagination;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use \R;

class MainController extends AppController{
    
        public function indexAction(){
        $model = new Main;


    }
//    public function testAction(){   
//        if($this->isAjax()){
//            $model = new Main();
//            $post = \R::findOne('postes', "id = {$_POST['id']}");
//            $this->loadView('_test', compact('post'));
//            die;
//        }
//
//    }
    
}
