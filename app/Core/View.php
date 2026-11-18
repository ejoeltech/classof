<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $data['errors'] = $data['errors'] ?? Session::pull('errors', []);
        $data['old'] = $data['old'] ?? Session::pull('old', []);
        $data['flashStatus'] = Session::getFlash('status');
        $data['flashError'] = Session::getFlash('error');

        $GLOBALS['__classof_old_input'] = $data['old'];

        $viewFile = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            echo $content;

            return;
        }

        $layoutFile = BASE_PATH . '/app/Views/' . str_replace('.', '/', $layout) . '.php';

        if (!is_file($layoutFile)) {
            throw new \RuntimeException('Layout not found: ' . $layout);
        }

        require $layoutFile;
    }

    public static function partial(string $view, array $data = []): void
    {
        $file = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($file)) {
            return;
        }

        extract($data, EXTR_SKIP);
        require $file;
    }
}
