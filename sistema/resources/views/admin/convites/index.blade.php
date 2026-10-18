@extends('layouts.app')

@section('titulo', 'Convites')

@section('content')
<div class="container">
    <h1 class="h3 mb-3">Convites para Coordenador projeto de extensão</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.convites.store') }}" class="row g-2 align-items-end" novalidate>
                @csrf
                <div class="col-md-8">
                    <label for="email" class="form-label">E-mail do(a) professor(a)</label>
                    <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required maxlength="150">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Enviar convite</button>
                </div>
            </form>
            <p class="form-text mb-0 mt-2">
                O convite vale por {{ \App\Models\Convite::VALIDADE_DIAS }} dias e pode ser usado uma vez. A conta já nasce ativa.
            </p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>E-mail</th>
                        <th class="d-none d-md-table-cell">Enviado por</th>
                        <th class="d-none d-md-table-cell">Enviado em</th>
                        <th>Situação</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($convites as $convite)
                        @php($situacao = $convite->situacao())
                        <tr>
                            <td>{{ $convite->email }}</td>
                            <td class="d-none d-md-table-cell">{{ $convite->criador->nome }}</td>
                            <td class="d-none d-md-table-cell small">{{ $convite->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge text-bg-{{ ['Válido' => 'primary', 'Usado' => 'success', 'Expirado' => 'secondary'][$situacao] }}">{{ $situacao }}</span>
                                @if ($situacao === 'Válido')
                                    <div class="small text-muted">até {{ $convite->expira_em->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($situacao === 'Válido')
                                    <form method="POST" action="{{ route('admin.convites.cancelar', $convite) }}" data-confirmar="Cancelar o convite para {{ $convite->email }}?">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Nenhum convite enviado ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $convites->links() }}
    </div>
</div>
@endsection
