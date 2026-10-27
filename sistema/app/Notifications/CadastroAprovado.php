<?php

namespace App\Notifications;

use App\Models\Parametro;
use App\Models\Usuario;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CadastroAprovado extends Notification
{
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
            ->subject('Seu cadastro foi aprovado')
            ->greeting("Olá, {$notifiable->nome}!")
            ->line("Seu cadastro como {$notifiable->perfil->rotulo()} na ".config('app.name').' foi aprovado.')
            ->action('Entrar no sistema', route('login'))
            ->salutation(Parametro::valor('nome_rede'));
    }
}
