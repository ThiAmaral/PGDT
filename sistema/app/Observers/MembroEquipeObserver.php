<?php

namespace App\Observers;

use App\Enums\TipoHistorico;
use App\Models\MembroEquipe;
use App\Models\PapelEquipe;
use Illuminate\Support\Facades\Auth;

/**
 * RF08 e RF11: registra na linha do tempo da demanda a entrada, a troca de papel e a remoção
 * de cada membro da equipe. Guarda os nomes, para a linha do tempo não mudar se um papel for renomeado.
 */
class MembroEquipeObserver
{
    public function created(MembroEquipe $membro): void
    {
        $this->registrar($membro, "{$membro->estudante->nome} entrou na equipe como {$membro->papel->nome}", [
            'acao' => 'entrada',
            'papel' => $membro->papel->nome,
        ]);
    }

    public function updated(MembroEquipe $membro): void
    {
        if ($membro->wasChanged('papel_id')) {
            $anterior = PapelEquipe::find($membro->getRawOriginal('papel_id'))?->nome;
            $novo = $membro->papel()->first()->nome;

            $this->registrar($membro, "Papel de {$membro->estudante->nome} alterado de {$anterior} para {$novo}", [
                'acao' => 'papel',
                'de' => $anterior,
                'para' => $novo,
            ]);
        }

        if ($membro->wasChanged('removido_em') && $membro->foiRemovido()) {
            $this->registrar($membro, "{$membro->estudante->nome} foi removido(a) da equipe", ['acao' => 'remocao']);
        }
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function registrar(MembroEquipe $membro, string $descricao, array $dados): void
    {
        $membro->subprojeto->solicitacao->historicos()->create([
            'usuario_id' => Auth::id(),
            'tipo' => TipoHistorico::Equipe,
            'descricao' => $descricao,
            'dados' => ['estudante_id' => $membro->estudante_id, ...$dados],
        ]);
    }
}
