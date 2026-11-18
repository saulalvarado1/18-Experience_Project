<?php

namespace App\Modules\Clientes\Services;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Seguridad\Models\Auditoria;
use App\Modules\Seguridad\Models\Persona;
use Illuminate\Support\Facades\DB;

class ClienteService
{
    protected ValidadorPersonaService $validador;

    public function __construct(ValidadorPersonaService $validador)
    {
        $this->validador = $validador;
    }

    public function listar(?string $termino = null, ?string $estado = 'TODOS')
    {
        $query = Cliente::with('persona')
            ->join('persona', 'persona.id_persona', '=', 'cliente.id_persona')
            ->select('cliente.*');

        if (!empty($termino)) {
            $t = '%' . trim($termino) . '%';
            $query->where(function ($q) use ($t) {
                $q->where('persona.numero_documento', 'like', $t)
                  ->orWhere('persona.nombres', 'like', $t)
                  ->orWhere('persona.apellidos', 'like', $t)
                  ->orWhere('persona.telefono', 'like', $t)
                  ->orWhere('persona.correo', 'like', $t);
            });
        }

        if ($estado === 'ACTIVOS') {
            $query->where('cliente.activo', 1);
        } elseif ($estado === 'INACTIVOS') {
            $query->where('cliente.activo', 0);
        }

        return $query->orderBy('persona.apellidos')
                     ->orderBy('persona.nombres')
                     ->paginate(15);
    }

    /**
     * Busca una persona por su documento (RN02: identidad única por tipo y número).
     */
    public function buscarPersonaPorDocumento(string $tipoDocumento, string $numeroDocumento): ?Persona
    {
        return Persona::where('tipo_documento', strtoupper(trim($tipoDocumento)))
            ->where('numero_documento', strtoupper(trim($numeroDocumento)))
            ->first();
    }

    /**
     * Reutiliza la persona del documento o la registra si no existe.
     * Si el documento ya pertenece a alguien con otros nombres, se rechaza (RN02).
     * La usan Clientes (CU06) y Reservas para los pasajeros (CU20, RN13).
     */
    public function obtenerOCrearPersona(array $datosPersona): Persona
    {
        $tipoDoc = strtoupper(trim((string) ($datosPersona['tipo_documento'] ?? '')));
        $numDoc = strtoupper(trim((string) ($datosPersona['numero_documento'] ?? '')));
        $nombres = trim((string) ($datosPersona['nombres'] ?? ''));
        $apellidos = trim((string) ($datosPersona['apellidos'] ?? ''));

        // Una persona ya registrada se reutiliza aunque su documento sea de datos heredados con otro formato;
        // el formato completo solo se exige al registrar personas nuevas.
        $existente = ($tipoDoc !== '' && $numDoc !== '') ? $this->buscarPersonaPorDocumento($tipoDoc, $numDoc) : null;
        if (!$existente) {
            $this->validador->validar($datosPersona);
        }
        if ($existente) {
            $mismo = fn ($a, $b) => mb_strtolower(trim($a)) === mb_strtolower(trim($b));
            if (!$mismo($existente->nombres, $nombres) || !$mismo($existente->apellidos, $apellidos)) {
                throw new \InvalidArgumentException(
                    "El documento {$tipoDoc} {$numDoc} ya pertenece a otra persona registrada con diferentes nombres."
                );
            }
            return $existente;
        }

        return Persona::create([
            'tipo_documento' => $tipoDoc,
            'numero_documento' => $numDoc,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => !empty($datosPersona['fecha_nacimiento']) ? $datosPersona['fecha_nacimiento'] : null,
            'nacionalidad' => !empty($datosPersona['nacionalidad']) ? strtoupper(trim($datosPersona['nacionalidad'])) : 'PERUANA',
            'genero' => $datosPersona['genero'] ?? null,
            'telefono' => $datosPersona['telefono'] ?? null,
            'correo' => $datosPersona['correo'] ?? null,
            'activo' => true,
        ]);
    }

    public function registrar(array $datosPersona, string $tipoCliente, int $idUsuario): Cliente
    {
        // 1. Validar reglas de persona
        $this->validador->validar($datosPersona);

        $tipoDoc = strtoupper(trim($datosPersona['tipo_documento']));
        $numDoc = trim($datosPersona['numero_documento']);
        $tipoCliente = strtoupper(trim($tipoCliente));

        if (!in_array($tipoCliente, ['DIRECTO', 'CORPORATIVO'])) {
            $tipoCliente = 'DIRECTO';
        }

        return DB::transaction(function () use ($datosPersona, $tipoDoc, $numDoc, $tipoCliente, $idUsuario) {
            // Si la persona ya existe y ya es cliente, no se registra de nuevo (RN02)
            $personaExistente = $this->buscarPersonaPorDocumento($tipoDoc, $numDoc);
            if ($personaExistente && Cliente::where('id_persona', $personaExistente->id_persona)->exists()) {
                throw new \InvalidArgumentException('La persona ya está registrada como cliente.');
            }

            $persona = $this->obtenerOCrearPersona($datosPersona);

            // Crear el registro de cliente
            $cliente = Cliente::create([
                'id_persona' => $persona->id_persona,
                'fecha_registro' => now(),
                'tipo_cliente' => $tipoCliente,
                'activo' => true,
            ]);

            // Auditoría
            Auditoria::registrar(
                $idUsuario,
                'CREAR_CLIENTE',
                'cliente',
                $cliente->id_cliente,
                "Cliente registrado: {$persona->nombre_completo} ({$tipoDoc} {$numDoc})"
            );

            return $cliente;
        });
    }

    public function actualizar(int $idCliente, array $datosPersona, string $tipoCliente, int $idUsuario): Cliente
    {
        $this->validador->validar($datosPersona);

        return DB::transaction(function () use ($idCliente, $datosPersona, $tipoCliente, $idUsuario) {
            $cliente = Cliente::with('persona')->findOrFail($idCliente);
            $persona = $cliente->persona;

            $tipoDoc = strtoupper(trim($datosPersona['tipo_documento']));
            $numDoc = trim($datosPersona['numero_documento']);

            // Verificar si el nuevo documento colisiona con otra persona
            $conflicto = Persona::where('tipo_documento', $tipoDoc)
                ->where('numero_documento', $numDoc)
                ->where('id_persona', '<>', $persona->id_persona)
                ->exists();

            if ($conflicto) {
                throw new \InvalidArgumentException('El documento ingresado ya pertenece a otra persona.');
            }

            $persona->update([
                'tipo_documento' => $tipoDoc,
                'numero_documento' => $numDoc,
                'nombres' => trim($datosPersona['nombres']),
                'apellidos' => trim($datosPersona['apellidos']),
                'fecha_nacimiento' => !empty($datosPersona['fecha_nacimiento']) ? $datosPersona['fecha_nacimiento'] : null,
                'nacionalidad' => !empty($datosPersona['nacionalidad']) ? strtoupper(trim($datosPersona['nacionalidad'])) : $persona->nacionalidad,
                'genero' => $datosPersona['genero'] ?? $persona->genero,
                'telefono' => $datosPersona['telefono'] ?? null,
                'correo' => $datosPersona['correo'] ?? null,
            ]);

            $cliente->update([
                'tipo_cliente' => in_array(strtoupper($tipoCliente), ['DIRECTO', 'CORPORATIVO']) ? strtoupper($tipoCliente) : 'DIRECTO',
            ]);

            Auditoria::registrar(
                $idUsuario,
                'EDITAR_CLIENTE',
                'cliente',
                $cliente->id_cliente,
                "Cliente actualizado: {$persona->nombre_completo}"
            );

            return $cliente;
        });
    }

    public function cambiarEstado(int $idCliente, bool $activo, int $idUsuario): Cliente
    {
        $cliente = Cliente::findOrFail($idCliente);
        $cliente->activo = $activo;
        $cliente->save();

        Auditoria::registrar(
            $idUsuario,
            $activo ? 'ACTIVAR_CLIENTE' : 'DESACTIVAR_CLIENTE',
            'cliente',
            $cliente->id_cliente,
            $activo ? 'Cliente reactivado.' : 'Cliente desactivado.'
        );

        return $cliente;
    }

    public function obtenerHistorial(int $idCliente): array
    {
        $cliente = Cliente::with('persona')->findOrFail($idCliente);

        // 1. Reservas del cliente
        $reservas = DB::table('reserva as r')
            ->join('cotizacion as c', 'c.id_cotizacion', '=', 'r.id_cotizacion')
            ->leftJoin('vw_saldo_reserva as sr', 'sr.id_reserva', '=', 'r.id_reserva')
            ->where('c.id_cliente', $idCliente)
            ->select(
                'r.id_reserva',
                'r.codigo',
                'r.fecha_reserva',
                'r.total_contratado',
                'r.moneda',
                'r.estado',
                DB::raw('COALESCE(sr.saldo_pendiente, r.total_contratado) as saldo_pendiente')
            )
            ->orderBy('r.fecha_reserva', 'desc')
            ->get();

        // 2. Cotizaciones del cliente
        $cotizaciones = DB::table('cotizacion as c')
            ->join('usuario as u', 'u.id_usuario', '=', 'c.id_usuario_counter')
            ->join('persona as p', 'p.id_persona', '=', 'u.id_persona')
            ->where('c.id_cliente', $idCliente)
            ->select(
                'c.id_cotizacion',
                'c.codigo',
                'c.fecha_cotizacion',
                'c.total',
                'c.moneda',
                'c.estado',
                DB::raw("CONCAT(p.nombres, ' ', p.apellidos) as counter")
            )
            ->orderBy('c.fecha_cotizacion', 'desc')
            ->get();

        // 3. Pagos realizados por el cliente
        $pagos = DB::table('pago as pg')
            ->join('cotizacion as c', 'c.id_cotizacion', '=', 'pg.id_cotizacion')
            ->join('metodo_pago as mp', 'mp.id_metodo_pago', '=', 'pg.id_metodo_pago')
            ->where('c.id_cliente', $idCliente)
            ->select(
                'pg.id_pago',
                'pg.fecha_pago',
                'c.codigo as cotizacion_codigo',
                'mp.nombre as metodo_pago',
                'pg.moneda',
                'pg.monto_total_pagado',
                'pg.estado'
            )
            ->orderBy('pg.fecha_pago', 'desc')
            ->get();

        return [
            'cliente' => $cliente,
            'reservas' => $reservas,
            'cotizaciones' => $cotizaciones,
            'pagos' => $pagos,
        ];
    }
}
