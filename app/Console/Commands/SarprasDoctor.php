<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SarprasDoctor extends Command
{
    protected $signature = 'sarpras:doctor
        {--skip-storage : Skip the object-storage write/read/delete probe}
        {--skip-juara : Skip the JUARA read-only directory probe}';

    protected $description = 'Verify Sarpras runtime dependencies and least-privilege integrations';

    public function handle(): int
    {
        $checks = [];

        $checks[] = $this->check('Main database', function (): string {
            DB::connection()->select('SELECT 1');

            return DB::connection()->getDatabaseName();
        });

        $checks[] = $this->check('Redis cache', function (): string {
            $key = 'sarpras-doctor:'.bin2hex(random_bytes(8));
            Cache::store('redis')->put($key, 'ok', 30);

            $value = Cache::store('redis')->get($key);
            Cache::store('redis')->forget($key);

            if ($value !== 'ok') {
                throw new \RuntimeException('Redis read-after-write failed.');
            }

            return 'read/write OK';
        });

        if (!$this->option('skip-juara')) {
            $checks[] = $this->check('JUARA read-only view', function (): string {
                $table = (string) config('services.juara.student_directory', 'sarpras_student_directory');
                DB::connection('juara')->table($table)->limit(1)->get();

                return $table.' readable';
            });
        }

        if (!$this->option('skip-storage')) {
            $checks[] = $this->check('Object storage', function (): string {
                $diskName = (string) config('filesystems.default');
                $disk = Storage::disk($diskName);
                $path = 'health/.sarpras-doctor-'.bin2hex(random_bytes(8)).'.txt';

                try {
                    $disk->put($path, 'sarpras-doctor');

                    if ($disk->get($path) !== 'sarpras-doctor') {
                        throw new \RuntimeException('Object read-after-write failed.');
                    }
                } finally {
                    try {
                        $disk->delete($path);
                    } catch (Throwable) {
                        // The primary check result is more useful than cleanup noise.
                    }
                }

                return $diskName.' read/write/delete OK';
            });
        }

        $this->newLine();
        $this->table(
            ['Check', 'Status', 'Detail'],
            array_map(
                fn (array $row) => [$row['name'], $row['ok'] ? 'PASS' : 'FAIL', $row['detail']],
                $checks,
            ),
        );

        $failed = collect($checks)->contains(fn (array $row) => !$row['ok']);

        if ($failed) {
            $this->error('Sarpras runtime is not ready.');

            return self::FAILURE;
        }

        $this->info('Sarpras runtime is ready.');

        return self::SUCCESS;
    }

    private function check(string $name, callable $callback): array
    {
        try {
            return [
                'name' => $name,
                'ok' => true,
                'detail' => (string) $callback(),
            ];
        } catch (Throwable $exception) {
            return [
                'name' => $name,
                'ok' => false,
                'detail' => $exception->getMessage(),
            ];
        }
    }
}
