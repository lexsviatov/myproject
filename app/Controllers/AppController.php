<?php
namespace app\controllers;
use vendor\fw\core\App;
use vendor\fw\widgets\language\Language;

class AppController extends \vendor\fw\core\base\Controller {
    public function __construct($route) {
        parent::__construct($route);
        new \app\models\Main;
        // подключение второго языка в проекте не используется
        App::$app->setProperty('langs', Language::getLanguages());
        App::$app->setProperty('lang', Language::getLanguage(App::$app->getProperty('langs')));
        
    }


}

?>

