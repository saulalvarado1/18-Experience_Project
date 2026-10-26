<?php

namespace App\Modules\PortalCliente\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Clientes\Services\ValidadorPersonaService;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class RegistroClienteController extends Controller
{
    protected ValidadorPersonaService $validadorPersona;

    public function __construct(ValidadorPersonaService $validadorPersona)
    {
        $this->validadorPersona = $validadorPersona;
    }

    public function showRegistro()
    {
        return view('portal.registro');
    }

    public function registrar(Request $request)
    {
        $validated = $request->validate([
            'tipo_documento' => 'required|string|in:DNI,PASAPORTE,CARNET_EXTRANJERIA',
            'numero_documento' => 'required|string|max:30',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:120',
            'telefono' => 'required|string|max:30',
            'correo' => 'required|email|max:120',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $usuario = DB::transaction(function () use ($validated) {
                // 1. Validar reglas de persona (DNI peruano 8 dígitos)
                $this->validadorPersona->validar([
                    'tipo_documento' => $validated['tipo_documento'],
                    'numero_documento' => $validated['numero_documento'],
                    'nombres' => $validated['nombres'],
                    'apellidos' => $validated['apellidos'],
                    'telefono' => $validated['telefono'],
                    'correo' => $validated['correo'],
                ]);

                // 2. Crear o reutilizar Persona por documento
                $persona = Persona::firstOrCreate(
                    [
                        'tipo_documento' => $validated['tipo_documento'],
                        'numero_documento' => $validated['numero_documento'],
                    ],
                    [
                        'nombres' => $validated['nombres'],
                        'apellidos' => $validated['apellidos'],
                        'telefono' => $validated['telefono'],
                        'correo' => $validated['correo'],
                        'activo' => 1,
                    ]
                );

                // 3. Crear registro en Cliente si no existe
                Cliente::firstOrCreate(
                    ['id_persona' => $persona->id_persona],
                    [
                        'fecha_registro' => now(),
                        'tipo_cliente' => 'DIRECTO',
                        'activo' => 1,
                    ]
                );

                // 4. Crear cuenta de usuario con rol CLIENTE (id_rol = 4)
                $nombreUsuario = trim($validated['correo']);
                // Si el correo ya estuviera como usuario, usar el DNI
                if (Usuario::where('nombre_usuario', $nombreUsuario)->exists()) {
                    $nombreUsuario = $validated['numero_documento'];
                }

                if (Usuario::where('nombre_usuario', $nombreUsuario)->exists()) {
                    throw new Exception("Ya existe una cuenta registrada con este correo o documento. Inicia sesión directamente.");
                }

                $usuario = Usuario::create([
                    'id_persona' => $persona->id_persona,
                    'id_rol' => 4, // ROL CLIENTE
                    'nombre_usuario' => $nombreUsuario,
                    'password_hash' => Hash::make($validated['password']),
                    'activo' => 1,
                    'fecha_creacion' => now(),
                ]);

                return $usuario;
            });

            // Iniciar sesión automáticamente
            Auth::login($usuario);

            return redirect()->route('portal.dashboard')
                ->with('success', "¡Bienvenido a 18 Grados Sur, {$validated['nombres']}! Tu cuenta ha sido creada exitosamente.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
