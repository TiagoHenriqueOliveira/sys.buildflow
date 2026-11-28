<?php

namespace App\Http\Requests;

use App\Http\Controllers\AuthController;
use App\Services\MfaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class MinhaContaRequest extends FormRequest
{
    /**
     * Auditoria S-08 (28/11/2026): trocar o e-mail ou a senha exige a senha
     * atual e o código do autenticador — uma sessão sequestrada não vira
     * tomada de conta. Senha atual errada conta no mesmo bloqueio do login
     * (5 erros → 15 min); código errado, no bloqueio do MFA. Só o nome não
     * pede nada.
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $usuario = $this->user();
            $trocaEmail = mb_strtolower(trim((string) $this->input('user_email'))) !== mb_strtolower((string) $usuario->user_email);
            $trocaSenha = filled($this->input('user_senha'));

            if ((! $trocaEmail && ! $trocaSenha) || $validator->errors()->isNotEmpty()) {
                return;
            }

            $chaveErros = 'login.senha.' . sha1(mb_strtolower((string) $usuario->user_email));
            if (RateLimiter::tooManyAttempts($chaveErros, AuthController::MAXIMO_ERROS_SENHA)) {
                $validator->errors()->add('senha_atual', 'Muitas tentativas com senha errada. Tente de novo em '
                    . max(1, (int) ceil(RateLimiter::availableIn($chaveErros) / 60)) . ' minutos.');

                return;
            }

            if (! Hash::check((string) $this->input('senha_atual'), (string) $usuario->user_senha)) {
                RateLimiter::hit($chaveErros, AuthController::BLOQUEIO_SENHA_SEGUNDOS);
                $validator->errors()->add('senha_atual', 'Para trocar o e-mail ou a senha, informe a senha atual correta.');

                return;
            }

            $mfa = app(MfaService::class);
            if (! $mfa->verificar($usuario, (string) $this->input('codigo_mfa'))) {
                $validator->errors()->add('codigo_mfa', $mfa->bloqueado($usuario)
                    ? 'Muitas tentativas com código errado. Tente de novo em ' . $mfa->minutosParaLiberar($usuario) . ' minutos.'
                    : 'Para trocar o e-mail ou a senha, informe o código atual do aplicativo autenticador.');
            }
        }];
    }

    public function rules(): array
    {
        return [
            'user_nome' => ['required', 'string', 'max:50'],
            'user_email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('usuarios', 'user_email')->ignore($this->user()->user_id, 'user_id'),
            ],
            // Auditoria 27/11/2026 (S-05): mesma política do cadastro de usuários.
            'user_senha' => ['nullable', 'string', Password::defaults(), 'max:50', 'confirmed'],
            'senha_atual' => ['nullable', 'string', 'max:50'],
            'codigo_mfa' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_nome.required' => 'O nome é obrigatório.',
            'user_email.required' => 'O e-mail é obrigatório.',
            'user_email.email' => 'Informe um e-mail válido.',
            'user_email.unique' => 'Este e-mail já está cadastrado.',
            'user_senha.min' => 'A senha deve ter no mínimo :min caracteres.',
            'user_senha.password.letters' => 'A senha deve ter ao menos uma letra.',
            'user_senha.password.numbers' => 'A senha deve ter ao menos um número.',
            'user_senha.password.uncompromised' => 'Esta senha aparece em vazamentos de dados conhecidos. Escolha outra.',
            'user_senha.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
