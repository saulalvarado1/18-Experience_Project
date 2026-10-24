<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Seguridad\Services\PasswordService;

class PasswordServiceTest extends TestCase
{
    private PasswordService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PasswordService();
    }

    public function test_verifies_bcrypt_passwords(): void
    {
        $hash = $this->service->hash('Secreto123');

        $this->assertTrue($this->service->verify('Secreto123', $hash));
        $this->assertFalse($this->service->verify('Erronea', $hash));
    }

    public function test_verifies_java_pbkdf2_hashes(): void
    {
        // Hash de prueba generado con PBKDF2 (120000 iteraciones con salt)
        $salt = random_bytes(16);
        $rawHash = hash_pbkdf2('sha256', 'PasswordPeru2026', $salt, 120000, 32, true);
        $pbkdf2Hash = 'PBKDF2$120000$' . base64_encode($salt) . '$' . base64_encode($rawHash);

        $this->assertTrue($this->service->verify('PasswordPeru2026', $pbkdf2Hash));
        $this->assertFalse($this->service->verify('ClaveInvalida', $pbkdf2Hash));
    }
}
