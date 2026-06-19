<?php
namespace Core;

class Router
{
    private array $routes = [];

    /**
     * Daftarkan route GET
     */
    public function get(string $path, array|callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Daftarkan route POST
     */
    public function post(string $path, array|callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Daftarkan semua route middleware/helper
     */
    private function addRoute(string $method, string $path, array|callable $handler): void
    {
        // Ubah route parameter seperti {id} jadi regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $path);
        // Tambahkan batas awal dan akhir
        $pattern = '#^' . $pattern . '/?$#';
        
        $this->routes[$method][$pattern] = $handler;
    }

    /**
     * Eksekusi router berdasarkan request URL
     */
    public function dispatch(string $method, string $uri): void
    {
        // Hapus query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Decode URL
        $uri = rawurldecode($uri);

        // Otomatis hapus path direktori root jika project dijalankan dalam sub-direktori
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptPath !== '/' && $scriptPath !== '\\' && str_starts_with($uri, $scriptPath)) {
            $uri = substr($uri, strlen($scriptPath));
        }

        if ($uri === '') {
            $uri = '/';
        }

        // Cari method
        if (!isset($this->routes[$method])) {
            $this->sendNotFound();
            return;
        }

        foreach ($this->routes[$method] as $pattern => $handler) {
            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                    return;
                }

                if (is_array($handler)) {
                    [$class, $methodName] = $handler;
                    
                    if (class_exists($class)) {
                        $controller = new $class();
                        
                        if (method_exists($controller, $methodName)) {
                            call_user_func_array([$controller, $methodName], $params);
                            return;
                        }
                    }
                }
            }
        }

        $this->sendNotFound();
    }

    private function sendNotFound(): void
    {
        http_response_code(404);
        require dirname(__DIR__) . '/views/errors/404.php';
        exit;
    }
}
