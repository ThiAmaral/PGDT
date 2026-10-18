<?php

namespace App\Notifications;

use App\Models\Convite;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConviteCoordenador extends Notification
{
    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Convite para Coordenador projeto de extensão')
            ->greeting('Olá!')
            ->line('Você foi convidado(a) para acessar a '.config('app.name').' como Coordenador projeto de extensão.')
            ->line('Clique no botão abaixo para informar seus dados e definir sua senha.')
            ->action('Aceitar convite', route('convite.aceitar', $this->token))
            ->line('O link pode ser usado uma única vez e vale por '.Convite::VALIDADE_DIAS.' dias.')
            ->line('Se você não esperava este convite, ignore este e-mail.')
            ->salutation('Rede de Apoio Tecnológico às Escolas — UFES/CCENS');
    }
}
