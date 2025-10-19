<?php
namespace App\Services;

class CryptoService {
    public static function aes256gcm_encrypt(string $plaintext, string $cek_raw): array {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $cek_raw, OPENSSL_RAW_DATA, $iv, $tag);
        return ['iv'=>$iv, 'tag'=>$tag, 'cipher'=>$cipher];
    }
    public static function aes256gcm_decrypt(string $cipher, string $cek_raw, string $iv, string $tag): string|false {
        return openssl_decrypt($cipher,'aes-256-gcm',$cek_raw,OPENSSL_RAW_DATA,$iv,$tag);
    }
    public static function wrapCEK(string $cek_raw, string $kek_raw): string {
        $enc = self::aes256gcm_encrypt($cek_raw, $kek_raw);
        return base64_encode($enc['iv']).'.'.base64_encode($enc['tag']).'.'.base64_encode($enc['cipher']);
    }
    public static function unwrapCEK(string $wrapped, string $kek_raw): string {
        [$iv_b64,$tag_b64,$c_b64] = explode('.', $wrapped);
        $iv = base64_decode($iv_b64);
        $tag = base64_decode($tag_b64);
        $c   = base64_decode($c_b64);
        $cek = self::aes256gcm_decrypt($c, $kek_raw, $iv, $tag);
        if ($cek === false) throw new \RuntimeException('Unwrap failed');
        return $cek;
    }
}
