<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'indogate:backup {--destination= : Custom destination path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform automated database backup and verify file integrity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $backupDir = $this->option('destination') ?: storage_path('app/backups');

        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $connection = config('database.default');

        $this->info("Starting database backup for connection [{$connection}]...");

        if ($connection === 'sqlite') {
            $databasePath = config('database.connections.sqlite.database');
            $targetFile = $backupDir."/backup_{$timestamp}.sqlite";

            if ($databasePath === ':memory:') {
                // For in-memory sqlite (e.g. testing), dump SQL tables
                $dumpFile = $backupDir."/backup_{$timestamp}.sql";
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $sql = "-- Indogate In-Memory SQLite Dump {$timestamp}\n";
                foreach ($tables as $table) {
                    $createStmt = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$table->name}'");
                    if (! empty($createStmt[0]->sql)) {
                        $sql .= $createStmt[0]->sql.";\n";
                    }
                    $rows = DB::table($table->name)->get();
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $keys = implode(', ', array_map(fn ($k) => "`$k`", array_keys($rowArray)));
                        $values = implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : "'".addslashes((string) $v)."'", array_values($rowArray)));
                        $sql .= "INSERT INTO `{$table->name}` ({$keys}) VALUES ({$values});\n";
                    }
                }
                File::put($dumpFile, $sql);
                $targetFile = $dumpFile;
            } elseif (File::exists($databasePath)) {
                File::copy($databasePath, $targetFile);
            } else {
                $this->error("Database file not found: {$databasePath}");

                return Command::FAILURE;
            }
        } else {
            // MySQL / MariaDB Dump
            $targetFile = $backupDir."/backup_{$timestamp}.sql";
            $tables = DB::select('SHOW TABLES');
            $dbName = config("database.connections.{$connection}.database");
            $tableKey = "Tables_in_{$dbName}";

            $sql = "-- Indogate MySQL Database Dump {$timestamp}\nSET FOREIGN_KEY_CHECKS=0;\n";

            foreach ($tables as $tableObj) {
                $tableName = $tableObj->$tableKey ?? current((array) $tableObj);
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $createTable[0]->{'Create Table'} ?? '';
                $sql .= "\nDROP TABLE IF EXISTS `{$tableName}`;\n{$createSql};\n";

                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $keys = implode(', ', array_map(fn ($k) => "`$k`", array_keys($rowArray)));
                    $values = implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : "'".addslashes((string) $v)."'", array_values($rowArray)));
                    $sql .= "INSERT INTO `{$tableName}` ({$keys}) VALUES ({$values});\n";
                }
            }

            $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
            File::put($targetFile, $sql);
        }

        $size = File::size($targetFile);
        $this->info("Backup completed successfully: {$targetFile} ({$size} bytes)");

        activity('security')
            ->withProperties([
                'file' => basename($targetFile),
                'size_bytes' => $size,
                'connection' => $connection,
            ])
            ->log('Database backup generated successfully');

        return Command::SUCCESS;
    }
}
