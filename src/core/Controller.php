<?php
namespace Core;

abstract class Controller
{
    /**
     * Tampilkan view
     */
    protected function render(string $view, array $data = [], string $layout = 'app'): void
    {
        // Extract data ke local variables
        extract($data);

        // Path ke views
        $viewsPath = dirname(__DIR__) . '/views/';
        $content_view = $viewsPath . str_replace('.', '/', $view) . '.php';

        if (!file_exists($content_view)) {
            http_response_code(500);
            exit("View '{$view}' tidak ditemukan.");
        }

        // Variabel global yang diwariskan ke view/layout
        $auth_user = auth_user();
        
        // Panggil layout
        require $viewsPath . 'layouts/' . $layout . '.php';
    }

    /**
     * Return JSON response
     */
    protected function json(mixed $data, int $statusCode = 200): never
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        
        // Regenerate CSRF for validation AJAX
        $data = array_merge((array)$data, [
            'csrf' => [
                'name' => '_csrf_token',
                'hash' => csrf_token()
            ]
        ]);

        echo json_encode($data);
        exit;
    }
}
