<?php

namespace App\Enums;

/**
 * Decisões das duas etapas de validação (RF13): a técnica, do coordenador, e o aceite, do demandante.
 */
enum DecisaoValidacao: string
{
    case Aprovada = 'aprovada';
    case Devolvida = 'devolvida';
    case Aceita = 'aceita';
    case Pendencia = 'pendencia';

    public function rotulo(): string
    {
        return match ($this) {
            self::Aprovada => 'Aprovada tecnicamente',
            self::Devolvida => 'Devolvida para ajustes',
            self::Aceita => 'Solução aceita',
            self::Pendencia => 'Pendência reportada',
        };
    }

    /**
     * Texto do botão na tela da entrega (RF13).
     */
    public function acao(): string
    {
        return match ($this) {
            self::Aprovada => 'Aprovar Tecnicamente',
            self::Devolvida => 'Devolver para Ajustes',
            self::Aceita => 'Aceitar Solução',
            self::Pendencia => 'Reportar Pendência',
        };
    }

    /**
     * Pergunta de confirmação antes de gravar a decisão.
     */
    public function confirmacao(): string
    {
        return match ($this) {
            self::Aprovada => 'Aprovar tecnicamente esta entrega e enviá-la ao demandante?',
            self::Devolvida => 'Devolver esta entrega para a equipe ajustar?',
            self::Aceita => 'Aceitar a solução? A coordenação poderá concluir a demanda.',
            self::Pendencia => 'Reportar a pendência para a equipe?',
        };
    }

    public function etapa(): EtapaValidacao
    {
        return match ($this) {
            self::Aprovada, self::Devolvida => EtapaValidacao::Tecnica,
            self::Aceita, self::Pendencia => EtapaValidacao::Aceite,
        };
    }

    /**
     * Devolver ou reportar pendência exige explicar o que falta.
     */
    public function exigeFeedback(): bool
    {
        return in_array($this, [self::Devolvida, self::Pendencia], true);
    }

    /**
     * Situação da entrega depois desta decisão.
     */
    public function situacaoResultante(): SituacaoEntrega
    {
        return match ($this) {
            self::Aprovada => SituacaoEntrega::AguardandoAceite,
            self::Devolvida => SituacaoEntrega::Devolvida,
            self::Aceita => SituacaoEntrega::Aceita,
            self::Pendencia => SituacaoEntrega::Pendencia,
        };
    }

    /**
     * @return list<self>
     */
    public static function daEtapa(EtapaValidacao $etapa): array
    {
        return array_values(array_filter(self::cases(), fn (self $decisao) => $decisao->etapa() === $etapa));
    }
}
