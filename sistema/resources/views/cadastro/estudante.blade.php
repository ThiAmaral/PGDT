@extends('layouts.app')

@section('titulo', 'Cadastro de Estudante')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Cadastro de Estudante</h1>
                    <p class="small mb-4">
                        Não é o seu caso? <a href="{{ route('register') }}">Trocar tipo de cadastro</a>
                    </p>

                    <form method="POST" action="{{ route('cadastro.estudante') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome completo</label>
                            <input id="nome" type="text" class="form-control @error('nome') is-invalid @enderror" name="nome" value="{{ old('nome') }}" required maxlength="150" autocomplete="name" autofocus>
                            @error('nome')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail institucional</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email" placeholder="nome.sobrenome@edu.ufes.br">
                            <div class="form-text">Só aceitamos e-mails <strong>@edu.ufes.br</strong>. Vamos enviar um link de confirmação.</div>
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                    @if (str_contains($message, '@edu.ufes.br'))
                                        Se você representa uma escola ou instituição, <a href="{{ route('cadastro.demandante') }}">faça o cadastro de demandante</a>.
                                    @endif
                                </div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label for="matricula" class="form-label">Matrícula</label>
                                <input id="matricula" type="text" inputmode="numeric" class="form-control @error('matricula') is-invalid @enderror" name="matricula" value="{{ old('matricula') }}" required maxlength="20">
                                @error('matricula')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-7">
                                <label for="curso_id" class="form-label">Curso</label>
                                <select id="curso_id" name="curso_id" class="form-select @error('curso_id') is-invalid @enderror" required>
                                    <option value="">Selecione o seu curso</option>
                                    @foreach ($cursos as $curso)
                                        <option value="{{ $curso->id }}" @selected(old('curso_id') == $curso->id)>{{ $curso->nome }}</option>
                                    @endforeach
                                </select>
                                @error('curso_id')
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

                        <div class="alert alert-info small">
                            Depois de confirmar o e-mail, o seu cadastro fica <strong>em análise</strong> até o administrador aprovar.
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Criar minha conta de estudante</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
