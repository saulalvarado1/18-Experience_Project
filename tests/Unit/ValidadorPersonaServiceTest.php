<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Clientes\Services\ValidadorPersonaService;

class ValidadorPersonaServiceTest extends TestCase
{
    private ValidadorPersonaService $validador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validador = new ValidadorPersonaService();
    }

    public function test_validates_correct_dni(): void
    {
        $datos = [
            'tipo_documento' => 'DNI',
            'numero_documento' => '72345678',
            'nombres' => 'Juan Alberto',
            'apellidos' => 'Pérez Gómez',
            'telefono' => '952345678',
            'correo' => 'juan@example.com',
            'fecha_nacimiento' => '1992-04-15',
        ];

        // No debe lanzar excepciones
        $this->validador->validar($datos);
        $this->assertTrue(true);
    }

    public function test_rejects_invalid_dni_formats(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validador->validarDocumento('DNI', '12345'); // Menos de 8 dígitos
    }

    public function test_rejects_dummy_dni_series(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validador->validarDocumento('DNI', '00000005');
    }

    public function test_rejects_identical_digits_dni(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validador->validarDocumento('DNI', '33333333');
    }

    public function test_rejects_numbers_in_names(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validador->validar([
            'tipo_documento' => 'DNI',
            'numero_documento' => '78451296',
            'nombres' => 'Juan 2do',
            'apellidos' => 'Pérez',
        ]);
    }
}
