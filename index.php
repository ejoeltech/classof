<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);
define('APP_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Autoloader (PSR-4, App\ => app/)
|--------------------------------------------------------------------------
*/
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }

    $relative = substr($class, 4);
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/helpers.php';

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/
$config = require BASE_PATH . '/config/config.php';
App\Core\Config::init($config);

date_default_timezone_set((string) config('app.timezone', 'Africa/Lagos'));

// Make absolute URL generation work whether the app sits in a subfolder or at the domain root.
$scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
$scriptDir = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
define('BASE_URL_PATH', $scriptDir);

// A group website is served when the request host is <subdomain>.<SITE_DOMAIN>.
$siteSubdomain = site_subdomain_from_host();

if ($siteSubdomain !== null) {
    define('GROUP_SITE_SUBDOMAIN', $siteSubdomain);
}

/*
|--------------------------------------------------------------------------
| Error handling
|--------------------------------------------------------------------------
*/
error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e): void {
    App\Core\Logger::error($e->getMessage() . "\n" . $e->getTraceAsString());

    http_response_code(500);

    if (config('app.debug')) {
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $trace = htmlspecialchars($e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        echo <<<HTML
        <!doctype html><html><head><meta charset="utf-8"><title>Classof — Error</title>
        <style>body{font-family:ui-monospace,monospace;background:#101915;color:#e8f1ec;padding:40px;line-height:1.6}
        h1{color:#ff8a8a;font-size:20px}pre{background:#0a0f0d;padding:20px;border-radius:10px;overflow:auto;font-size:13px}
        .file{color:#8fd9b6}</style></head><body>
        <h1>Application error</h1><p class="file">{$trace}</p><pre>{$message}</pre>
        <p><a style="color:#8fd9b6" href="javascript:history.back()">Go back</a></p></body></html>
        HTML;
        return;
    }

    require BASE_PATH . '/app/Views/errors/500.php';
});

/*
|--------------------------------------------------------------------------
| Session & request context
|--------------------------------------------------------------------------
*/
App\Core\Session::start();

App\Core\Auth::bootRemembered();

/*
|--------------------------------------------------------------------------
| CSRF protection (webhooks authenticate via signatures instead)
|--------------------------------------------------------------------------
*/
$request = App\Core\Request::capture();

if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
    && !str_starts_with($request->path(), '/webhooks')) {
    if (!App\Core\Csrf::validate($request->input('_token'))) {
        http_response_code(419);
        if ($request->wantsJson()) {
            App\Core\Response::json(['message' => 'Your session expired. Please refresh and try again.'], 419);
        }
        App\Core\Session::flash('error', 'Your session expired. Please refresh the page and try again.');
        App\Core\Response::back();
    }
}

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/
$router = new App\Core\Router();
require BASE_PATH . '/routes/web.php';

$router->dispatch($request);
