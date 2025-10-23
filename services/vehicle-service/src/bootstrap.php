<?php

// Simple autoloader and bootstrap for vehicle service
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = __DIR__ . '/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Simple Request class mock for compatibility
class Request {
    private $data;
    
    public function __construct() {
        $this->data = array_merge($_GET, $_POST);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            $input = file_get_contents('php://input');
            if ($input) {
                $json = json_decode($input, true);
                if ($json) {
                    $this->data = array_merge($this->data, $json);
                }
            }
        }
    }
    
    public function all() {
        return $this->data;
    }
    
    public function get($key, $default = null) {
        return $this->data[$key] ?? $default;
    }
    
    public function has($key) {
        return isset($this->data[$key]);
    }
}

// Simple JsonResponse class
class JsonResponse {
    private $data;
    private $status;
    
    public function __construct($data, $status = 200) {
        $this->data = $data;
        $this->status = $status;
    }
    
    public function getContent() {
        http_response_code($this->status);
        return json_encode($this->data);
    }
}

// Simple Response class mock
function response() {
    return new class {
        public function json($data, $status = 200) {
            return new JsonResponse($data, $status);
        }
    };
}