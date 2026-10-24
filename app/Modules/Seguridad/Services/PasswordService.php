<?php

namespace App\Modules\Seguridad\Services;

class PasswordService
{
    /**
     * Verifica una contraseña plana contra el hash almacenado, soportando:
     * 1. PBKDF2 de Java (formato: PBKDF2$120000$saltBase64$hashBase64)
     * 2. Bcrypt nativo de PHP / Laravel
     */
    public function verify(string $password, string $storedHash): bool
    {
        if (empty($storedHash)) {
            return false;
        }

        // Si es formato PBKDF2 originario de Java:
        if (str_starts_with($storedHash, 'PBKDF2$')) {
            return $this->verifyPbkdf2($password, $storedHash);
        }

        // Si es Bcrypt / Argon2 nativo de PHP:
        return password_verify($password, $storedHash);
    }

    /**
     * Genera un hash seguro para nuevas contraseñas (Bcrypt estándar de Laravel).
     */
    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verifica PBKDF2WithHmacSHA256 con 120,000 iteraciones y salt Base64.
     */
    private function verifyPbkdf2(string $password, string $storedHash): bool
    {
        $parts = explode('$', $storedHash);
        if (count($parts) !== 4 || $parts[0] !== 'PBKDF2') {
            return false;
        }

        $iterations = (int) $parts[1];
        $salt = base64_decode($parts[2]);
        $expectedHash = base64_decode($parts[3]);

        if (!$salt || !$expectedHash || $iterations <= 0) {
            return false;
        }

        $calculated = hash_pbkdf2('sha256', $password, $salt, $iterations, strlen($expectedHash), true);

        return hash_equals($expectedHash, $calculated);
    }
}
