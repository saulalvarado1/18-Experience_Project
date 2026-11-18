<?php

namespace Tests\Feature;

use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Seguridad\Services\PasswordService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** CU05 – Cambiar contraseña de usuario (GERENTE, mínimo 8 caracteres). */
class CambiarContrasenaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function usuario(string $rol): Usuario
    {
        return Usuario::whereHas('rol', fn ($q) => $q->where('nombre', $rol))->firstOrFail();
    }

    public function test_gerente_cambia_la_contrasena_y_la_anterior_deja_de_valer()
    {
        $counter = $this->usuario('COUNTER');
        $hashAnterior = $counter->password_hash;

        $this->actingAs($this->usuario('GERENTE'))
            ->post("/usuarios/{$counter->id_usuario}/password", ['password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026'])
            ->assertRedirect('/usuarios')
            ->assertSessionHas('success');

        $counter->refresh();
        $this->assertNotSame($hashAnterior, $counter->password_hash);
        $this->assertTrue(app(PasswordService::class)->verify('NuevaClave2026', $counter->password_hash));
        $this->assertDatabaseHas('auditoria', ['accion' => 'CAMBIAR_CONTRASENA', 'id_registro' => $counter->id_usuario]);
        // La contraseña nunca queda escrita en la auditoría.
        $this->assertDatabaseMissing('auditoria', ['accion' => 'CAMBIAR_CONTRASENA', 'detalle' => 'NuevaClave2026']);
        $this->assertFalse(DB::table('auditoria')->where('detalle', 'like', '%NuevaClave2026%')->exists());
    }

    public function test_rechaza_contrasena_corta_o_distinta()
    {
        $counter = $this->usuario('COUNTER');
        $hash = $counter->password_hash;
        $gerente = $this->usuario('GERENTE');

        $this->actingAs($gerente)->post("/usuarios/{$counter->id_usuario}/password", ['password' => 'corta1', 'password_confirmation' => 'corta1'])
            ->assertSessionHas('error', 'La contraseña debe tener al menos 8 caracteres.');
        $this->actingAs($gerente)->post("/usuarios/{$counter->id_usuario}/password", ['password' => 'Clave12345', 'password_confirmation' => 'Clave54321'])
            ->assertSessionHas('error', 'Las contraseñas no coinciden.');

        $this->assertSame($hash, $counter->fresh()->password_hash);
    }

    public function test_solo_el_gerente_puede_cambiar_contrasenas()
    {
        $contabilidad = $this->usuario('CONTABILIDAD');

        $this->actingAs($this->usuario('COUNTER'))
            ->post("/usuarios/{$contabilidad->id_usuario}/password", ['password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026'])
            ->assertForbidden();
    }

    public function test_alta_de_usuario_exige_ocho_caracteres()
    {
        $this->actingAs($this->usuario('GERENTE'))->post('/usuarios', [
            'tipo_documento' => 'DNI', 'numero_documento' => $this->dniDePrueba(), 'nombres' => 'Rosa', 'apellidos' => 'Quispe Mamani',
            'id_rol' => $this->usuario('COUNTER')->id_rol, 'nombre_usuario' => 'rosa.' . uniqid(), 'password' => 'corta1',
        ])->assertSessionHasErrors('password');
    }
}
