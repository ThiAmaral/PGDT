<?php

namespace App\Http\Requests;

use App\Enums\TipoEscola;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * RF02: cadastro institucional. O demandante escolhe a escola na lista
 * ou digita uma nova, que o admin confere na aprovação.
 */
class CadastroDemandanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'nova_escola_uf' => mb_strtoupper(trim((string) $this->input('nova_escola_uf'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:usuarios,email'],
            'telefone' => ['required', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'],
            'cargo' => ['required', 'string', 'max:100'],
            'escola_modo' => ['required', Rule::in(['lista', 'nova'])],
            'escola_instituicao_id' => ['required_if:escola_modo,lista', 'nullable', Rule::exists('escolas_instituicoes', 'id')->where('ativo', true)],
            'nova_escola_nome' => ['required_if:escola_modo,nova', 'nullable', 'string', 'max:200'],
            'nova_escola_tipo' => ['required_if:escola_modo,nova', 'nullable', Rule::enum(TipoEscola::class)],
            'nova_escola_cidade' => ['required_if:escola_modo,nova', 'nullable', 'string', 'max:100'],
            'nova_escola_uf' => ['required_if:escola_modo,nova', 'nullable', 'string', 'size:2', 'alpha'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Já existe uma conta com este e-mail. Use "Esqueci minha senha" se não lembrar a senha.',
            'telefone.regex' => 'Informe o telefone no formato (28) 99999-9999.',
            'escola_instituicao_id.required_if' => 'Escolha a escola ou instituição na lista, ou clique em "Não encontrei minha escola".',
            'nova_escola_nome.required_if' => 'Informe o nome da escola ou instituição.',
            'nova_escola_tipo.required_if' => 'Informe o tipo da escola ou instituição.',
            'nova_escola_cidade.required_if' => 'Informe a cidade da escola ou instituição.',
            'nova_escola_uf.required_if' => 'Informe a UF da escola ou instituição.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'escola_instituicao_id' => 'escola ou instituição',
            'nova_escola_nome' => 'nome da escola',
            'nova_escola_tipo' => 'tipo',
            'nova_escola_cidade' => 'cidade',
            'nova_escola_uf' => 'UF',
            'password' => 'senha',
        ];
    }
}
