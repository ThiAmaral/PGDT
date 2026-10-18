<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * RF01: cadastro acadêmico. Só e-mails institucionais de estudante da UFES.
 */
class CadastroEstudanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'matricula' => preg_replace('/\D/', '', (string) $this->input('matricula')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'ends_with:@'.config('pgdt.dominio_estudante'), 'unique:usuarios,email'],
            'matricula' => ['required', 'digits_between:6,20', 'unique:estudantes,matricula'],
            'curso_id' => ['required', Rule::exists('cursos', 'id')->where('ativo', true)],
            'telefone' => ['nullable', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.ends_with' => 'Use o seu e-mail institucional de estudante (@'.config('pgdt.dominio_estudante').').',
            'email.unique' => 'Já existe uma conta com este e-mail. Use "Esqueci minha senha" se não lembrar a senha.',
            'matricula.unique' => 'Esta matrícula já está cadastrada.',
            'telefone.regex' => 'Informe o telefone no formato (28) 99999-9999.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'curso_id' => 'curso',
            'password' => 'senha',
        ];
    }
}
