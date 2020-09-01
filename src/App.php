<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once '../Virtualab.Class.php';
require_once '../vendor/autoload.php';

use VirtualLab\Requests\Request;
use VirtualLab\Enviroment\Enviroment;

    class App {
        public function __construct() {

        }

        public function jsonResponse($response, $statusCode) {
            header('Content-Type: application/json');
            header("HTTP/1.0 " . (string)$statusCode . " ");
            echo json_encode($response);
            exit;
        }

        public function init() {
            $enviroment = Enviroment::getEnv();
            $request = new Request(); // Create a request object
            $data = null;
            
            if (in_array($request->route, array_keys(VirtualLab::ROUTES[$request->method]))) {
                $controllerArr = explode('@', VirtualLab::ROUTES[$request->method][$request->route]);
            
                // Set the allowed params expecting from the client
                $result = $request->setParam(VirtualLab::ALLOWED_PARAM[$request->method][$request->route], $request->method);
                
                if (!$result->success) {
                    $this->jsonResponse($result, 400);
                }
            
                $controllerClass = "VirtualLab\\Controllers\\" . $controllerArr[0];
                $method = $controllerArr[1];
                $controllerObject = new $controllerClass($request);
            
                $controllerObject->$method($request);
            
            } else {
                $this->jsonResponse(array('success' => false, 'message' => 'route not defined'), 200);
                // TODO 404 
            }
        }
    }

$broostrap = new App();
$broostrap->init();

?>

