@extends('layouts.app')

@section('titulo', 'Projetos de extensão')

@section('content')
<div class="container">
    <div class="mb-3">
        <h1 class="h3 mb-0">Projetos de extensão</h1>
        <p class="text-muted mb-0">
            Conheça os projetos da Rede de Apoio Tecnológico às Escolas e as vagas abertas para estudantes da UFES.
            Para participar, é preciso entrar com a sua conta de estudante.
        </p>
    </div>

    <h2 class="h5 mt-4 mb-2" id="vagas">Sub-projetos com vagas abertas</h2>

    @include('oportunidades._filtros', ['acao' => route('publico.index').'#vagas'])

    <div class="row g-3">
        @forelse ($subprojetos as $subprojeto)
            <div class="col-md-6 col-xl-4">
                @include('oportunidades._card', ['publico' => true])
            </div>
        @empty
            <div class="col-12">
                <div class="card card-body text-center text-muted py-4">
                    @if (array_filter($filtros))
                        Nenhum sub-projeto encontrado com esses filtros.
                    @else
                        Nenhum sub-projeto com vagas abertas no momento.
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $subprojetos->fragment('vagas')->links() }}
    </div>

    <h2 class="h5 mt-4 mb-2">Projetos de extensão</h2>
    <div class="row g-3">
        @forelse ($projetos as $projeto)
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <h3 class="h6 mb-0">
                                <a href="{{ route('publico.show', $projeto) }}" class="text-body">{{ $projeto->titulo }}</a>
                            </h3>
                            <span class="badge text-bg-light border">{{ $projeto->tipologia->nome }}</span>
                        </div>
                        <p class="small text-muted mb-2">Coordenação: {{ $projeto->coordenador?->nome ?? 'a definir' }} · {{ $projeto->periodo() }}</p>
                        <p class="small mb-3">{{ \Illuminate\Support\Str::limit($projeto->descricao, 220) }}</p>
                        <div class="mt-auto d-flex justify-content-between align-items-center gap-2">
                            <span class="small {{ $projeto->vagas_abertas_count ? 'text-success' : 'text-muted' }}">
                                {{ $projeto->vagas_abertas_count ? $projeto->vagas_abertas_count.' sub-projeto(s) com vagas' : 'Sem vagas abertas agora' }}
                            </span>
                            <a href="{{ route('publico.show', $projeto) }}" class="btn btn-sm btn-outline-primary">Ver projeto</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card card-body text-center text-muted py-4">Nenhum projeto de extensão publicado.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection
