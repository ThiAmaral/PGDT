<?php

namespace App\Observers;

use App\Enums\Complexidade;
use App\Enums\StatusDemanda;
use App\Enums\TipoHistorico;
use App\Models\CategoriaDemanda;
use App\Models\Prioridade;
use App\Models\Solicitacao;
use Illuminate\Support\Facades\Auth;

/**
 * RF08: registra na linha do tempo a abertura, cada mudança de status e cada classificação,
 * sem depender de o controller lembrar de gravar.
 */
class SolicitacaoObserver
{
    public function created(Solicitacao $solicitacao): void
    {
        $solicitacao->historicos()->create([
            'usuario_id' => Auth::id() ?? $solicitacao->demandante_id,
            'tipo' => TipoHistorico::Status,
            'descricao' => 'Demanda aberta',
            'dados' => ['de' => null, 'para' => $solicitacao->status->value],
        ]);
    }

    public function updated(Solicitacao $solicitacao): void
    {
        if ($solicitacao->wasChanged('status')) {
            $this->registrarStatus($solicitacao);
        }

        if ($solicitacao->wasChanged(['categoria_id', 'prioridade_id', 'complexidade'])) {
            $this->registrarTriagem($solicitacao);
        }
    }

    private function registrarStatus(Solicitacao $solicitacao): void
    {
        $anterior = StatusDemanda::from($solicitacao->getRawOriginal('status'));
        $novo = $solicitacao->status;

        $solicitacao->historicos()->create([
            'usuario_id' => Auth::id(),
            'tipo' => TipoHistorico::Status,
            'descricao' => "Status alterado de \"{$anterior->rotulo()}\" para \"{$novo->rotulo()}\"",
            'dados' => array_filter([
                'de' => $anterior->value,
                'para' => $novo->value,
                'motivo' => $novo->exigeMotivo() ? $solicitacao->motivo_status : null,
            ], fn ($valor) => $valor !== null),
        ]);
    }

    /**
     * Guarda os nomes (e não só os ids) para a linha do tempo não mudar se o admin renomear um item depois.
     */
    private function registrarTriagem(Solicitacao $solicitacao): void
    {
        $campos = [
            'categoria' => ['categoria_id', fn ($id) => CategoriaDemanda::find($id)?->nome],
            'prioridade' => ['prioridade_id', fn ($id) => Prioridade::find($id)?->nome],
            'complexidade' => ['complexidade', fn ($valor) => $valor ? Complexidade::from($valor)->rotulo() : null],
        ];

        $primeiraClassificacao = collect($campos)->every(fn ($campo) => $solicitacao->getRawOriginal($campo[0]) === null);
        $dados = [];

        foreach ($campos as $nome => [$coluna, $rotulo]) {
            if ($primeiraClassificacao || $solicitacao->wasChanged($coluna)) {
                $dados[$nome] = [
                    'de' => $primeiraClassificacao ? null : $rotulo($solicitacao->getRawOriginal($coluna)),
                    'para' => $rotulo($solicitacao->getAttributes()[$coluna] ?? null),
                ];
            }
        }

        $solicitacao->historicos()->create([
            'usuario_id' => Auth::id(),
            'tipo' => TipoHistorico::Triagem,
            'descricao' => $primeiraClassificacao ? 'Demanda classificada' : 'Classificação revista',
            'dados' => $dados,
        ]);
    }
}
