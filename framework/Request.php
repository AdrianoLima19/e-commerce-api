<?php

namespace eNote;

class Request
{
    protected array $server;
    protected ?array $body;
    protected array $attributes;

    protected string $method;
    protected string $scheme;
    protected string $host;
    protected ?int $port;
    protected string $path;
    protected string $baseUrl;

    public function __construct()
    {
        $this->server = $_SERVER;
        $this->method = $this->server['REQUEST_METHOD'];
        $this->getUriFromServer();
        $this->getBodyFromRequest();
        $this->setAttributes([]);
    }

    public function getServer(): array
    {
        return $this->server;
    }

    public function getBody(): ?array
    {
        return $this->body;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    protected function getUriFromServer(): void
    {
        $host = $this->server['HTTP_X_FORWARDED_HOST'] ?? $this->server['HTTP_HOST'] ?? 'localhost';
        $scheme = ($this->server['HTTPS'] ?? 'off') !== 'off' ? 'https' : 'http';
        $url = parse_url("{$scheme}://{$host}");

        $scriptName = $this->server['SCRIPT_NAME'];
        $requestUri = $this->server['REQUEST_URI'];

        if ($requestUri === $scriptName) {
            $baseUrl = '';
        } elseif (strpos($requestUri, $scriptName) === 0) {
            $baseUrl = $scriptName;
        } elseif (strpos($requestUri, dirname($scriptName)) === 0) {
            $baseUrl = rtrim(dirname($scriptName), '/');
        } else {
            $baseUrl = '';
        }

        $path = substr($requestUri, strlen($baseUrl));

        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        $this->scheme = $url['scheme'] ?? 'http';
        $this->host = $url['host'] ?? 'localhost';
        $this->port = $url['port'] ?? null;
        $this->path = $path;
        $this->baseUrl = $baseUrl;
    }

    public function getBodyFromRequest(): void
    {
        if ($this->method === 'GET') {
            $this->body = $_GET;
            return;
        }

        if (!in_array($this->method, ['POST', 'PUT', 'DELETE'])) {
            throw new \InvalidArgumentException("Invalid HTTP method: {$this->method}. Expected 'POST', 'PUT' or 'DELETE'.", 1);
        }

        $data = file_get_contents('php://input');

        if ($data === false) {
            throw new \RuntimeException('Unable to read the request body.');
        }

        $contentType = $this->server['CONTENT_TYPE'] ?? '';

        if (empty($contentType)) {
            return;
        }

        switch ($contentType) {
            case 'application/json':
                // If the content is already JSON, do nothing
                break;

            case 'text/xml':
            case 'application/xml':
                $xml = simplexml_load_string($data);
                $data = json_encode($xml);
                break;

            default:
                $data = '';
        }

        $this->body = json_decode($data, true);
    }
}
