<?php

namespace App\Notifications;

use App\Models\Usuario;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CadastroRejeitado extends Notification
{
    public function __construct(public string $motivo) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Usuario $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Seu cadastro não foi aprovado')
            ->greeting("Olá, {$notifiable->nome}.")
            ->line('Seu cadastro na '.config('app.name').' não foi aprovado.')
            ->line("Motivo: {$this->motivo}")
            ->line('Em caso de dúvida, procure a coordenação do projeto.')
            ->salutation('Rede de Apoio Tecnológico às Escolas — UFES/CCENS');
    }
}
