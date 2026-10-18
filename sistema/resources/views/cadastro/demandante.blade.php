@extends('layouts.app')

@section('titulo', 'Cadastro de Demandante')

@section('content')
@php($escolaModo = old('escola_modo', $escolas->isEmpty() ? 'nova' : 'lista'))
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Cadastro de Demandante</h1>
                    <p class="text-muted small mb-1">Para quem representa uma escola ou instituição e quer solicitar apoio tecnológico.</p>
                    <p class="small mb-4">
                        Não é o seu caso? <a href="{{ route('register') }}">Trocar tipo de cadastro</a>
                    </p>

                    <form method="POST" action="{{ route('cadastro.demandante') }}" novalidate>
                        @csrf

                        <h2 class="h6 text-uppercase text-muted mb-3">Seus dados</h2>

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome completo</label>
                            <input id="nome" type="text" class="form-control @error('nome') is-invalid @enderror" name="nome" value="{{ old('nome') }}" required maxlength="150" autocomplete="name" autofocus>
                            @error('nome')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label for="email" class="form-label">E-mail</label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email" data-aviso-ufes="#aviso-ufes">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-5">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input id="telefone" type="tel" class="form-control @error('telefone') is-invalid @enderror" name="telefone" value="{{ old('telefone') }}" required data-mascara="telefone" placeholder="(28) 99999-9999" autocomplete="tel">
                                @error('telefone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div id="aviso-ufes" class="alert alert-warning small d-none" role="status">
                            Parece que você é da UFES. Quer se cadastrar como estudante?
                            <a href="{{ route('cadastro.estudante') }}">Ir para o cadastro de estudante</a>.
                            Se você representa uma escola ou instituição, pode continuar aqui.
                        </div>

                        <div class="mb-4">
                            <label for="cargo" class="form-label">Cargo ou função na escola/instituição</label>
                            <input id="cargo" type="text" class="form-control @error('cargo') is-invalid @enderror" name="cargo" value="{{ old('cargo') }}" required maxlength="100" placeholder="Ex.: Diretor(a), Coordenador(a) pedagógico(a)">
                            @error('cargo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <h2 class="h6 text-uppercase text-muted mb-3">Escola ou instituição</h2>

                        <input type="hidden" name="escola_modo" id="escola_modo" value="{{ $escolaModo }}">

                        <div id="escola-lista" class="mb-3 @if ($escolaModo === 'nova') d-none @endif">
                            <label for="escola_instituicao_id" class="form-label">Escolha na lista</label>
                            <select id="escola_instituicao_id" name="escola_instituicao_id" class="form-select @error('escola_instituicao_id') is-invalid @enderror">
                                <option value="">Selecione a escola ou instituição</option>
                                @foreach ($escolas as $escola)
                                    <option value="{{ $escola->id }}" @selected(old('escola_instituicao_id') == $escola->id)>{{ $escola->nomeCompleto() }}</option>
                                @endforeach
                            </select>
                            @error('escola_instituicao_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <button type="button" class="btn btn-link px-0 small" data-escola-modo="nova">Não encontrei minha escola</button>
                        </div>

                        <div id="escola-nova" class="border rounded p-3 mb-3 bg-light @if ($escolaModo === 'lista') d-none @endif">
                            <p class="small text-muted mb-3">Informe os dados da escola ou instituição. O administrador vai conferir junto com o seu cadastro.</p>
                            <div class="mb-3">
                                <label for="nova_escola_nome" class="form-label">Nome da escola ou instituição</label>
                                <input id="nova_escola_nome" type="text" class="form-control @error('nova_escola_nome') is-invalid @enderror" name="nova_escola_nome" value="{{ old('nova_escola_nome') }}" maxlength="200">
                                @error('nova_escola_nome')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="nova_escola_tipo" class="form-label">Tipo</label>
                                    <select id="nova_escola_tipo" name="nova_escola_tipo" class="form-select @error('nova_escola_tipo') is-invalid @enderror">
                                        <option value="">Selecione</option>
                                        @foreach ($tiposEscola as $valor => $rotulo)
                                            <option value="{{ $valor }}" @selected(old('nova_escola_tipo') === $valor)>{{ $rotulo }}</option>
                                        @endforeach
                                    </select>
                                    @error('nova_escola_tipo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-5">
                                    <label for="nova_escola_cidade" class="form-label">Cidade</label>
                                    <input id="nova_escola_cidade" type="text" class="form-control @error('nova_escola_cidade') is-invalid @enderror" name="nova_escola_cidade" value="{{ old('nova_escola_cidade') }}" maxlength="100">
                                    @error('nova_escola_cidade')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="nova_escola_uf" class="form-label">UF</label>
                                    <input id="nova_escola_uf" type="text" class="form-control text-uppercase @error('nova_escola_uf') is-invalid @enderror" name="nova_escola_uf" value="{{ old('nova_escola_uf', 'ES') }}" maxlength="2">
                                    @error('nova_escola_uf')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @if ($escolas->isNotEmpty())
                                <button type="button" class="btn btn-link px-0 small mt-2" data-escola-modo="lista">Voltar para a lista de escolas</button>
                            @endif
                        </div>

                        <h2 class="h6 text-uppercase text-muted mb-3 mt-4">Acesso</h2>

                        <div class="mb-4">
                            @include('cadastro._senha')
                        </div>

                        <div class="alert alert-info small">
                            O seu cadastro fica <strong>em análise</strong> até o administrador aprovar. Você recebe um e-mail com a resposta.
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Criar minha conta de demandante</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
