@extends('layouts.app')

@section('titulo', $projeto->titulo)

@section('content')
<div class="container">
    <div class="mb-2">
        <a href="{{ route('publico.index') }}" class="small">&larr; Projetos de extensão</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <span class="badge text-bg-light border mb-2">{{ $projeto->tipologia->nome }}</span>
                    <h1 class="h3">{{ $projeto->titulo }}</h1>
                    <p class="mb-0" style="white-space: pre-line">{{ $projeto->descricao }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="small text-muted">Coordenação</dt>
                        <dd>
                            {{ $projeto->coordenador?->nome ?? 'A definir' }}
                            @if ($projeto->coordenador?->coordenador?->departamento)
                                <div class="small text-muted">{{ $projeto->coordenador->coordenador->departamento }}</div>
                            @endif
                        </dd>
                        <dt class="small text-muted">Período</dt>
                        <dd class="mb-0">{{ $projeto->periodo() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h5 mb-2">Sub-projetos com vagas abertas</h2>
    <div class="row g-3">
        @forelse ($subprojetos as $subprojeto)
            <div class="col-md-6 col-xl-4">
                @include('oportunidades._card', ['publico' => true])
            </div>
        @empty
            <div class="col-12">
                <div class="card card-body text-center text-muted py-4">Este projeto não tem vagas abertas no momento.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection
