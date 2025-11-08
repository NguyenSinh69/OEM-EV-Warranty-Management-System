<?php

namespace App\Core;

class App
{
    private static $instance = null;
    private array $services = [];

    public function __construct()
    {
        if (self::$instance === null) {
            self::$instance = $this;
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function bind(string $key, callable $resolver): void
    {
        $this->services[$key] = $resolver;
    }

    public function resolve(string $key)
    {
        if (!array_key_exists($key, $this->services)) {
            throw new \Exception("Service {$key} not found");
        }

        if (is_callable($this->services[$key])) {
            return $this->services[$key]();
        }

        return $this->services[$key];
    }
}