@extends('layouts.app')

@section('titulo', $config['titulo'])

@section('content')
<div class="container">
    <h1 class="h3 mb-3">Cadastros de apoio</h1>

    <ul class="nav nav-tabs mb-3">
        @foreach ($tipos as $chave => $item)
            <li class="nav-item">
                <a class="nav-link @if ($chave === $tipo) active @endif" href="{{ route('admin.dominios.index', $chave) }}">{{ $item['titulo'] }}</a>
            </li>
        @endforeach
    </ul>

    <p class="text-muted">{{ $config['descricao'] }} Itens em uso não são apagados: desmarque "Ativo" para que não apareçam mais nas listas.</p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.dominios.store', $tipo) }}" class="row g-2 align-items-end" novalidate>
                @csrf
                <input type="hidden" name="novo" value="1">
                <div class="{{ $config['ordem'] ? 'col-md-6' : 'col-md-9' }}">
                    <label for="nome" class="form-label">{{ $config['feminino'] ? 'Nova' : 'Novo' }} {{ $config['singular'] }}</label>
                    <input id="nome" type="text" name="nome" class="form-control @error('nome') is-invalid @enderror" value="{{ old('novo') ? old('nome') : '' }}" required maxlength="100">
                    @error('nome')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @if ($config['ordem'])
                    <div class="col-md-3">
                        <label for="ordem" class="form-label">Ordem</label>
                        <input id="ordem" type="number" name="ordem" min="0" max="999" class="form-control @error('ordem') is-invalid @enderror" value="{{ old('novo') ? old('ordem') : ($itens->max('ordem') ?? 0) + 10 }}" required>
                        @error('ordem')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Incluir</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <ul class="list-group list-group-flush">
            @forelse ($itens as $item)
                <li class="list-group-item">
                    <form method="POST" action="{{ route('admin.dominios.update', [$tipo, $item->id]) }}" class="row g-2 align-items-center">
                        @csrf
                        @method('PATCH')
                        <div class="{{ $config['ordem'] ? 'col-12 col-md-5' : 'col-12 col-md-8' }}">
                            <label for="nome-{{ $item->id }}" class="visually-hidden">Nome</label>
                            <input id="nome-{{ $item->id }}" type="text" name="nome" class="form-control form-control-sm" value="{{ $item->nome }}" required maxlength="100">
                        </div>
                        @if ($config['ordem'])
                            <div class="col-6 col-md-3">
                                <div class="input-group input-group-sm">
                                    <label for="ordem-{{ $item->id }}" class="input-group-text">Ordem</label>
                                    <input id="ordem-{{ $item->id }}" type="number" name="ordem" min="0" max="999" class="form-control" value="{{ $item->ordem }}" required>
                                </div>
                            </div>
                        @endif
                        <div class="col-auto">
                            <div class="form-check form-switch mb-0">
                                <input id="ativo-{{ $item->id }}" type="checkbox" name="ativo" value="1" class="form-check-input" role="switch" @checked($item->ativo)>
                                <label for="ativo-{{ $item->id }}" class="form-check-label small">Ativo</label>
                            </div>
                        </div>
                        <div class="col-auto ms-auto">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                        </div>
                        @if ($errors->hasBag("item{$item->id}"))
                            <div class="col-12 small text-danger">{{ $errors->getBag("item{$item->id}")->first() }}</div>
                        @endif
                    </form>
                </li>
            @empty
                <li class="list-group-item text-center text-muted py-4">Nenhum item cadastrado.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
