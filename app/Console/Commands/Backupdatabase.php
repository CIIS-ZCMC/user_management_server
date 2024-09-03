<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use Illuminate\Console\Command;

class Backupdatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $backupDirectory = storage_path('app/backups');

        // Ensure the backups directory exists
        if (!is_dir($backupDirectory)) {
            mkdir($backupDirectory, 0755, true);
            Helpers::infoLog("Backupdatabase", "handle", "Directory created for backup database: ".$backupDirectory);
        }
        
        $filename = 'umis_db_' . date('m-d-Y') . '.sql';
        $path = $backupDirectory . '/' . $filename;
        
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg(env('DB_USERNAME')),
            escapeshellarg(env('DB_PASSWORD')),
            escapeshellarg(env('DB_HOST')),
            escapeshellarg(env('DB_DATABASE')),
            escapeshellarg($path)
        );

        $result = null;
        $output = null;
        
        exec($command, $output, $result);

        if ($result === 0) {
            Helpers::infoLog("Backupdatabase", "handle", "Database backup created successfully: ".$filename);
        } else {
            Helpers::errorLog("Backupdatabase", "handle", "Failed to create database backup. Check your configurations and permissions.");
        }
    }
}
