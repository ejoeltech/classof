<?php

declare(strict_types=1);

/**
 * Classof additive migration runner (CLI only).
 *
 *   php database/migrate.php
 *
 * Applies every database/migrations/*.sql file that has not been recorded in
 * the schema_migrations table. Use this on an existing installation so live
 * data is never dropped. Fresh installs continue to use database/install.php.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL_PATH', '');
define('APP_START', microtime(true));

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }

    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/helpers.php';

$config = require BASE_PATH . '/config/config.php';
App\Core\Config::init($config);
date_default_timezone_set('Africa/Lagos');

$db = config('db');
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['database']);

try {
    $pdo = new PDO($dsn, (string) $db['username'], (string) $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Could not connect to `{$db['database']}`. Run `php database/install.php` first for a fresh install.\n\n" . $e->getMessage() . "\n");
    exit(1);
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `version` VARCHAR(190) NOT NULL,
        `applied_at` DATETIME NOT NULL,
        PRIMARY KEY (`version`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci'
);

$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
$migrationsDir = __DIR__ . '/migrations';

if (!is_dir($migrationsDir)) {
    fwrite(STDOUT, "No migrations directory found. Nothing to do.\n");
    exit(0);
}

$files = glob($migrationsDir . '/*.sql') ?: [];
sort($files);
$count = 0;

foreach ($files as $file) {
    $version = basename($file, '.sql');

    if (in_array($version, $applied, true)) {
        continue;
    }

    $sql = (string) file_get_contents($file);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];

    try {
        foreach ($statements as $statement) {
            $statement = trim($statement);

            if ($statement === '') {
                continue;
            }

            $pdo->exec($statement);
        }

        $insert = $pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, ?)');
        $insert->execute([$version, date('Y-m-d H:i:s')]);

        fwrite(STDOUT, "Applied {$version}\n");
        $count++;
    } catch (PDOException $e) {
        fwrite(STDERR, "Migration {$version} failed.\n\n" . $e->getMessage() . "\n");
        exit(1);
    }
}

fwrite(STDOUT, $count === 0 ? "Database is already up to date.\n" : "Applied {$count} migration(s).\n");
