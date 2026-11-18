<?php

declare(strict_types=1);

/**
 * Classof installer (CLI only).
 *
 *   php database/install.php          Create the database and tables.
 *   php database/install.php --seed   Also load the demo data.
 *
 * Use this on a fresh machine instead of piping schema.sql through the mysql client.
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
$dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $db['host'], $db['port']);

try {
    $pdo = new PDO($dsn, (string) $db['username'], (string) $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Could not connect to MySQL.\nCheck DB_HOST, DB_PORT, DB_USERNAME and DB_PASSWORD in .env.\n\n" . $e->getMessage() . "\n");
    exit(1);
}

$schemaPath = __DIR__ . '/schema.sql';

if (!is_file($schemaPath)) {
    fwrite(STDERR, "database/schema.sql is missing.\n");
    exit(1);
}

$sql = (string) file_get_contents($schemaPath);

// Strip comment-only lines first so a statement that follows the file header
// is not mistaken for a comment block. Then split on statement terminators.
$sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
$statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
$executed = 0;

try {
    foreach ($statements as $statement) {
        $statement = trim($statement);

        if ($statement === '') {
            continue;
        }

        $pdo->exec($statement);
        $executed++;
    }
} catch (PDOException $e) {
    fwrite(STDERR, "Installation failed after {$executed} statements.\n\n" . $e->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "Database `{$db['database']}` created with {$executed} statements.\n");

// A fresh schema already contains every migration. Record them so a later
// `php database/migrate.php` does not try to re-apply additive columns.
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `version` VARCHAR(190) NOT NULL,
        `applied_at` DATETIME NOT NULL,
        PRIMARY KEY (`version`)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci'
);

$migrationFiles = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($migrationFiles);

foreach ($migrationFiles as $migrationFile) {
    $statement = $pdo->prepare('INSERT IGNORE INTO schema_migrations (version, applied_at) VALUES (?, ?)');
    $statement->execute([basename($migrationFile, '.sql'), date('Y-m-d H:i:s')]);
}

if (in_array('--seed', $argv, true)) {
    fwrite(STDOUT, "Loading demo data…\n");
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/seed.php');
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, "Seeder exited with code {$exitCode}.\n");
        exit(1);
    }
} else {
    fwrite(STDOUT, "Run `php database/seed.php` to load the demo accounts and data.\n");
}

fwrite(STDOUT, "\nOpen: http://localhost/classof/\n");
