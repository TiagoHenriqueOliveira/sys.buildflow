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
            'usuario' => ['required', 'string'],
            'senha'   => ['required', 'string'],
        ], [
            'usuario.required' => 'O campo usuário é obrigatório.',
            'senha.required'   => 'O campo senha é obrigatório.',
        ]);

        $usuario = Usuario::where('user_email', strtoupper($request->usuario))
            ->orWhere('user_nome', strtoupper($request->usuario))
            ->where('user_ativo', true)
            ->first();

        if (!$usuario || !Hash::check($request->senha, $usuario->user_senha)) {
            return back()
                ->withErrors(['usuario' => 'Usuário ou senha inválidos.'])
                ->withInput($request->only('usuario'));
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
