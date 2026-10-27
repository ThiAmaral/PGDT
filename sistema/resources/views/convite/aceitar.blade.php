@extends('layouts.app')

@section('titulo', 'Aceitar convite')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Cadastro de Coordenador projeto de extensão</h1>
                    <p class="text-muted small mb-4">
                        Convite para <strong>{{ $convite->email }}</strong>, válido até {{ $convite->expira_em->format('d/m/Y H:i') }}.
                    </p>

                    <form method="POST" action="{{ route('convite.aceitar', $token) }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome completo</label>
                            <input id="nome" type="text" class="form-control @error('nome') is-invalid @enderror" name="nome" value="{{ old('nome') }}" required maxlength="150" autocomplete="name" autofocus>
                            @error('nome')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input id="email" type="email" class="form-control" value="{{ $convite->email }}" disabled>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="siape" class="form-label">SIAPE <span class="text-muted">(opcional)</span></label>
                                <input id="siape" type="text" inputmode="numeric" class="form-control @error('siape') is-invalid @enderror" name="siape" value="{{ old('siape') }}" maxlength="20">
                                @error('siape')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-8">
                                <label for="departamento" class="form-label">Departamento <span class="text-muted">(opcional)</span></label>
                                <input id="departamento" type="text" class="form-control @error('departamento') is-invalid @enderror" name="departamento" value="{{ old('departamento') }}" maxlength="150">
                                @error('departamento')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="telefone" class="form-label">Telefone <span class="text-muted">(opcional)</span></label>
                            <input id="telefone" type="tel" class="form-control @error('telefone') is-invalid @enderror" name="telefone" value="{{ old('telefone') }}" data-mascara="telefone" placeholder="(28) 99999-9999" autocomplete="tel">
                            @error('telefone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            @include('cadastro._senha')
                        </div>

                        <p class="small text-muted">Ao concluir o cadastro, você confirma que leu o <a href="{{ route('privacidade') }}" target="_blank" rel="noopener">aviso de privacidade</a>.</p>

                        <button type="submit" class="btn btn-primary w-100">Concluir cadastro</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
