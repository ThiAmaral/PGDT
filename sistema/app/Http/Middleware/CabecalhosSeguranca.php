<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança em todas as respostas (Fase 8): impedem que as telas sejam abertas
 * dentro de outro site, que o navegador adivinhe o tipo dos arquivos baixados e que o endereço
 * completo das páginas vá para sites externos.
 */
class CabecalhosSeguranca
{
    public function handle(Request $request, Closure $next): Response
    {
        $resposta = $next($request);

        $resposta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $resposta->headers->set('X-Content-Type-Options', 'nosniff');
        $resposta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $resposta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Em HTTPS, o navegador passa a usar só HTTPS neste endereço.
        if ($request->isSecure()) {
            $resposta->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $resposta;
    }
}
