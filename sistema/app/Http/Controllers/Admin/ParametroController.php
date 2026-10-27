<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Parametro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidadorDeDados;
use Illuminate\View\View;

/**
 * Parâmetros gerais do sistema, editados só pelo admin (Fase 7).
 */
class ParametroController extends Controller
{
    public function index(): View
    {
        return view('admin.parametros.index', [
            'grupos' => collect(Parametro::DEFINICOES)->groupBy('grupo', preserveKeys: true),
            'valores' => collect(Parametro::DEFINICOES)->mapWithKeys(fn ($definicao, $chave) => [$chave => Parametro::valor($chave)]),
            'ultimaAlteracao' => Parametro::query()->with('atualizador')->latest('updated_at')->first(),
            'tamanhoMaximoDoServidor' => Parametro::tamanhoMaximoDoServidorMb(),
            'envioMaximoDoServidor' => Parametro::envioMaximoDoServidorMb(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tamanhoMaximo = Parametro::tamanhoMaximoDoServidorMb();

        $validador = Validator::make($request->all(), [
            'nome_rede' => ['required', 'string', 'max:150'],
            'email_contato' => ['nullable', 'email', 'max:150'],
            'notificacoes_email' => ['nullable', 'boolean'],
            'demanda_anexos_quantidade' => ['required', 'integer', 'min:1', 'max:'.Parametro::LIMITE_QUANTIDADE],
            'demanda_anexos_tamanho_mb' => ['required', 'integer', 'min:1', 'max:'.$tamanhoMaximo],
            'entrega_arquivos_quantidade' => ['required', 'integer', 'min:1', 'max:'.Parametro::LIMITE_QUANTIDADE],
            'entrega_arquivos_tamanho_mb' => ['required', 'integer', 'min:1', 'max:'.$tamanhoMaximo],
        ], [
            'demanda_anexos_tamanho_mb.max' => "O servidor aceita arquivos de até {$tamanhoMaximo} MB.",
            'entrega_arquivos_tamanho_mb.max' => "O servidor aceita arquivos de até {$tamanhoMaximo} MB.",
        ], collect(Parametro::DEFINICOES)->map(fn ($definicao) => mb_strtolower($definicao['rotulo']))->all());

        // Todos os arquivos de um envio precisam caber no limite do servidor (post_max_size).
        $validador->after(function (ValidadorDeDados $validador): void {
            $envioMaximo = Parametro::envioMaximoDoServidorMb();
            $dados = $validador->getData();

            foreach (['demanda_anexos', 'entrega_arquivos'] as $prefixo) {
                $total = (int) ($dados["{$prefixo}_quantidade"] ?? 0) * (int) ($dados["{$prefixo}_tamanho_mb"] ?? 0);

                if ($total > $envioMaximo && ! $validador->errors()->has("{$prefixo}_tamanho_mb")) {
                    $validador->errors()->add("{$prefixo}_tamanho_mb", "A quantidade vezes o tamanho ({$total} MB) passa do limite de envio do servidor ({$envioMaximo} MB). Diminua um dos dois.");
                }
            }
        });

        $dados = $validador->validate();
        $dados['notificacoes_email'] = $request->boolean('notificacoes_email') ? '1' : '0';
        $dados['email_contato'] = (string) ($dados['email_contato'] ?? '');

        Parametro::salvar(array_map(fn ($valor) => trim((string) $valor), $dados), $request->user());

        return redirect()->route('admin.parametros.index')->with('sucesso', 'Parâmetros salvos.');
    }
}
