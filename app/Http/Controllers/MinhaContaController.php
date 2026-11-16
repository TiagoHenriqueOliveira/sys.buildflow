<?php

namespace App\Http\Controllers;

use App\Http\Requests\MinhaContaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Minha conta (2026-11-16): qualquer usuário edita o próprio nome, e-mail e
 * senha — nunca nível de acesso nem status. É também o único jeito do
 * usuário protegido (3iodigital26@gmail.com) se editar, já que ele não
 * aparece na listagem de Usuários.
 */
class MinhaContaController extends Controller
{
    public function edit(): View
    {
        return view('minha-conta.edit', ['usuario' => Auth::user()]);
    }

    public function update(MinhaContaRequest $request): RedirectResponse
    {
        $usuario = Auth::user();
        $dados = $request->validated();

        $payload = [
            'user_nome' => $dados['user_nome'],
            'user_email' => $dados['user_email'],
        ];
        if (! empty($dados['user_senha'])) {
            $payload['user_senha'] = Hash::make($dados['user_senha']);
        }

        $usuario->update($payload);

        return redirect()
            ->route('minha-conta.edit')
            ->with('success', 'Seus dados foram atualizados.');
    }
}
