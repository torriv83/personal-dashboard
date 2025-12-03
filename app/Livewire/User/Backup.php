<?php

namespace App\Livewire\User;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Backup extends Component
{
    public array $backups = [];

    public bool $isCreatingBackup = false;

    public bool $isLoadingBackups = true;

    public function loadBackups(): void
    {
        $this->isLoadingBackups = true;

        try {
            $disk = Storage::disk('sftp');

            if (! $disk->exists('')) {
                $this->backups = [];

                return;
            }

            $files = collect($disk->allFiles())
                ->filter(fn ($file) => str_ends_with($file, '.zip'))
                ->map(function ($file) use ($disk) {
                    return [
                        'name' => basename($file),
                        'path' => $file,
                        'size' => $disk->size($file),
                        'date' => $disk->lastModified($file),
                    ];
                })
                ->sortByDesc('date')
                ->values()
                ->toArray();

            $this->backups = $files;
        } finally {
            $this->isLoadingBackups = false;
        }
    }

    public function createBackup(): void
    {
        $this->isCreatingBackup = true;

        try {
            Artisan::call('backup:run', ['--only-db' => false]);

            $this->dispatch('notify', message: 'Backup opprettet!', type: 'success');

            // Reload backups after a short delay to allow file to be created
            sleep(2);
            $this->loadBackups();
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Feil ved opprettelse av backup: '.$e->getMessage(), type: 'error');
        } finally {
            $this->isCreatingBackup = false;
        }
    }

    public function downloadBackup(string $path): mixed
    {
        try {
            $disk = Storage::disk('sftp');

            if (! $disk->exists($path)) {
                $this->dispatch('notify', message: 'Backup-fil ikke funnet', type: 'error');

                return null;
            }

            return response()->streamDownload(function () use ($disk, $path) {
                echo $disk->get($path);
            }, basename($path));
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Feil ved nedlasting: '.$e->getMessage(), type: 'error');

            return null;
        }
    }

    public function deleteBackup(string $path): void
    {
        try {
            $disk = Storage::disk('sftp');

            if ($disk->exists($path)) {
                $disk->delete($path);
                $this->dispatch('notify', message: 'Backup slettet', type: 'success');
                $this->loadBackups();
            } else {
                $this->dispatch('notify', message: 'Backup-fil ikke funnet', type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Feil ved sletting: '.$e->getMessage(), type: 'error');
        }
    }

    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    public function render()
    {
        return view('livewire.user.backup');
    }
}
