<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Exibe a tela de login
     */
    public function mostrarLogin()
    {
        return view('login.index');
    }

    /**
     * Processa o login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'senha' => ['required', 'string'],
        ], [
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email'    => 'Informe um e-mail válido.',
            'senha.required' => 'O campo senha é obrigatório.',
        ]);

        $usuario = Usuario::where('user_email', $request->email)
            ->where('user_ativo', true)
            ->first();

        if (!$usuario || !Hash::check($request->senha, $usuario->user_senha)) {
            return back()
                ->withErrors(['email' => 'E-mail ou senha inválidos.'])
                ->withInput($request->only('email'));
        }

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    /**
     * Realiza o logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
