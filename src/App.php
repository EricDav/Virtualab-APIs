<?php

require_once '../Virtualab.Class.php';
require_once '../vendor/autoload.php';

use VirtualLab\Requests\Request;
use VirtualLab\Enviroment\Enviroment;

    class App {
        public function __construct() {

        }

        public function init() {
            $enviroment = Enviroment::getEnv();
            $request = new Request(); // Create a request object
            $data = null;
            
            if (in_array($request->route, array_keys(VirtualLab::ROUTES[$request->method]))) {
                $controllerArr = explode('@', VirtualLab::ROUTES[$request->method][$request->route]);
            
                // Set the allowed params expecting from the client
                $request->setParam(VirtualLab::ALLOWED_PARAM[$request->method][$request->route], $request->method);
                // var_dump($request); exit;
            
                $controllerClass = "VirtualLab\\Controllers\\" . $controllerArr[0];
                $method = $controllerArr[1];
                $controllerObject = new $controllerClass($request);
            
                $controllerObject->$method($request);
            
            } else {
                // TODO 404 
            }
        }
    }

$broostrap = new App();
$broostrap->init();

?>

