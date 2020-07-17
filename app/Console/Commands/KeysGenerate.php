<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class KeysGenerate extends Command
{
    protected $signature = 'keys:generate';
    protected $description = 'Generate private/public keys';

    public function handle()
    {
        try {
            $storage = Storage::createLocalDriver([ 'root' => base_path('licenses') ]);

            $res = openssl_pkey_new([
                'digest_alg' => 'sha256',
                'private_key_bits' => 1024,
                'private_key_type' => OPENSSL_KEYTYPE_RSA
            ]);

            openssl_pkey_export($res, $privKey);
            $pubKey = openssl_pkey_get_details($res)['key'];

            $storage->put('_private.key', $privKey);
            $storage->put('_public.key', $pubKey);

            $this->info('New Private/Public keys successfuly generated in /licenses');
        } catch (Exception $e) {
            $this->error($e);
        }
    }
}
