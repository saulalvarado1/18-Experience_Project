<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Seguridad\Models\Rol;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Seguridad\Services\PasswordService;
use Illuminate\Support\Facades\DB;

class LoginAndDashboardTest extends TestCase
{
    public function test_login_page_renders_successfully_with_captcha(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Iniciar Sesión');
        $response->assertSee('18 EXPERIENCE');
        $response->assertSessionHas('login_captcha');
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_login_rejects_invalid_captcha(): void
    {
        $this->get('/login');

        $response = $this->post('/login', [
            'usuario' => 'admin',
            'password' => 'cualquiera',
            'captcha' => 'WRONG',
        ]);

        $response->assertSessionHasErrors(['captcha']);
        $this->assertGuest();
    }

    public function test_authenticated_user_can_view_dashboard_and_logout(): void
    {
        $rol = Rol::where('nombre', 'GERENTE')->first();

        DB::beginTransaction();

        try {
            $persona = Persona::create([
                'tipo_documento' => 'DNI',
                'numero_documento' => '12344321',
                'nombres' => 'Usuario',
                'apellidos' => 'Prueba',
                'fecha_nacimiento' => '1990-05-10',
                'nacionalidad' => 'PERUANA',
                'genero' => 'M',
                'telefono' => '952111222',
                'correo' => 'prueba@18experience.pe',
                'activo' => true,
            ]);

            $passwordService = new PasswordService();

            $usuario = Usuario::create([
                'id_persona' => $persona->id_persona,
                'id_rol' => $rol->id_rol,
                'nombre_usuario' => 'usuario_dashboard_test',
                'password_hash' => $passwordService->hash('Dashboard2026!'),
                'activo' => true,
                'fecha_creacion' => now(),
            ]);

            // 1. Iniciar sesión simulando CAPTCHA válido
            $this->withSession(['login_captcha' => 'A1B2C']);

            $loginResponse = $this->post('/login', [
                'usuario' => 'usuario_dashboard_test',
                'password' => 'Dashboard2026!',
                'captcha' => 'A1B2C',
            ]);

            $loginResponse->assertRedirect('/');
            $this->assertAuthenticatedAs($usuario);

            // 2. Visitar el Dashboard (CU02)
            $dashboardResponse = $this->get('/');
            $dashboardResponse->assertStatus(200);
            $dashboardResponse->assertSee('RESERVAS HOY');
            $dashboardResponse->assertSee('SALIDAS HOY');
            $dashboardResponse->assertSee('PAGOS POR VALIDAR');
            $dashboardResponse->assertSee('PRÓXIMAS SALIDAS PROGRAMADAS');

            // 3. Cerrar sesión (CU03)
            $logoutResponse = $this->post('/logout');
            $logoutResponse->assertRedirect('/login');
            $this->assertGuest();

        } finally {
            DB::rollBack();
        }
    }
}
