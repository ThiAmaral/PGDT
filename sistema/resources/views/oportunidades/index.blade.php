@extends('layouts.app')

@section('titulo', 'Oportunidades')

@section('content')
<div class="container">
    <div class="mb-3">
        <h1 class="h3 mb-0">Oportunidades</h1>
        <p class="text-muted small mb-0">Sub-projetos com vagas abertas para estudantes extensionistas. Escolha um e envie a sua candidatura.</p>
    </div>

    @include('oportunidades._filtros', ['acao' => route('oportunidades.index')])

    <div class="row g-3">
        @forelse ($subprojetos as $subprojeto)
            <div class="col-md-6 col-xl-4">
                @include('oportunidades._card', ['publico' => false])
            </div>
        @empty
            <div class="col-12">
                <div class="card card-body text-center text-muted py-5">
                    @if (array_filter($filtros))
                        Nenhum sub-projeto encontrado com esses filtros.
                    @else
                        Nenhum sub-projeto com vagas abertas no momento. Volte em breve.
                    @endif
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $subprojetos->links() }}
    </div>
</div>
@endsection
