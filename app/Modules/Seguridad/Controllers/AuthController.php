<?php

namespace App\Modules\Seguridad\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Seguridad\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $captcha = $this->authService->generateCaptcha();

        return view('auth.login', compact('captcha'));
    }

    public function refreshCaptcha()
    {
        $captcha = $this->authService->generateCaptcha();

        return response()->json(['captcha' => $captcha]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|string',
        ], [
            'usuario.required' => 'Ingrese su nombre de usuario.',
            'password.required' => 'Ingrese su contraseña.',
            'captcha.required' => 'Ingrese el código CAPTCHA.',
        ]);

        // 1. Validar CAPTCHA (CU01)
        if (!$this->authService->validateCaptcha($request->input('captcha'))) {
            return back()
                ->withInput($request->only('usuario'))
                ->withErrors(['captcha' => 'El código CAPTCHA es incorrecto.']);
        }

        // 2. Intentar autenticación
        try {
            $this->authService->authenticate(
                $request->input('usuario'),
                $request->input('password')
            );

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        } catch (\Exception $e) {
            return back()
                ->withInput($request->only('usuario'))
                ->withErrors(['usuario' => $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
