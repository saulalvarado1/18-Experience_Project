<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Seguridad\Models\Rol;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Seguridad\Services\AuthService;
use App\Modules\Seguridad\Services\PasswordService;
use Illuminate\Support\Facades\DB;

class AuthServiceTest extends TestCase
{
    protected AuthService $authService;
    protected PasswordService $passwordService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passwordService = new PasswordService();
        $this->authService = new AuthService($this->passwordService);
    }

    public function test_captcha_generation_and_validation(): void
    {
        $captcha = $this->authService->generateCaptcha();

        $this->assertEquals(5, strlen($captcha));
        $this->assertTrue($this->authService->validateCaptcha(strtolower($captcha)));
        $this->assertTrue($this->authService->validateCaptcha(strtoupper($captcha)));
        $this->assertFalse($this->authService->validateCaptcha('XXXXX'));
    }

    public function test_user_authentication_flow(): void
    {
        // 1. Obtener el rol GERENTE existente
        $rol = Rol::where('nombre', 'GERENTE')->first();
        $this->assertNotNull($rol, 'El rol GERENTE debe existir en la BD');

        // 2. Crear una persona y un usuario de prueba en transacción
        DB::beginTransaction();

        try {
            $persona = Persona::create([
                'tipo_documento' => 'DNI',
                'numero_documento' => '99887766',
                'nombres' => 'Tester',
                'apellidos' => 'Seguridad',
                'fecha_nacimiento' => '1995-01-01',
                'nacionalidad' => 'PERUANA',
                'genero' => 'M',
                'telefono' => '952000111',
                'correo' => 'tester@18experience.pe',
                'activo' => true,
            ]);

            $usuario = Usuario::create([
                'id_persona' => $persona->id_persona,
                'id_rol' => $rol->id_rol,
                'nombre_usuario' => 'tester_seguridad',
                'password_hash' => $this->passwordService->hash('PruebaSegura123'),
                'activo' => true,
                'fecha_creacion' => now(),
            ]);

            // Autenticación exitosa
            $authenticated = $this->authService->authenticate('tester_seguridad', 'PruebaSegura123');
            $this->assertEquals($usuario->id_usuario, $authenticated->id_usuario);
            $this->assertTrue($authenticated->tieneRol('GERENTE'));
            $this->assertFalse($authenticated->tieneRol('COUNTER'));

            // Intento con clave incorrecta
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('Usuario o contraseña incorrectos.');
            $this->authService->authenticate('tester_seguridad', 'ClaveErronea');

        } finally {
            DB::rollBack();
        }
    }
}
