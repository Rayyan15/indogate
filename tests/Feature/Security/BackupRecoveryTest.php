<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_backup_command_generates_valid_backup_file(): void
    {
        $this->seed();

        $testBackupDir = storage_path('framework/testing/backups');
        if (File::exists($testBackupDir)) {
            File::deleteDirectory($testBackupDir);
        }

        $this->artisan('indogate:backup', ['--destination' => $testBackupDir])
            ->assertSuccessful();

        $files = File::files($testBackupDir);
        $this->assertNotEmpty($files, 'Backup directory must contain generated backup file');

        $backupFile = $files[0];
        $this->assertGreaterThan(0, File::size($backupFile->getRealPath()), 'Backup file must not be empty');

        // Verify activity log recorded the backup
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'security',
            'description' => 'Database backup generated successfully',
        ]);

        // Clean up test files
        File::deleteDirectory($testBackupDir);
    }
}
