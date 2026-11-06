<?php

/**
 * Audits a Laravel app's migrations on a fresh database. Run from inside the app's container:
 *
 *   php /path/to/audit-migrations.php [/path/to/app] > report.json
 *
 * It wipes the database, runs every registered migration one at a time in filename order (the order `migrate` uses),
 * keeps going after failures, and reports:
 *   - migrations that throw, with the first line of the error
 *   - foreign keys that point at a table created by a later migration, or by none at all
 *
 * Foreign key checks are switched off for the run, so ordering mistakes show up in the report instead of as
 * a cascade of failures.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$appPath = $argv[1] ?? getcwd();
chdir($appPath);
require $appPath.'/vendor/autoload.php';
$app = require $appPath.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

config(['cache.default' => 'array']);
Schema::dropAllTables();
// after the drop: dropAllTables() switches them back on. Set AUDIT_FK_CHECKS=1 to keep them enforced (finds type mismatches)
if (! getenv('AUDIT_FK_CHECKS')) {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
}

$migrator = app('migrator');
// `migrate` also reads the app's own database/migrations, which isn't in paths()
$files = $migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')]));
$resolve = new ReflectionMethod($migrator, 'resolvePath');

$tables = fn () => array_column(Schema::getTables(DB::getDatabaseName()), 'name');
$current = null;
$statements = [];
DB::listen(function ($q) use (&$current, &$statements) {
    if ($current !== null && preg_match('/foreign key .*references `([^`]+)`/i', $q->sql, $m)) {
        preg_match('/^alter table `([^`]+)`/i', $q->sql, $t);
        $statements[] = ['migration' => $current, 'table' => $t[1] ?? '?', 'references' => $m[1]];
    }
});

$createdBy = [];   // table => [migration, position]
$failures = [];
$position = 0;
$order = [];

foreach ($files as $name => $file) {
    $position++;
    $order[$name] = $position;
    $current = $name;
    $before = $tables();
    try {
        $migrator->requireFiles([$file]);
        $resolve->invoke($migrator, $file)->up();
    } catch (Throwable $e) {
        $failures[] = [
            'migration' => $name,
            'path' => str_replace($appPath.'/', '', $file),
            'error' => mb_substr(trim(strtok($e->getMessage(), "\n")), 0, 400),
        ];
    }
    foreach (array_diff($tables(), $before) as $table) {
        $createdBy[$table] = [$name, $position];
    }
}

$fkProblems = [];
foreach ($statements as $s) {
    $created = $createdBy[$s['references']] ?? null;
    $myPos = $order[$s['migration']];
    if ($created === null) {
        $fkProblems[] = $s + ['problem' => 'referenced table is never created'];
    } elseif ($created[1] > $myPos) {
        $fkProblems[] = $s + ['problem' => 'referenced table is created by a later migration', 'created_by' => $created[0]];
    }
}

DB::statement('SET FOREIGN_KEY_CHECKS=1');

echo json_encode([
    'migrations_run' => count($files),
    'failures' => $failures,
    'foreign_key_problems' => $fkProblems,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
