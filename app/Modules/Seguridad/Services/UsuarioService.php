<?php

namespace App\Modules\Seguridad\Services;

use App\Modules\Clientes\Services\ValidadorPersonaService;
use App\Modules\Seguridad\Models\Auditoria;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Seguridad\Models\Rol;
use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UsuarioService
{
    /** Longitud mínima de contraseña (SRS CU05). */
    public const MIN_PASSWORD = 8;

    public function __construct(
        private readonly PasswordService $passwordService,
        private readonly ValidadorPersonaService $validador,
    ) {
    }

    /** Roles asignables desde el módulo de usuarios (SRS CU04); CLIENTE se registra solo en el portal. */
    public const ROLES_PERSONAL = ['GERENTE', 'COUNTER', 'CONTABILIDAD'];

    /**
     * CU04 – Buscar usuarios por texto (usuario, nombres, apellidos o documento), rol y estado.
     */
    public function listar(?string $texto = null, ?int $idRol = null, ?string $estado = 'TODOS')
    {
        $query = Usuario::with(['persona', 'rol'])
            ->join('persona', 'persona.id_persona', '=', 'usuario.id_persona')
            ->select('usuario.*');

        if ($texto = trim((string) $texto)) {
            $t = '%' . mb_strtolower($texto) . '%';
            $query->where(function ($q) use ($t) {
                $q->whereRaw('LOWER(usuario.nombre_usuario) LIKE ?', [$t])
                  ->orWhereRaw('LOWER(persona.nombres) LIKE ?', [$t])
                  ->orWhereRaw('LOWER(persona.apellidos) LIKE ?', [$t])
                  ->orWhereRaw('LOWER(persona.numero_documento) LIKE ?', [$t]);
            });
        }
        if ($idRol) {
            $query->where('usuario.id_rol', $idRol);
        }
        if ($estado === 'ACTIVOS') {
            $query->where('usuario.activo', true);
        } elseif ($estado === 'INACTIVOS') {
            $query->where('usuario.activo', false);
        }

        return $query->orderBy('usuario.id_usuario', 'desc')->paginate(15)->withQueryString();
    }

    /**
     * CU04 flujo 2a – Editar usuario: persona (documento y nombres) y cuenta (nombre de usuario y rol)
     * en una sola transacción, sin duplicar documento ni nombre de usuario.
     */
    public function actualizar(int $idUsuario, array $datos, int $idGerente): Usuario
    {
        // El formato del documento se valida solo si cambia (hay documentos heredados del prototipo).
        $actual = Usuario::with('persona')->findOrFail($idUsuario)->persona;
        $mismoDocumento = strtoupper(trim((string) ($datos['tipo_documento'] ?? ''))) === strtoupper((string) $actual->tipo_documento)
            && strtoupper(trim((string) ($datos['numero_documento'] ?? ''))) === strtoupper((string) $actual->numero_documento);
        $this->validador->validar($datos, !$mismoDocumento);

        $tipoDoc = strtoupper(trim($datos['tipo_documento']));
        $numDoc = strtoupper(trim($datos['numero_documento']));
        $nombreUsuario = trim((string) ($datos['nombre_usuario'] ?? ''));
        if ($nombreUsuario === '' || mb_strlen($nombreUsuario) > 50) {
            throw new InvalidArgumentException('Ingrese un nombre de usuario de hasta 50 caracteres.');
        }

        $rol = Rol::where('activo', true)->whereIn('nombre', self::ROLES_PERSONAL)->find((int) ($datos['id_rol'] ?? 0));
        if (!$rol) {
            throw new InvalidArgumentException('Seleccione un rol válido (Gerente, Counter o Contabilidad).');
        }

        return DB::transaction(function () use ($idUsuario, $datos, $tipoDoc, $numDoc, $nombreUsuario, $rol, $idGerente) {
            $usuario = Usuario::with(['persona', 'rol'])->whereKey($idUsuario)->lockForUpdate()->firstOrFail();

            if ($usuario->rol?->nombre === 'CLIENTE') {
                throw new InvalidArgumentException('Las cuentas de clientes se gestionan desde el módulo Clientes.');
            }
            if ($usuario->id_usuario === $idGerente && $rol->nombre !== 'GERENTE') {
                throw new InvalidArgumentException('No puede quitarse a sí mismo el rol de Gerente.');
            }
            if (Usuario::where('nombre_usuario', $nombreUsuario)->where('id_usuario', '!=', $usuario->id_usuario)->exists()) {
                throw new InvalidArgumentException("El nombre de usuario {$nombreUsuario} ya está en uso.");
            }
            if (Persona::where('tipo_documento', $tipoDoc)->where('numero_documento', $numDoc)
                ->where('id_persona', '!=', $usuario->id_persona)->exists()) {
                throw new InvalidArgumentException('El documento ingresado ya pertenece a otra persona.');
            }

            $usuario->persona->update([
                'tipo_documento' => $tipoDoc,
                'numero_documento' => $numDoc,
                'nombres' => trim($datos['nombres']),
                'apellidos' => trim($datos['apellidos']),
                'telefono' => $datos['telefono'] ?? null,
                'correo' => $datos['correo'] ?? null,
            ]);

            $rolAnterior = $usuario->rol?->nombre;
            $usuario->nombre_usuario = $nombreUsuario;
            $usuario->id_rol = $rol->id_rol;
            $usuario->save();

            Auditoria::registrar($idGerente, 'EDITAR_USUARIO', 'usuario', $usuario->id_usuario,
                "Usuario {$usuario->nombre_usuario} actualizado" . ($rolAnterior !== $rol->nombre ? " (rol {$rolAnterior} → {$rol->nombre})" : '') . '.');

            return $usuario;
        });
    }

    /**
     * CU05 – Cambiar contraseña de usuario (solo GERENTE). La contraseña anterior deja de ser válida.
     */
    public function cambiarContrasena(int $idUsuario, string $nueva, string $confirmacion, int $idGerente): Usuario
    {
        if (mb_strlen($nueva) < self::MIN_PASSWORD) {
            throw new InvalidArgumentException('La contraseña debe tener al menos ' . self::MIN_PASSWORD . ' caracteres.');
        }
        if ($nueva !== $confirmacion) {
            throw new InvalidArgumentException('Las contraseñas no coinciden.');
        }

        return DB::transaction(function () use ($idUsuario, $nueva, $idGerente) {
            $usuario = Usuario::whereKey($idUsuario)->lockForUpdate()->firstOrFail();
            $usuario->password_hash = $this->passwordService->hash($nueva);
            $usuario->save();

            // Nunca se registra la contraseña en la auditoría (RN16).
            Auditoria::registrar(
                $idGerente,
                'CAMBIAR_CONTRASENA',
                'usuario',
                $usuario->id_usuario,
                "Contraseña del usuario {$usuario->nombre_usuario} cambiada por Gerencia."
            );

            return $usuario;
        });
    }
}
