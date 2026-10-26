<?php

namespace App\Modules\Seguridad\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clientes\Services\ValidadorPersonaService;
use App\Modules\Seguridad\Models\Auditoria;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Seguridad\Models\Rol;
use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class UsuarioController extends Controller
{
    protected ValidadorPersonaService $validadorPersona;

    public function __construct(ValidadorPersonaService $validadorPersona)
    {
        $this->validadorPersona = $validadorPersona;
    }

    public function index()
    {
        $usuarios = Usuario::with(['persona', 'rol'])->orderBy('id_usuario', 'desc')->paginate(15);
        $roles = Rol::where('activo', 1)->get();

        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_documento' => 'required|string|in:DNI,PASAPORTE,CARNET_EXTRANJERIA',
            'numero_documento' => 'required|string|max:30',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:120',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:120',
            'id_rol' => 'required|integer|exists:rol,id_rol',
            'nombre_usuario' => 'required|string|max:50|unique:usuario,nombre_usuario',
            'password' => 'required|string|min:6',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // 1. Validar y crear/actualizar Persona
                $this->validadorPersona->validar([
                    'tipo_documento' => $validated['tipo_documento'],
                    'numero_documento' => $validated['numero_documento'],
                    'nombres' => $validated['nombres'],
                    'apellidos' => $validated['apellidos'],
                    'telefono' => $validated['telefono'] ?? null,
                    'correo' => $validated['correo'] ?? null,
                ]);

                $persona = Persona::firstOrCreate(
                    [
                        'tipo_documento' => $validated['tipo_documento'],
                        'numero_documento' => $validated['numero_documento'],
                    ],
                    [
                        'nombres' => $validated['nombres'],
                        'apellidos' => $validated['apellidos'],
                        'telefono' => $validated['telefono'] ?? null,
                        'correo' => $validated['correo'] ?? null,
                        'activo' => 1,
                    ]
                );

                // 2. Crear Usuario
                $usuario = Usuario::create([
                    'id_persona' => $persona->id_persona,
                    'id_rol' => (int) $validated['id_rol'],
                    'nombre_usuario' => trim($validated['nombre_usuario']),
                    'password_hash' => Hash::make($validated['password']),
                    'activo' => 1,
                    'fecha_creacion' => now(),
                ]);

                Auditoria::registrar(
                    Auth::id(),
                    'CREAR_USUARIO',
                    'usuario',
                    $usuario->id_usuario,
                    "Usuario {$usuario->nombre_usuario} creado con rol ID {$usuario->id_rol}."
                );
            });

            return redirect()->route('usuarios.index')
                ->with('success', "Usuario '{$validated['nombre_usuario']}' creado exitosamente.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function toggleEstado($id)
    {
        $idActual = Auth::id();
        if ($idActual == $id) {
            return back()->with('error', "No puede desactivar su propia cuenta de usuario en sesión.");
        }

        try {
            $usuario = Usuario::findOrFail($id);
            $usuario->activo = $usuario->activo ? 0 : 1;
            $usuario->save();

            Auditoria::registrar(
                $idActual,
                $usuario->activo ? 'ACTIVAR_USUARIO' : 'DESACTIVAR_USUARIO',
                'usuario',
                $usuario->id_usuario,
                "Usuario {$usuario->nombre_usuario} estado cambiado a " . ($usuario->activo ? 'ACTIVO' : 'INACTIVO')
            );

            return redirect()->route('usuarios.index')
                ->with('success', "El usuario {$usuario->nombre_usuario} ahora está " . ($usuario->activo ? 'activo' : 'inactivo') . ".");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
