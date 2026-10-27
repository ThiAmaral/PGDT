<?php

namespace App\Enums;

/**
 * Situação da entrega no fluxo de validação em duas etapas (RF13).
 */
enum SituacaoEntrega: string
{
    case AguardandoTecnica = 'aguardando_tecnica';
    case Devolvida = 'devolvida';
    case AguardandoAceite = 'aguardando_aceite';
    case Pendencia = 'pendencia';
    case Aceita = 'aceita';

    public function rotulo(): string
    {
        return match ($this) {
            self::AguardandoTecnica => 'Aguardando aprovação técnica',
            self::Devolvida => 'Devolvida para ajustes',
            self::AguardandoAceite => 'Aguardando aceite',
            self::Pendencia => 'Pendência reportada',
            self::Aceita => 'Solução aceita',
        };
    }

    /**
     * Classe de cor do Bootstrap usada no badge da entrega.
     */
    public function cor(): string
    {
        return match ($this) {
            self::AguardandoTecnica, self::AguardandoAceite => 'warning',
            self::Devolvida, self::Pendencia => 'danger',
            self::Aceita => 'success',
        };
    }

    /**
     * Entrega ainda em avaliação: a equipe espera a decisão antes de enviar outra.
     */
    public function emAvaliacao(): bool
    {
        return in_array($this, [self::AguardandoTecnica, self::AguardandoAceite], true);
    }

    /**
     * Devolvida pelo coordenador ou com pendência do demandante: a equipe precisa ajustar.
     */
    public function pedeAjustes(): bool
    {
        return in_array($this, [self::Devolvida, self::Pendencia], true);
    }

    /**
     * O demandante só vê a entrega depois da aprovação técnica.
     */
    public function passouPelaEtapaTecnica(): bool
    {
        return in_array($this, [self::AguardandoAceite, self::Pendencia, self::Aceita], true);
    }
}
