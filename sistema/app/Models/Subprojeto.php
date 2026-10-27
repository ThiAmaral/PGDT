<?php

namespace App\Models;

use App\Enums\Modalidade;
use App\Enums\Perfil;
use App\Enums\SituacaoEntrega;
use App\Enums\StatusCandidatura;
use App\Enums\StatusDemanda;
use App\Enums\StatusUsuario;
use App\Observers\SubprojetoObserver;
use Database\Factories\SubprojetoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;

/**
 * Demanda aceita e executada por uma equipe de estudantes (RF09). O Projeto Base vem da demanda.
 */
#[Fillable([
    'solicitacao_id', 'coordenador_id', 'titulo', 'escopo', 'data_inicio', 'data_fim',
    'carga_horaria_estimada', 'vagas', 'modalidade', 'candidaturas_abertas',
])]
#[ObservedBy(SubprojetoObserver::class)]
class Subprojeto extends Model
{
    /** @use HasFactory<SubprojetoFactory> */
    use HasFactory;

    protected $table = 'subprojetos';

    protected $attributes = [
        'candidaturas_abertas' => true,
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'carga_horaria_estimada' => 'integer',
            'vagas' => 'integer',
            'modalidade' => Modalidade::class,
            'candidaturas_abertas' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Solicitacao, $this>
     */
    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class);
    }

    /**
     * Coordenador responsável: quem criou o sub-projeto.
     *
     * @return BelongsTo<Usuario, $this>
     */
    public function coordenador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'coordenador_id');
    }

    /**
     * @return HasMany<Candidatura, $this>
     */
    public function candidaturas(): HasMany
    {
        return $this->hasMany(Candidatura::class);
    }

    /**
     * Todos os que já passaram pela equipe, inclusive os removidos.
     *
     * @return HasMany<MembroEquipe, $this>
     */
    public function membros(): HasMany
    {
        return $this->hasMany(MembroEquipe::class);
    }

    /**
     * @return HasMany<MembroEquipe, $this>
     */
    public function membrosAtivos(): HasMany
    {
        return $this->membros()->ativos();
    }

    /**
     * Entregas da equipe (RF12), na ordem de envio (nº 1, nº 2...).
     *
     * @return HasMany<Entrega, $this>
     */
    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class)->orderBy('numero');
    }

    /**
     * Sub-projetos que recebem candidaturas (RF10): inscrições abertas, vagas livres, demanda em andamento
     * e Projeto Base ativo.
     *
     * @param  Builder<Subprojeto>  $query
     */
    public function scopeComVagasAbertas(Builder $query): void
    {
        $query
            ->where('candidaturas_abertas', true)
            ->where('vagas', '>', MembroEquipe::query()
                ->selectRaw('count(*)')
                ->whereColumn('membros_equipe.subprojeto_id', 'subprojetos.id')
                ->whereNull('removido_em'))
            ->whereHas('solicitacao', fn (Builder $query) => $query
                ->whereNotIn('status', array_map(fn (StatusDemanda $status) => $status->value, StatusDemanda::semEquipe()))
                ->whereHas('projetoBase', fn (Builder $query) => $query->where('ativo', true)));
    }

    /**
     * Só os sub-projetos de Projetos Base que aparecem na página pública (RF14).
     *
     * @param  Builder<Subprojeto>  $query
     */
    public function scopePublicos(Builder $query): void
    {
        $query->whereHas('solicitacao.projetoBase', fn (Builder $query) => $query->where('publico', true));
    }

    /**
     * Sub-projetos que o usuário acompanha: o admin vê todos; o coordenador, os dos projetos que coordena
     * e os que criou; o estudante, aqueles em que é membro da equipe.
     *
     * @param  Builder<Subprojeto>  $query
     */
    public function scopeVisiveisPara(Builder $query, Usuario $usuario): void
    {
        match ($usuario->perfil) {
            Perfil::Admin => null,
            Perfil::Coordenador => $query->where(fn (Builder $query) => $query
                ->where('coordenador_id', $usuario->id)
                ->orWhereHas('solicitacao.projetoBase', fn (Builder $query) => $query->where('coordenador_id', $usuario->id))),
            Perfil::Estudante => $query->whereHas('membrosAtivos', fn (Builder $query) => $query->where('estudante_id', $usuario->id)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Filtros da vitrine e da página pública: título ou escopo, modalidade e projeto de extensão.
     *
     * @param  Builder<Subprojeto>  $query
     * @param  array{busca?: ?string, modalidade?: ?string, projeto?: ?string}  $filtros
     */
    public function scopeFiltrar(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['busca'] ?? null, fn (Builder $query, string $busca) => $query->where(fn (Builder $query) => $query
                ->where('titulo', 'like', "%{$busca}%")
                ->orWhere('escopo', 'like', "%{$busca}%")))
            ->when($filtros['modalidade'] ?? null, fn (Builder $query, string $modalidade) => $query->where('modalidade', $modalidade))
            ->when($filtros['projeto'] ?? null, fn (Builder $query, string $projeto) => $query->whereHas(
                'solicitacao', fn (Builder $query) => $query->where('projeto_base_id', $projeto)
            ));
    }

    public function vagasLivres(): int
    {
        $ocupadas = $this->membros_ativos_count ?? $this->membrosAtivos()->count();

        return max(0, $this->vagas - $ocupadas);
    }

    /**
     * Inscrições abertas pelo coordenador, vagas livres e demanda ainda em andamento.
     */
    public function recebeCandidaturas(): bool
    {
        return $this->candidaturas_abertas
            && $this->vagasLivres() > 0
            && $this->solicitacao->status->permiteEquipe()
            && $this->solicitacao->projetoBase->ativo;
    }

    /**
     * Por que o usuário não pode se candidatar agora, ou nulo se pode (RF10 e RF14).
     * O texto aparece ao lado do botão desabilitado.
     */
    public function impedimentoDeCandidatura(Usuario $usuario): ?string
    {
        if (! $usuario->temPerfil(Perfil::Estudante)) {
            return 'Só estudantes extensionistas podem se inscrever nos sub-projetos.';
        }

        if ($usuario->status === StatusUsuario::Pendente) {
            return 'Seu cadastro ainda está em análise. A inscrição fica disponível depois que o administrador aprovar a sua conta.';
        }

        if (! $usuario->estaAtivo()) {
            return 'Sua conta não está ativa.';
        }

        $candidatura = $this->candidaturaDe($usuario);

        $motivo = match ($candidatura?->status) {
            StatusCandidatura::Pendente => 'Sua candidatura está aguardando a avaliação da coordenação.',
            StatusCandidatura::Recusada => 'Sua candidatura a este sub-projeto não foi aprovada.',
            StatusCandidatura::Aprovada => $this->ehMembroAtivo($usuario) ? 'Você já faz parte desta equipe.' : 'Você já participou desta equipe.',
            default => null,
        };

        if ($motivo !== null) {
            return $motivo;
        }

        return $this->recebeCandidaturas() ? null : 'As inscrições deste sub-projeto estão encerradas.';
    }

    /**
     * Por que o usuário não pode enviar uma entrega agora, ou nulo se pode (RF12).
     * Enviam os membros da equipe e, em nome dela, quem gerencia a demanda (coordenador e admin).
     * Várias entregas podem estar em avaliação ao mesmo tempo.
     */
    public function impedimentoDeEntrega(Usuario $usuario): ?string
    {
        $ehMembro = $usuario->temPerfil(Perfil::Estudante) && $this->ehMembroAtivo($usuario);

        if (! $ehMembro && ! Gate::forUser($usuario)->allows('gerencia', $this->solicitacao)) {
            return 'Só os membros da equipe e a coordenação enviam entregas.';
        }

        if (! $this->solicitacao->status->permiteEntrega()) {
            return $this->solicitacao->status->encerrada() || $this->solicitacao->status === StatusDemanda::Recusada
                ? 'A demanda está encerrada e não recebe mais entregas.'
                : 'As entregas começam quando a coordenação mudar a demanda para "Em Desenvolvimento".';
        }

        return null;
    }

    /**
     * Próximo número de entrega do sub-projeto. Chamar com o sub-projeto travado (lockForUpdate).
     */
    public function proximoNumeroDeEntrega(): int
    {
        return (int) $this->entregas()->max('numero') + 1;
    }

    public function temEntregaAceita(): bool
    {
        return $this->entregas()->where('situacao', SituacaoEntrega::Aceita)->exists();
    }

    public function candidaturaDe(Usuario $usuario): ?Candidatura
    {
        return $this->candidaturas()->where('estudante_id', $usuario->id)->first();
    }

    public function ehMembroAtivo(Usuario $usuario): bool
    {
        return $this->membrosAtivos()->where('estudante_id', $usuario->id)->exists();
    }

    public function periodo(): string
    {
        return "{$this->data_inicio->format('d/m/Y')} a {$this->data_fim->format('d/m/Y')}";
    }

    /**
     * Total de candidaturas aguardando avaliação nos sub-projetos visíveis ao usuário (painel).
     */
    public static function candidaturasPendentesPara(Usuario $usuario): int
    {
        return Candidatura::query()
            ->where('status', StatusCandidatura::Pendente)
            ->whereHas('subprojeto', fn (Builder $query) => $query->visiveisPara($usuario))
            ->count();
    }
}
