@extends('layouts.app')

@section('titulo', 'Usuários')

@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <h1 class="h3 mb-0">Usuários</h1>
        @if ($totalPendentes > 0)
            <a href="{{ route('admin.usuarios.index', ['status' => 'pendente']) }}" class="btn btn-warning btn-sm">
                {{ $totalPendentes }} {{ $totalPendentes === 1 ? 'cadastro aguardando' : 'cadastros aguardando' }} aprovação
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route('admin.usuarios.index') }}" class="card card-body shadow-sm mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label for="busca" class="form-label small">Nome ou e-mail</label>
                <input id="busca" type="search" name="busca" class="form-control" value="{{ $filtros['busca'] ?? '' }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="perfil" class="form-label small">Perfil</label>
                <select id="perfil" name="perfil" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($perfis as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(($filtros['perfil'] ?? '') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="status" class="form-label small">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($statusOpcoes as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(($filtros['status'] ?? '') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary" title="Limpar filtros">Limpar</a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th class="d-none d-md-table-cell">E-mail</th>
                        <th>Perfil</th>
                        <th>Status</th>
                        <th class="d-none d-lg-table-cell">Cadastro</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $item)
                        <tr>
                            <td>
                                <a href="{{ route('admin.usuarios.show', $item) }}" class="text-body">{{ $item->nome }}</a>
                                <div class="small text-muted d-md-none">{{ $item->email }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">{{ $item->email }}</td>
                            <td>{{ $item->perfil->rotulo() }}</td>
                            <td>
                                <span class="badge text-bg-{{ $item->status->cor() }}">{{ $item->status->rotulo() }}</span>
                                @unless ($item->hasVerifiedEmail())
                                    <div class="small text-muted">e-mail não confirmado</div>
                                @endunless
                            </td>
                            <td class="d-none d-lg-table-cell small">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.usuarios.show', $item) }}" class="btn btn-sm btn-outline-primary">
                                    {{ $item->status === \App\Enums\StatusUsuario::Pendente ? 'Analisar' : 'Detalhes' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum usuário encontrado com esses filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $usuarios->links() }}
    </div>
</div>
@endsection
