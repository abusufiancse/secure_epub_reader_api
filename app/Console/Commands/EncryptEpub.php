<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ebook;
use App\Models\KeyWrap;
use App\Services\CryptoService;
use Illuminate\Support\Facades\Storage;

class EncryptEpub extends Command {
    protected $signature = 'epub:encrypt {epub_path} {--title=Sample Book} {--free=0} {--price=19900}';
    protected $description = 'Encrypt a local EPUB and register it';

    public function handle() {
        $epubPath = $this->argument('epub_path');
        if (!file_exists($epubPath)) { $this->error('Input not found'); return 1; }
        $plaintext = file_get_contents($epubPath);
        $cek_raw = random_bytes(32);
        $kek_raw = base64_decode(env('BOOKS_MASTER_KEK_B64'));

        $enc = CryptoService::aes256gcm_encrypt($plaintext, $cek_raw);
        $blob = json_encode([
            'v' => 1,
            'iv'=> base64_encode($enc['iv']),
            'tag'=> base64_encode($enc['tag']),
            'data'=> base64_encode($enc['cipher']),
        ], JSON_UNESCAPED_SLASHES);

        $ebook = Ebook::create([
            'title' => $this->option('title'),
            'author'=> null,
            'is_free'=> (bool)$this->option('free'),
            'price_cents'=> (int)$this->option('price'),
            'enc_path'=> 'ebooks/'.uniqid().'.enc'
        ]);

        Storage::put($ebook->enc_path, $blob);

        KeyWrap::create([
            'ebook_id'=>$ebook->id,
            'wrapped_cek_b64'=> CryptoService::wrapCEK($cek_raw, $kek_raw)
        ]);

        $this->info('Encrypted & saved: ebook_id='.$ebook->id);
        return 0;
    }
}
