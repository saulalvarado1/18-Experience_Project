<?php

namespace App\Modules\Clientes\Services;

class ValidadorPersonaService
{
    /**
     * Valida los datos personales según las reglas de negocio (RN02).
     *
     * @throws \InvalidArgumentException Si algún campo no cumple las reglas.
     */
    public function validar(array $datos): void
    {
        $tipoDoc = strtoupper(trim($datos['tipo_documento'] ?? ''));
        $numDoc = trim($datos['numero_documento'] ?? '');
        $nombres = trim($datos['nombres'] ?? '');
        $apellidos = trim($datos['apellidos'] ?? '');
        $telefono = trim($datos['telefono'] ?? '');
        $correo = trim($datos['correo'] ?? '');

        // 1. Validar Tipo y Número de Documento
        $this->validarDocumento($tipoDoc, $numDoc);

        // 2. Validar Nombres y Apellidos
        $this->validarNombre($nombres, 'nombres');
        $this->validarNombre($apellidos, 'apellidos');

        // 3. Validar Teléfono (opcional pero con formato si viene)
        if (!empty($telefono)) {
            $limpio = preg_replace('/[\s\-]/', '', $telefono);
            if (!preg_match('/^\+?\d{6,15}$/', $limpio)) {
                throw new \InvalidArgumentException('El número de teléfono no tiene un formato válido.');
            }
        }

        // 4. Validar Correo (opcional pero con formato si viene)
        if (!empty($correo)) {
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Ingrese un correo electrónico válido.');
            }
        }

        // 5. Validar Fecha de Nacimiento
        if (!empty($datos['fecha_nacimiento'])) {
            $fecha = strtotime($datos['fecha_nacimiento']);
            if (!$fecha || $fecha > time()) {
                throw new \InvalidArgumentException('La fecha de nacimiento no puede ser futura.');
            }
        }
    }

    public function validarDocumento(string $tipo, string $numero): void
    {
        if (empty($tipo)) {
            throw new \InvalidArgumentException('Seleccione el tipo de documento.');
        }

        if (empty($numero)) {
            throw new \InvalidArgumentException('Ingrese el número de documento.');
        }

        if ($tipo === 'DNI') {
            // Exactamente 8 dígitos
            if (!preg_match('/^\d{8}$/', $numero)) {
                throw new \InvalidArgumentException('El DNI debe contener exactamente 8 dígitos numéricos.');
            }

            // Evitar pruebas 00000000 .. 00000009
            if (preg_match('/^0000000\d$/', $numero)) {
                throw new \InvalidArgumentException('Ingrese un DNI válido.');
            }

            // Evitar dígitos repetidos (11111111, 22222222, etc.)
            if (preg_match('/^(\d)\1{7}$/', $numero)) {
                throw new \InvalidArgumentException('Ingrese un DNI válido.');
            }
        } elseif ($tipo === 'PASAPORTE') {
            if (!preg_match('/^[A-Za-z0-9]{5,20}$/', $numero)) {
                throw new \InvalidArgumentException('El pasaporte debe contener entre 5 y 20 caracteres alfanuméricos.');
            }
        } elseif (in_array($tipo, ['CE', 'CARNET DE EXTRANJERIA', 'CARNET DE EXTRANJERÍA'])) {
            if (!preg_match('/^[A-Za-z0-9]{6,15}$/', $numero)) {
                throw new \InvalidArgumentException('El Carnet de Extranjería debe contener entre 6 y 15 caracteres.');
            }
        }

        if (strlen($numero) > 30) {
            throw new \InvalidArgumentException('El número de documento es demasiado largo.');
        }
    }

    private function validarNombre(string $texto, string $campo): void
    {
        if (empty($texto)) {
            throw new \InvalidArgumentException("Ingrese los {$campo}.");
        }

        if (strlen($texto) < 2) {
            throw new \InvalidArgumentException("Los {$campo} deben tener al menos 2 caracteres.");
        }

        if (preg_match('/\d/', $texto)) {
            throw new \InvalidArgumentException("Los {$campo} no deben contener números.");
        }
    }
}
