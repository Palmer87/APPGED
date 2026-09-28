<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateDocumentStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:migrate-storage 
                            {--from=local : The source storage disk}
                            {--to=s3 : The destination storage disk}
                            {--delete-source : Delete files from source disk after copying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate stored documents and versions from one filesystem disk to another (e.g., local to s3/R2)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fromDisk = (string) $this->option('from');
        $toDisk = (string) $this->option('to');
        $deleteSource = (bool) $this->option('delete-source');

        $this->info("Starting document storage migration from '{$fromDisk}' to '{$toDisk}'...");

        $versions = DocumentVersion::where('storage_disk', $fromDisk)->get();

        if ($versions->isEmpty()) {
            $this->info("No document versions found with storage_disk = '{$fromDisk}'.");
        } else {
            $this->info("Found {$versions->count()} version(s) to migrate.");
            $bar = $this->output->createProgressBar($versions->count());
            $bar->start();

            $migratedCount = 0;
            $failedCount = 0;

            foreach ($versions as $version) {
                $path = $version->storage_path;

                try {
                    if (Storage::disk($fromDisk)->exists($path)) {
                        $content = Storage::disk($fromDisk)->get($path);
                        Storage::disk($toDisk)->put($path, $content);

                        if ($deleteSource) {
                            Storage::disk($fromDisk)->delete($path);
                        }

                        $version->update(['storage_disk' => $toDisk]);
                        $migratedCount++;
                    } else {
                        $this->warn("\nFile not found on source disk: {$path} for version #{$version->id}");
                        $failedCount++;
                    }
                } catch (\Throwable $e) {
                    $this->error("\nFailed to migrate version #{$version->id}: ".$e->getMessage());
                    $failedCount++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Versions migration: {$migratedCount} migrated, {$failedCount} failed.");
        }

        // Also update Document records
        $documents = Document::where('storage_disk', $fromDisk)->get();
        if ($documents->isNotEmpty()) {
            foreach ($documents as $doc) {
                $doc->update(['storage_disk' => $toDisk]);
            }
            $this->info("Updated storage_disk on {$documents->count()} document record(s).");
        }

        $this->info('Migration completed successfully!');

        return Command::SUCCESS;
    }
}
