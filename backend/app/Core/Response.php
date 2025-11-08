<?php

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function json(array $data): void
    {
        $this->header('Content-Type', 'application/json');
        $this->send(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function text(string $text): void
    {
        $this->header('Content-Type', 'text/plain');
        $this->send($text);
    }

    public function html(string $html): void
    {
        $this->header('Content-Type', 'text/html');
        $this->send($html);
    }

    public function redirect(string $url): void
    {
        $this->status(302);
        $this->header('Location', $url);
        $this->send('');
    }

    private function send(string $content): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Set headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Send content
        echo $content;
        exit;
    }

    public function success(array $data = [], string $message = 'Success'): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        $this->status($statusCode)->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    public function paginated(array $data, int $total, int $page, int $perPage): void
    {
        $totalPages = ceil($total / $perPage);
        
        $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_prev_page' => $page > 1,
            ],
        ]);
    }
}