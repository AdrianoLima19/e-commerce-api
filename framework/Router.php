<?php

namespace eNote;

use InvalidArgumentException;

class Router
{
    protected array $routes = [];
    protected array $currentRoute = [];

    public function get(string $path, string|callable $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    public function post(string $path, string|callable $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    public function put(string $path, string|callable $handler): self
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }

    public function delete(string $path, string|callable $handler): self
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }

    public function name(string $name): self
    {
        $this->setAttribute('name', $name);
        return $this;
    }

    protected function setAttribute(string $type, mixed $value): void
    {
        $path =  $this->currentRoute['path'];
        $method =  $this->currentRoute['method'];

        $this->currentRoute[$type] = $value;
        $this->routes[$path][$method] = $this->currentRoute;
    }

    protected function addRoute(string $method, string $path, string|callable $handler): void
    {
        $this->validateHandler($handler);

        $this->currentRoute = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'name' => null,
            'before' => [],
            'after' => [],
            'where' => [],
        ];

        $this->routes[$path][$method] = $this->currentRoute;
    }

    protected function validateHandler(string|callable $handler): void
    {
        if (is_callable($handler)) {
            return;
        }

        if (!is_string($handler)) {
            throw new InvalidArgumentException("The provided \$handler is not valid.");
        }

        $parts  = explode('@', $handler);
        $class = $parts[0];

        if (!class_exists($class)) {
            throw new InvalidArgumentException("The class {$class} does not exist.");
        }

        if (isset($parts[1])) {
            $method = $parts[1];

            if (!method_exists($class, $method)) {
                throw new InvalidArgumentException("The method {$method} is not valid in class {$class}.");
            }

            return;
        }

        if (method_exists($class, '__invoke')) {
            return;
        }

        throw new InvalidArgumentException("The class {$class} is not invocable and no method was specified.");
    }
}
