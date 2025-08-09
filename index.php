<?php
// phpinfo();
// die;


use vendor\fw\core\Router;

define("DEBUG", 1); //при DEBUG = 1 будет происходить вывод дебагера при  $this->loadView('_debug', compact('post'));
define("BUG", 0); //при BUG = 1 будет происходить вывод дебагера в при  $this->loadView('_debug', compact('post'));
define("CACHEUPD", 1);  //при CACHEUPD = 0 файлы кеша не обновляются
define("CACHEDELL", 1); //при CACHEDELL = 0 файлы кеша не удаляются
define("GETZ", 0);      //при GETZ = 1 Будет перезагрузка страницы при каждом клике (после завершения переноса на аякс удалить)
$query = rtrim($_SERVER['QUERY_STRING'], '/');
define('WWW', __DIR__);
define('CORE', WWW . '/vendor/fw/core');
define('LIBS', '/vendor/fw/libs');
define('ROOT', WWW);
define('APP', WWW . '/app');
define('CACHE', 'tmp/cache');
define('CACHESELECT', 'tmp/cache/select');

define('PUB', 'public');
define('JSON', 'public/file/json');
define('AJX', 'public' . DIRECTORY_SEPARATOR . 'ajaxupload');
define('LAYOUT', 'blog'); //шаблон для корневой директории
define('PROJECT_ROOT', realpath('.'));

define('ARRDOWN', '{*.png,*.PNG,*.gif*,*.jpeg,*.JPEG,*.jpg,*.JPG,*.mp4,*.svg,*.SVG}');
define('ARRDOWN_PDF', '{*.pdf,*.PDF}');
define('SLESH', '_');
define('ADMIN', 'http://crm.com/admin');
define('YDATE', 2023); 
define('BASE_URL', '/'); // если сайт в корне

//            echo '<pre>' . print_r($_REQUEST, true) . '</pre>';

require 'vendor/fw/libs/function.php';
//require_once 'routes.php';

//            debug([[__FILE__, __CLASS__, __FUNCTION__, __LINE__], '$_REQUEST', $_REQUEST]);  

//require 'vendor/fw/libs/func.php'; // для дипломной
//require 'vendor/fw/libs/funcven.php'; // для дипломной
        $dbs = require 'config/config_db.php';
        define('PREFIX', 'bbb');
        define('SUBPREFIX', 'kat');
        // define('PREFIX', $dbs['dbname']);
        define('NBD', $dbs['dbname']);
        define('PBD', $dbs['pass']);
        
        
        
        
//        define('PREFIX', $dbs['dbname']);
//define('PREFIX', 'bbb');
//define('SUBPREFIX', 'kat');
require 'vendor/autoload.php';

spl_autoload_register(function($class) {

    $file = PROJECT_ROOT . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) {
        require_once $file;
    } else {
        
    }
});

new vendor\fw\core\App;


// Добавляем маршруты для команд
Router::add('^command$', ['controller' => 'Command', 'action' => 'index']);
Router::add('^command/execute$', ['controller' => 'Command', 'action' => 'execute']);



//new fw\core\App;
//исключения (перенаправление)
//Router::add('^pages/?(?P<action>[a-z-]+)?$', ['controller' => 'posts', 'action' => 'index']);
Router::add('^page/(?P<action>[a-z-]+)/(?P<alias>[a-z-]+)$', ['controller' => 'Page']);
Router::add('^page/(?P<alias>[a-z-]+)$', ['controller' => 'Page', 'action' => 'view']);


Router::add('^admin$', ['controller' => 'Main', 'action' => 'index', 'prefix' => 'admin']);
Router::add('^admin/(?P<controller>[a-z-]+)/?(?P<action>[a-z-]+)?$', ['prefix' => 'admin']);
Router::add('^$', ['controller' => 'Main', 'action' => 'index']);
Router::add('^(?P<controller>[a-z-]+)/?(?P<action>[a-z-]+)?$');
Router::dispatch($query);
