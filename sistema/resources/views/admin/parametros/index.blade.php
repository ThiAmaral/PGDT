@extends('layouts.app')

@section('titulo', 'Parâmetros gerais')

@section('content')
<div class="container">
    <h1 class="h3 mb-1">Parâmetros gerais</h1>
    <p class="text-muted small mb-3">
        Valores usados em todo o sistema. As mudanças valem a partir do próximo envio; arquivos já enviados não mudam.
        @if ($ultimaAlteracao)
            Última alteração em {{ $ultimaAlteracao->updated_at->format('d/m/Y H:i') }}@if ($ultimaAlteracao->atualizador), por {{ $ultimaAlteracao->atualizador->nome }}@endif.
        @endif
    </p>

    <form method="POST" action="{{ route('admin.parametros.update') }}" novalidate>
        @csrf
        @method('PATCH')

        @foreach ($grupos as $grupo => $definicoes)
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">{{ $grupo }}</div>
                <div class="card-body row g-3">
                    @foreach ($definicoes as $chave => $definicao)
                        @php($valor = old($chave, $valores[$chave]))
                        @if ($definicao['tipo'] === 'booleano')
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input id="{{ $chave }}" type="checkbox" name="{{ $chave }}" value="1" class="form-check-input" role="switch"
                                           @checked(old() ? old($chave) : $valores[$chave] === '1')>
                                    <label for="{{ $chave }}" class="form-check-label">{{ $definicao['rotulo'] }}</label>
                                </div>
                                <div class="form-text">{{ $definicao['descricao'] }}</div>
                            </div>
                        @else
                            <div class="{{ $definicao['tipo'] === 'inteiro' ? 'col-md-6' : 'col-12' }}">
                                <label for="{{ $chave }}" class="form-label">{{ $definicao['rotulo'] }}</label>
                                <input id="{{ $chave }}" name="{{ $chave }}" value="{{ $valor }}"
                                       class="form-control @error($chave) is-invalid @enderror"
                                       @if ($definicao['tipo'] === 'inteiro')
                                           type="number" min="1"
                                           max="{{ str_ends_with($chave, '_quantidade') ? \App\Models\Parametro::LIMITE_QUANTIDADE : $tamanhoMaximoDoServidor }}" required
                                       @elseif ($definicao['tipo'] === 'email')
                                           type="email" maxlength="150"
                                       @else
                                           type="text" maxlength="150" required
                                       @endif>
                                @error($chave)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">{{ $definicao['descricao'] }}</div>
                            </div>
                        @endif
                    @endforeach
                    @if ($grupo === 'Arquivos')
                        <div class="col-12">
                            <p class="small text-muted mb-0">
                                Limites deste servidor: até {{ $tamanhoMaximoDoServidor }} MB por arquivo e {{ $envioMaximoDoServidor }} MB por envio
                                (quantidade × tamanho). Para aumentar, é preciso mudar o <code>php.ini</code>.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <button type="submit" class="btn btn-primary">Salvar parâmetros</button>
    </form>
</div>
@endsection
