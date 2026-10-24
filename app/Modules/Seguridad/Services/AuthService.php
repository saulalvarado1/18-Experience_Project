<?php

namespace App\Modules\Seguridad\Services;

use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthService
{
    protected PasswordService $passwordService;

    public function __construct(PasswordService $passwordService)
    {
        $this->passwordService = $passwordService;
    }

    /**
     * Genera un código CAPTCHA de 5 caracteres alfanuméricos en mayúsculas (CU01).
     */
    public function generateCaptcha(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $captcha = '';
        for ($i = 0; $i < 5; $i++) {
            $captcha .= $characters[random_int(0, strlen($characters) - 1)];
        }

        session(['login_captcha' => $captcha]);

        return $captcha;
    }

    /**
     * Valida el CAPTCHA ingresado de manera case-insensitive.
     */
    public function validateCaptcha(?string $input): bool
    {
        if (empty($input)) {
            return false;
        }

        $expected = session('login_captcha');
        if (empty($expected)) {
            return false;
        }

        return strtoupper(trim($input)) === strtoupper(trim($expected));
    }

    /**
     * Autentica al usuario por nombre de usuario y contraseña (CU01).
     *
     * @throws \Exception Si el usuario no existe, está inactivo o la contraseña es inválida.
     */
    public function authenticate(string $username, string $password): Usuario
    {
        $usuario = Usuario::with(['persona', 'rol'])
            ->where('nombre_usuario', trim($username))
            ->first();

        if (!$usuario) {
            throw new \Exception('Usuario o contraseña incorrectos.');
        }

        if (!$usuario->activo) {
            throw new \Exception('Su cuenta de usuario se encuentra inactiva.');
        }

        if (!$usuario->persona || !$usuario->persona->activo) {
            throw new \Exception('El registro de personal asociado está inactivo.');
        }

        if (!$usuario->rol || !$usuario->rol->activo) {
            throw new \Exception('El rol asignado a su cuenta no está habilitado.');
        }

        if (!$this->passwordService->verify($password, $usuario->password_hash)) {
            throw new \Exception('Usuario o contraseña incorrectos.');
        }

        // Actualizar último acceso (SRS CU01)
        $usuario->ultimo_acceso = now();
        $usuario->save();

        Auth::login($usuario);

        return $usuario;
    }
}
