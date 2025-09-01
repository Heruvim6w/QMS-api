<?php

declare(strict_types=1);

namespace App\Services;

use Random\RandomException;

class EncryptionService
{
    /**
     * @throws RandomException
     */
    public function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function encrypt(string $plaintext, string $key, string $iv): string
    {
        $cipher = "aes-256-gcm";

        if (!in_array($cipher, openssl_get_cipher_methods(), true)) {
            throw new \RuntimeException('Cipher method not available');
        }

        $encrypted = openssl_encrypt(
            $plaintext,
            $cipher,
            hex2bin($key),
            OPENSSL_RAW_DATA,
            hex2bin($iv),
            $tag
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return bin2hex($tag . $encrypted);
    }

    public function decrypt(string $encryptedData, string $key, string $iv): string
    {
        $cipher = "aes-256-gcm";
        $data = hex2bin($encryptedData);

        $tag = substr($data, 0, 16);
        $ciphertext = substr($data, 16);

        $decrypted = openssl_decrypt(
            $ciphertext,
            $cipher,
            hex2bin($key),
            OPENSSL_RAW_DATA,
            hex2bin($iv),
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $decrypted;
    }

    /**
     * @throws RandomException
     */
    public function encryptForUser(string $plaintext, string $publicKey): array
    {
        $sessionKey = $this->generateKey();
        $iv = bin2hex(random_bytes(16));

        $encryptedContent = $this->encrypt($plaintext, $sessionKey, $iv);

        openssl_public_encrypt(hex2bin($sessionKey), $encryptedKey, $publicKey);

        return [
            'encrypted_content' => $encryptedContent,
            'encrypted_key' => bin2hex($encryptedKey),
            'iv' => $iv
        ];
    }

    public function decryptForUser(string $encryptedContent, string $encryptedKey, string $iv, string $privateKey): string
    {
        openssl_private_decrypt(hex2bin($encryptedKey), $sessionKey, $privateKey);

        if ($sessionKey === false) {
            throw new \RuntimeException('Failed to decrypt session key');
        }

        return $this->decrypt($encryptedContent, bin2hex($sessionKey), $iv);
    }
}
