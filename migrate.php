<?php

declare(strict_types=1);

/**
 * Owebku database migration script.
 *
 * Usage:
 *   php migrate.php
 *
 * This script is idempotent: it creates required tables if they do not exist
 * and adds missing columns/indexes safely when possible.
 */

require __DIR__ . '/src/config/environment.php';
load_environment(__DIR__ . '/.env');
$config = require __DIR__ . '/src/config/database.php';

try {
    $pdo = connectToDatabase($config);

    migrate($pdo);

    echo "Migration completed successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

function connectToDatabase(array $config): PDO
{
    $charset = $config['charset'] ?? 'utf8mb4';
    $database = $config['dbname'];

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $serverDsn = sprintf(
        'mysql:host=%s;port=%s;charset=%s',
        $config['host'],
        $config['port'],
        $charset
    );

    try {
        $pdo = new PDO($serverDsn, $config['username'], $config['password'], $options);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
        $pdo->exec("USE `{$database}`");
        return $pdo;
    } catch (PDOException $e) {
        $databaseDsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $database,
            $charset
        );

        return new PDO($databaseDsn, $config['username'], $config['password'], $options);
    }
}

function migrate(PDO $pdo): void
{
    createUsersTable($pdo);
    createProjectsTable($pdo);
    createProjectFilesTable($pdo);
    createActivityLogsTable($pdo);
    createPublishJobsTable($pdo);

    ensureUsersColumns($pdo);
    ensureProjectsColumns($pdo);
    ensureProjectFilesColumns($pdo);
    ensureActivityLogsColumns($pdo);
    ensurePublishJobsColumns($pdo);

    ensureIndex($pdo, 'users', 'idx_users_username', 'username');
    ensureIndex($pdo, 'users', 'idx_users_email', 'email');
    ensureIndex($pdo, 'projects', 'idx_projects_id_user', 'id_user');
    ensureIndex($pdo, 'projects', 'idx_projects_slug', 'slug');
    ensureIndex($pdo, 'project_files', 'idx_project_files_project_path', 'id_project, relative_path(191)');
    ensureIndex($pdo, 'activity_logs', 'idx_activity_logs_user_created', 'id_user, created_at');
    ensureIndex($pdo, 'publish_jobs', 'idx_publish_jobs_project_user', 'id_project, id_user');
}

function createUsersTable(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS users (
    id_user INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
}

function createProjectsTable(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS projects (
    id_project INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT UNSIGNED NOT NULL,
    project_name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    workspace_path VARCHAR(500) NOT NULL,
    published_path VARCHAR(500) NULL,
    public_url VARCHAR(500) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    last_published_at DATETIME NULL,
    created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
}

function createProjectFilesTable(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS project_files (
    id_file INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_project INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    relative_path VARCHAR(1000) NOT NULL,
    file_extension VARCHAR(50) NULL,
    is_folder TINYINT(1) NOT NULL DEFAULT 0,
    is_editable TINYINT(1) NOT NULL DEFAULT 0,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
}

function createActivityLogsTable(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS activity_logs (
    id_log INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT UNSIGNED NOT NULL,
    id_project INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
}

function createPublishJobsTable(PDO $pdo): void
{
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS publish_jobs (
    id_job INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_project INT UNSIGNED NOT NULL,
    id_user INT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL,
    message TEXT NULL,
    started_at DATETIME NULL,
    finished_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
}

function ensureUsersColumns(PDO $pdo): void
{
    addColumnIfMissing($pdo, 'users', 'full_name', "VARCHAR(150) NULL AFTER password");
    addColumnIfMissing($pdo, 'users', 'role', "VARCHAR(30) NOT NULL DEFAULT 'user' AFTER full_name");
    addColumnIfMissing($pdo, 'users', 'is_active', "TINYINT(1) NOT NULL DEFAULT 1 AFTER role");
    addColumnIfMissing($pdo, 'users', 'created_at', "DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER is_active");
    addColumnIfMissing($pdo, 'users', 'updated_at', "DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
}

function ensureProjectsColumns(PDO $pdo): void
{
    addColumnIfMissing($pdo, 'projects', 'published_path', "VARCHAR(500) NULL AFTER workspace_path");
    addColumnIfMissing($pdo, 'projects', 'public_url', "VARCHAR(500) NULL AFTER published_path");
    addColumnIfMissing($pdo, 'projects', 'status', "VARCHAR(30) NOT NULL DEFAULT 'draft' AFTER public_url");
    addColumnIfMissing($pdo, 'projects', 'last_published_at', "DATETIME NULL AFTER status");
    addColumnIfMissing($pdo, 'projects', 'created_at', "DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER last_published_at");
    addColumnIfMissing($pdo, 'projects', 'updated_at', "DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
}

function ensureProjectFilesColumns(PDO $pdo): void
{
    addColumnIfMissing($pdo, 'project_files', 'file_extension', "VARCHAR(50) NULL AFTER relative_path");
    addColumnIfMissing($pdo, 'project_files', 'is_folder', "TINYINT(1) NOT NULL DEFAULT 0 AFTER file_extension");
    addColumnIfMissing($pdo, 'project_files', 'is_editable', "TINYINT(1) NOT NULL DEFAULT 0 AFTER is_folder");
    addColumnIfMissing($pdo, 'project_files', 'file_size', "BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER is_editable");
    addColumnIfMissing($pdo, 'project_files', 'created_at', "DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER file_size");
    addColumnIfMissing($pdo, 'project_files', 'updated_at', "DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
}

function ensureActivityLogsColumns(PDO $pdo): void
{
    addColumnIfMissing($pdo, 'activity_logs', 'description', "TEXT NULL AFTER action");
    addColumnIfMissing($pdo, 'activity_logs', 'created_at', "DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER description");
}

function ensurePublishJobsColumns(PDO $pdo): void
{
    addColumnIfMissing($pdo, 'publish_jobs', 'message', "TEXT NULL AFTER status");
    addColumnIfMissing($pdo, 'publish_jobs', 'started_at', "DATETIME NULL AFTER message");
    addColumnIfMissing($pdo, 'publish_jobs', 'finished_at', "DATETIME NULL AFTER started_at");
}

function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare(<<<SQL
SELECT COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = ?
  AND COLUMN_NAME = ?
SQL);
    $stmt->execute([$table, $column]);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "Added column {$table}.{$column}\n";
    }
}

function ensureIndex(PDO $pdo, string $table, string $index, string $columns): void
{
    $stmt = $pdo->prepare(<<<SQL
SELECT COUNT(*)
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = ?
  AND INDEX_NAME = ?
SQL);
    $stmt->execute([$table, $index]);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("CREATE INDEX `{$index}` ON `{$table}` ({$columns})");
        echo "Added index {$table}.{$index}\n";
    }
}
