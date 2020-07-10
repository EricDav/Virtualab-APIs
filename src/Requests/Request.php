<?php
namespace VirtualLab\Requests;

class Request {
    public $method;
    public $query;
    public $body;
    public $host;
    public $session;
    // public $uri = $_SERVER['REQUEST_URI'];
    public $serverName;
    
    public function __construct() {
        $this->method =  $_SERVER['REQUEST_METHOD'];
        $this->host = $_SERVER['HTTP_HOST'];
        $this->session = (object) $_SESSION;
        $this->serverName = $_SERVER['SERVER_NAME'];
        $path = explode('?', $_SERVER['REQUEST_URI'])[0];

        $this->route = $path;
    }

    public function setParam($allowedParams, $method) {
        $obj = (object)array();

        if ($method == 'POST') {
            $paramType = $_POST;
        } else if ($method == 'GET') {
            $paramType = $_GET;
        } else {
            // TODO method not allowed;
        }

        foreach($allowedParams as $param) {
            $obj->$param = isset($paramType[$param]) ? $paramType[$param] : null;
        }

        if ($method == 'POST') {
            $this->body = $obj;
        } else {
            $this->query = $obj;
        }
    }
}

?>