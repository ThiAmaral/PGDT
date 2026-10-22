<?php

namespace App\Enums;

/**
 * Ciclo de status da demanda (decisão da Fase 0). Toda mudança é manual.
 */
enum StatusDemanda: string
{
    case Cadastrada = 'cadastrada';
    case EmAnalise = 'em_analise';
    case Priorizada = 'priorizada';
    case EmDesenvolvimento = 'em_desenvolvimento';
    case EmValidacao = 'em_validacao';
    case AguardandoAjustes = 'aguardando_ajustes';
    case Concluida = 'concluida';
    case Recusada = 'recusada';
    case Cancelada = 'cancelada';

    public function rotulo(): string
    {
        return match ($this) {
            self::Cadastrada => 'Cadastrada',
            self::EmAnalise => 'Em Análise',
            self::Priorizada => 'Priorizada',
            self::EmDesenvolvimento => 'Em Desenvolvimento',
            self::EmValidacao => 'Em Validação',
            self::AguardandoAjustes => 'Aguardando Ajustes',
            self::Concluida => 'Concluída',
            self::Recusada => 'Recusada',
            self::Cancelada => 'Cancelada',
        };
    }

    /**
     * Classe de cor do Bootstrap usada no badge do status.
     */
    public function cor(): string
    {
        return match ($this) {
            self::Cadastrada => 'info',
            self::EmAnalise => 'warning',
            self::Priorizada => 'primary',
            self::EmDesenvolvimento, self::EmValidacao => 'primary',
            self::AguardandoAjustes => 'warning',
            self::Concluida => 'success',
            self::Recusada => 'danger',
            self::Cancelada => 'secondary',
        };
    }

    /**
     * Status que exigem um motivo ao serem aplicados.
     */
    public function exigeMotivo(): bool
    {
        return in_array($this, [self::Recusada, self::Cancelada], true);
    }

    /**
     * Status em que a triagem (categoria, prioridade e complexidade) ainda pode ser alterada.
     */
    public function permiteTriagem(): bool
    {
        return in_array($this, [self::Cadastrada, self::EmAnalise, self::Priorizada], true);
    }

    /**
     * O demandante só cancela antes de "Em Desenvolvimento".
     */
    public function demandantePodeCancelar(): bool
    {
        return in_array($this, [self::Cadastrada, self::EmAnalise, self::Priorizada], true);
    }

    /**
     * Próximos status que o coordenador e o admin podem aplicar nesta fase (RF07).
     * Os status de execução e validação entram nas Fases 5 e 6.
     *
     * @return list<self>
     */
    public function proximosDaTriagem(): array
    {
        return match ($this) {
            self::Cadastrada => [self::EmAnalise, self::Recusada],
            self::EmAnalise => [self::Priorizada, self::Recusada],
            self::Priorizada => [self::EmAnalise, self::Recusada],
            self::Recusada => [self::EmAnalise],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->rotulo()])->all();
    }
}
