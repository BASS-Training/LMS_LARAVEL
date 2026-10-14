<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Filesystem\Filesystem;

class GenerateSensitiveDataKey extends Command
{
    protected $signature = 'sensitive-data:key {--show : Tampilkan kunci baru tanpa mengubah .env}';

    protected $description = 'Buat kunci AES-256-GCM untuk enkripsi nomor HP';

    public function handle(Filesystem $files): int
    {
        $key = 'base64:'.base64_encode(Encrypter::generateKey('aes-256-gcm'));

        if ($this->option('show')) {
            $this->line($key);

            return self::SUCCESS;
        }

        $path = $this->laravel->environmentFilePath();

        if (! $files->exists($path)) {
            $this->error('File environment belum tersedia. Gunakan --show untuk secret manager.');

            return self::FAILURE;
        }

        $environment = $files->get($path);

        if (preg_match('/^SENSITIVE_DATA_KEY=(.*)$/m', $environment, $matches)) {
            if (trim($matches[1], " \t\r\n\"'") !== '') {
                $this->error('Kunci sudah tersedia. Gunakan --show dan prosedur rotasi untuk menggantinya.');

                return self::FAILURE;
            }

            $environment = preg_replace('/^SENSITIVE_DATA_KEY=.*$/m', 'SENSITIVE_DATA_KEY='.$key, $environment);
        } else {
            $environment = rtrim($environment).PHP_EOL.'SENSITIVE_DATA_KEY='.$key.PHP_EOL;
        }

        $files->put($path, $environment);
        $this->info('Kunci data sensitif berhasil disimpan di environment. Jalankan config:clear atau config:cache.');

        return self::SUCCESS;
    }
}
