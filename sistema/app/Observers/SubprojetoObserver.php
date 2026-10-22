<?php

namespace App\Observers;

use App\Enums\Modalidade;
use App\Enums\TipoHistorico;
use App\Models\Subprojeto;
use Illuminate\Support\Facades\Auth;

/**
 * RF08 e RF09: registra na linha do tempo da demanda a criação do sub-projeto, as alterações
 * dos dados e, em separado, cada mudança da carga horária estimada (valor anterior e novo).
 */
class SubprojetoObserver
{
    /**
     * Campos mostrados na linha do tempo, com o rótulo e a forma de exibir o valor.
     *
     * @return array<string, array{0: string, 1: callable(mixed): ?string}>
     */
    private function campos(): array
    {
        $data = fn ($valor) => $valor ? date('d/m/Y', strtotime((string) $valor)) : null;

        return [
            'titulo' => ['título', fn ($valor) => $valor],
            'escopo' => ['escopo', fn ($valor) => $valor === null ? null : 'alterado'],
            'data_inicio' => ['início', $data],
            'data_fim' => ['fim', $data],
            'vagas' => ['vagas', fn ($valor) => $valor === null ? null : (string) $valor],
            'modalidade' => ['modalidade', fn ($valor) => $valor ? Modalidade::from($valor)->rotulo() : null],
            'candidaturas_abertas' => ['inscrições', fn ($valor) => $valor === null ? null : ($valor ? 'abertas' : 'fechadas')],
        ];
    }

    public function created(Subprojeto $subprojeto): void
    {
        $dados = [];

        foreach ($this->campos() as $coluna => [$rotulo, $formatar]) {
            if (in_array($coluna, ['titulo', 'escopo', 'candidaturas_abertas'], true)) {
                continue;
            }

            $dados[$rotulo] = ['de' => null, 'para' => $formatar($subprojeto->getAttributes()[$coluna] ?? null)];
        }

        $dados['carga horária estimada'] = ['de' => null, 'para' => "{$subprojeto->carga_horaria_estimada} h"];

        $subprojeto->solicitacao->historicos()->create([
            'usuario_id' => Auth::id() ?? $subprojeto->coordenador_id,
            'tipo' => TipoHistorico::Subprojeto,
            'descricao' => 'Sub-projeto criado',
            'dados' => $dados,
        ]);
    }

    public function updated(Subprojeto $subprojeto): void
    {
        if ($subprojeto->wasChanged('carga_horaria_estimada')) {
            $anterior = (int) $subprojeto->getRawOriginal('carga_horaria_estimada');
            $nova = $subprojeto->carga_horaria_estimada;

            $subprojeto->solicitacao->historicos()->create([
                'usuario_id' => Auth::id(),
                'tipo' => TipoHistorico::CargaHoraria,
                'descricao' => "Carga horária estimada alterada de {$anterior} h para {$nova} h",
                'dados' => ['de' => $anterior, 'para' => $nova],
            ]);
        }

        $dados = [];

        foreach ($this->campos() as $coluna => [$rotulo, $formatar]) {
            if (! $subprojeto->wasChanged($coluna)) {
                continue;
            }

            $de = $coluna === 'escopo' ? null : $formatar($subprojeto->getRawOriginal($coluna));
            $para = $formatar($subprojeto->getAttributes()[$coluna] ?? null);

            // Ex.: a mesma data gravada com outro horário não é uma alteração para quem lê.
            if ($de !== $para) {
                $dados[$rotulo] = ['de' => $de, 'para' => $para];
            }
        }

        if ($dados !== []) {
            $subprojeto->solicitacao->historicos()->create([
                'usuario_id' => Auth::id(),
                'tipo' => TipoHistorico::Subprojeto,
                'descricao' => 'Sub-projeto atualizado',
                'dados' => $dados,
            ]);
        }
    }
}
