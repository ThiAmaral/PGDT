<?php

namespace App\Notifications;

use App\Models\Parametro;
use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as Notificacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/**
 * Aviso por e-mail dos acontecimentos da demanda (Fase 7): nova demanda, mudança de status,
 * mensagem no chat, candidaturas e entregas. Vai pela fila, depois de a transação terminar,
 * para o envio não atrasar nem desfazer a ação de quem clicou.
 */
class Aviso extends Notificacao implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $linhas
     */
    public function __construct(
        public string $assunto,
        public array $linhas,
        public string $botao,
        public string $url,
    ) {
        $this->afterCommit();
    }

    /**
     * Envia o aviso a cada destinatário uma vez, só para contas ativas e sem avisar quem fez a ação.
     * Não envia nada se o admin desligou os avisos nos parâmetros gerais.
     *
     * @param  iterable<Usuario|null>  $destinatarios
     */
    public static function enviar(iterable $destinatarios, self $aviso): void
    {
        if (! Parametro::ativo('notificacoes_email')) {
            return;
        }

        $usuarios = Collection::make($destinatarios)
            ->filter(fn (?Usuario $usuario) => $usuario !== null && $usuario->estaAtivo() && $usuario->id !== Auth::id())
            ->unique('id')
            ->values();

        if ($usuarios->isNotEmpty()) {
            Notification::send($usuarios, $aviso);
        }
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Usuario $notifiable): MailMessage
    {
        $mensagem = (new MailMessage)
            ->subject($this->assunto)
            ->greeting('Olá, '.self::semMarkdown($notifiable->nome).'!');

        foreach ($this->linhas as $linha) {
            $mensagem->line(self::semMarkdown($linha));
        }

        $mensagem->action($this->botao, $this->url);

        if ($contato = Parametro::valor('email_contato')) {
            $mensagem->line('Dúvidas: '.self::semMarkdown($contato).'.');
        }

        return $mensagem
            ->line('Você recebe este aviso porque participa desta demanda na '.config('app.name').'.')
            ->salutation(self::semMarkdown((string) Parametro::valor('nome_rede')));
    }

    /**
     * O e-mail é montado em Markdown: o texto digitado pelos usuários (chat, títulos, motivos)
     * sai como texto, sem virar link, imagem ou formatação. O HTML já é escapado pelo Laravel.
     */
    public static function semMarkdown(string $texto): string
    {
        $texto = preg_replace('/[\\\\`*_\[\]()#!|~]/', '\\\\$0', $texto);

        // Número, hífen ou sinal de mais no começo da linha viraria lista.
        return preg_replace('/^(\d+)([.)])|^([-+])/', '$1\\\\$2$3', $texto);
    }
}
