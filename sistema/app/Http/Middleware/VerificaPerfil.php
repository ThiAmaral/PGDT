<?php

namespace App\Http\Middleware;

use App\Enums\Perfil;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Libera a rota só para os perfis informados. Uso: `perfil:admin,coordenador` (RNF04).
 */
class VerificaPerfil
{
    public function handle(Request $request, Closure $next, string ...$perfis): Response
    {
        $permitidos = array_map(fn (string $perfil) => Perfil::from($perfil), $perfis);

        abort_unless($request->user()?->temPerfil(...$permitidos), 403, 'Você não tem permissão para acessar esta página.');

        return $next($request);
    }
}
