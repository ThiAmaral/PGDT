<?php

namespace App\Http\Controllers;

use App\Enums\DecisaoValidacao;
use App\Enums\EtapaValidacao;
use App\Enums\TipoEntrega;
use App\Models\Entrega;
use App\Models\EntregaArquivo;
use App\Models\Subprojeto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * RF12: envio das entregas pela equipe. RF13: validação técnica (coordenador) e aceite (demandante).
 * As decisões não mudam o status da demanda: a tela sugere o próximo status (decisão da Fase 0).
 */
class EntregaController extends Controller
{
    public function create(Request $request, Subprojeto $subprojeto): View|RedirectResponse
    {
        $impedimento = $subprojeto->impedimentoDeEntrega($request->user());

        if ($impedimento !== null) {
            Gate::authorize('view', $subprojeto);

            return redirect()->route('subprojetos.show', $subprojeto)->withErrors(['entrega' => $impedimento]);
        }

        return view('entregas.create', [
            'subprojeto' => $subprojeto->load('solicitacao'),
            'tipos' => TipoEntrega::cases(),
            // Entregas devolvidas ou com pendência, para quem envia saber o que ajustar.
            'ajustes' => $subprojeto->entregas()->with('validacoes.usuario')->get()->filter(fn (Entrega $entrega) => $entrega->situacao->pedeAjustes()),
            'emNomeDaEquipe' => ! $subprojeto->ehMembroAtivo($request->user()),
            'proximoNumero' => $subprojeto->proximoNumeroDeEntrega(),
        ]);
    }

    public function store(Request $request, Subprojeto $subprojeto): RedirectResponse
    {
        Gate::authorize('create', [Entrega::class, $subprojeto]);

        $tipo = TipoEntrega::tryFrom((string) $request->input('tipo'));

        // Cada arquivo aparece pelo nome nas mensagens de erro.
        $nomesDosArquivos = collect($request->file('arquivos', []))
            ->mapWithKeys(fn ($arquivo, $indice) => ["arquivos.{$indice}" => $arquivo instanceof UploadedFile
                ? "arquivo \"{$arquivo->getClientOriginalName()}\""
                : 'arquivo'])
            ->all();

        $extensoes = $tipo?->extensoes() ?? TipoEntrega::Arquivos->extensoes();

        $dados = $request->validate([
            'tipo' => ['required', Rule::enum(TipoEntrega::class)],
            'titulo' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'url' => [Rule::requiredIf($tipo?->exigeUrl() ?? false), 'nullable', 'url:http,https', 'max:500'],
            'arquivos' => [Rule::requiredIf($tipo?->exigeArquivo() ?? false), 'array', 'max:'.Entrega::QUANTIDADE_MAXIMA],
            'arquivos.*' => ['file', 'mimes:'.implode(',', $extensoes), 'max:'.Entrega::TAMANHO_MAXIMO_KB],
        ], [
            'url.required' => 'Informe o endereço do link ou do repositório.',
            'url.url' => 'Informe um endereço válido, começando com http:// ou https://.',
            'arquivos.required' => $tipo === TipoEntrega::Relatorio ? 'Envie o relatório em '.$tipo->rotuloDosArquivos().'.' : 'Envie pelo menos um arquivo.',
            'arquivos.max' => 'Envie no máximo :max arquivos.',
            'arquivos.*.mimes' => 'O :attribute não é '.($tipo ?? TipoEntrega::Arquivos)->rotuloDosArquivos().'.',
            'arquivos.*.max' => 'O :attribute tem mais de 20 MB.',
            'arquivos.*.uploaded' => 'O :attribute não pôde ser enviado. Verifique se ele tem até 20 MB.',
        ], [
            ...$nomesDosArquivos,
            'titulo' => 'título',
            'descricao' => 'descrição',
        ]);

        $usuario = $request->user();
        // O link só é guardado na entrega do tipo link; os arquivos, nos outros tipos.
        $arquivos = $tipo->exigeArquivo() ? $request->file('arquivos', []) : [];
        $gravados = [];

        try {
            $entrega = DB::transaction(function () use ($subprojeto, $dados, $tipo, $usuario, $arquivos, &$gravados): Entrega {
                // Trava o sub-projeto: dois envios ao mesmo tempo não recebem o mesmo número.
                Subprojeto::query()->lockForUpdate()->findOrFail($subprojeto->id);

                $entrega = $subprojeto->entregas()->create([
                    'numero' => $subprojeto->proximoNumeroDeEntrega(),
                    'enviado_por' => $usuario->id,
                    'tipo' => $tipo,
                    'titulo' => $dados['titulo'],
                    'descricao' => $dados['descricao'] ?? null,
                    'url' => $tipo->exigeUrl() ? $dados['url'] : null,
                ]);

                foreach ($arquivos as $arquivo) {
                    $gravados[] = $caminho = $arquivo->store("entregas/{$entrega->id}", 'local');
                    $entrega->arquivos()->create([
                        'nome_original' => mb_substr($arquivo->getClientOriginalName(), 0, 255),
                        'caminho' => $caminho,
                        'mime' => $arquivo->getMimeType() ?? 'application/octet-stream',
                        'tamanho_bytes' => $arquivo->getSize(),
                    ]);
                }

                return $entrega;
            });
        } catch (Throwable $erro) {
            // A entrega não foi gravada: apaga os arquivos que já tinham sido salvos.
            Storage::disk('local')->delete($gravados);

            throw $erro;
        }

        return redirect()->route('subprojetos.entregas.show', [$subprojeto, $entrega])
            ->with('sucesso', $subprojeto->ehMembroAtivo($usuario)
                ? "{$entrega->rotulo()} enviada. A coordenação vai fazer a validação técnica e você acompanha a decisão por aqui."
                : "{$entrega->rotulo()} registrada em nome da equipe. Agora faça a validação técnica.");
    }

    public function show(Request $request, Subprojeto $subprojeto, Entrega $entrega): View
    {
        Gate::authorize('view', $entrega);

        $entrega->load(['remetente', 'arquivos', 'validacoes.usuario', 'subprojeto.solicitacao']);

        return view('entregas.show', [
            'subprojeto' => $subprojeto,
            'entrega' => $entrega,
            'decisoesTecnicas' => DecisaoValidacao::daEtapa(EtapaValidacao::Tecnica),
            'decisoesAceite' => DecisaoValidacao::daEtapa(EtapaValidacao::Aceite),
        ]);
    }

    /**
     * Download autorizado: o arquivo fica fora da pasta pública e só sai por aqui.
     */
    public function baixarArquivo(Subprojeto $subprojeto, Entrega $entrega, EntregaArquivo $arquivo): StreamedResponse
    {
        Gate::authorize('view', $entrega);
        abort_unless(Storage::disk('local')->exists($arquivo->caminho), 404, 'Arquivo não encontrado.');

        return Storage::disk('local')->download($arquivo->caminho, $arquivo->nome_original);
    }

    /**
     * RF13 etapa 1: "Aprovar Tecnicamente" ou "Devolver para Ajustes".
     */
    public function validar(Request $request, Subprojeto $subprojeto, Entrega $entrega): RedirectResponse
    {
        Gate::authorize('validarTecnicamente', $entrega);

        $decisao = $this->decidir($request, $entrega, EtapaValidacao::Tecnica);

        return redirect()->route('subprojetos.entregas.show', [$subprojeto, $entrega])->with('sucesso', match ($decisao) {
            DecisaoValidacao::Aprovada => 'Entrega aprovada tecnicamente. Agora o demandante aceita a solução. Se a demanda ainda não está "Em Validação", mude o status.',
            default => 'Entrega devolvida para a equipe. Se for o caso, mude a demanda para "Aguardando Ajustes".',
        });
    }

    /**
     * RF13 etapa 2: "Aceitar Solução" ou "Reportar Pendência".
     */
    public function aceitar(Request $request, Subprojeto $subprojeto, Entrega $entrega): RedirectResponse
    {
        Gate::authorize('aceitar', $entrega);

        $decisao = $this->decidir($request, $entrega, EtapaValidacao::Aceite);
        $ehDemandante = $subprojeto->solicitacao->demandante_id === $request->user()->id;

        return redirect()->route('subprojetos.entregas.show', [$subprojeto, $entrega])->with('sucesso', match (true) {
            $decisao === DecisaoValidacao::Aceita && $ehDemandante => 'Solução aceita. Obrigado! A coordenação vai concluir a demanda.',
            $decisao === DecisaoValidacao::Aceita => 'Aceite registrado em nome do demandante. Se for o caso, mude a demanda para "Concluída".',
            $ehDemandante => 'Pendência enviada à equipe. Você acompanha os ajustes por aqui e pelo chat da demanda.',
            default => 'Pendência registrada em nome do demandante e enviada à equipe. Se for o caso, mude a demanda para "Aguardando Ajustes".',
        });
    }

    private function decidir(Request $request, Entrega $entrega, EtapaValidacao $etapa): DecisaoValidacao
    {
        $opcoes = DecisaoValidacao::daEtapa($etapa);

        $dados = $request->validate([
            'decisao' => ['required', Rule::in(array_map(fn (DecisaoValidacao $decisao) => $decisao->value, $opcoes))],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ], [
            'decisao.in' => 'Escolha uma das decisões da tela.',
        ]);

        $decisao = DecisaoValidacao::from($dados['decisao']);
        $feedback = trim((string) ($dados['feedback'] ?? '')) ?: null;

        if ($decisao->exigeFeedback() && $feedback === null) {
            throw ValidationException::withMessages(['feedback' => $etapa === EtapaValidacao::Tecnica
                ? 'Explique à equipe o que precisa ser ajustado.'
                : 'Descreva a pendência para a equipe.']);
        }

        $entrega->registrarValidacao($decisao, $feedback, $request->user());

        return $decisao;
    }
}
