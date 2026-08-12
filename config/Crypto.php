<?php
/**
 * Crypto.php — Cifrado simétrico mínimo para datos sensibles guardados en BD
 * (uso exclusivo: contraseña SMTP propia del Gestor de envío masivo de correos).
 *
 * No se usa para contraseñas de usuarios (esas siguen con password_hash/verify).
 * Requiere APP_ENCRYPTION_KEY en .env (64 caracteres hex = 32 bytes, AES-256).
 */

class Crypto
{
    private const CIPHER = 'aes-256-cbc';

    private static function key(): string
    {
        $hex = $_ENV['APP_ENCRYPTION_KEY'] ?? '';
        if (!$hex || !ctype_xdigit($hex) || strlen($hex) !== 64) {
            throw new RuntimeException('APP_ENCRYPTION_KEY no está configurada correctamente en .env (deben ser 64 caracteres hexadecimales).');
        }
        return hex2bin($hex);
    }

    /**
     * Cifra un texto plano. Devuelve "<iv_hex>:<ciphertext_base64>", o null si $plain está vacío.
     */
    public static function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '') {
            return null;
        }
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLen);
        $cipherText = openssl_encrypt($plain, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        if ($cipherText === false) {
            throw new RuntimeException('No se pudo cifrar el valor.');
        }
        return bin2hex($iv) . ':' . base64_encode($cipherText);
    }

    /**
     * Descifra un valor generado por encrypt(). Devuelve null si $encoded está vacío o es inválido.
     */
    public static function decrypt(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return null;
        }
        $parts = explode(':', $encoded, 2);
        if (count($parts) !== 2 || !ctype_xdigit($parts[0])) {
            return null;
        }
        $iv = hex2bin($parts[0]);
        $cipherText = base64_decode($parts[1], true);
        if ($iv === false || $cipherText === false) {
            return null;
        }
        $plain = openssl_decrypt($cipherText, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? null : $plain;
    }
}
