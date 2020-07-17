<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LicenseGenerate extends Command
{
    protected $signature = 'license:generate {license}';
    protected $description = 'Generate license';

    // 1024/8 = 128 - 11 (padding) = 117 (-17 safe space)
    private $ENCRYPT_BLOCK_SIZE = 100;

    public function handle()
    {
        try {
            $licenseName = $this->argument('license');

            $storage = Storage::createLocalDriver([ 'root' => base_path('licenses') ]);
            $storageOutput = Storage::createLocalDriver([ 'root' => base_path('licenses_output') ]);

            try {
                $privKey = $storage->get('_private.key');
            } catch (Exception $e) {
                $this->error('Private key not found. Please run keys:generate command.');
                return;
            }

            try {
                $license = json_decode($storage->get("{$licenseName}.json"));
            } catch (Exception $e) {
                $this->error("/licenses/{$licenseName}.json not found.");
                return;
            }

            $hash = $this->randomSha256();
            $md5_hash = md5($hash);

            $license->md5_hash = $md5_hash;
            $encrypted = $this->encrypt( json_encode($license), $privKey );

            $storageOutput->put("{$licenseName}/license", $encrypted);
            $storageOutput->put("{$licenseName}/hash.key", $hash);

            $this->info($encrypted);
        } catch (Exception $e) {
            $this->error($e);
        }
    }

    private function encrypt(string $message, string $privKey): string
    {
        $encrypted = '';
        $chunks = str_split($message, $this->ENCRYPT_BLOCK_SIZE);

        foreach ($chunks as $chunk) {
            openssl_private_encrypt($chunk, $partialEncrypted, $privKey, OPENSSL_PKCS1_PADDING);
            $encrypted .= $partialEncrypted;
        }

        return base64_encode($encrypted);
    }

    private function randomSha256(): string
    {
        return hash('sha256', openssl_random_pseudo_bytes(20));
    }
}
