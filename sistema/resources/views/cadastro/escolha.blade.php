@extends('layouts.app')

@section('titulo', 'Criar conta')

@section('content')
<div class="container">
    <div class="text-center mb-4">
        <h1 class="h3">Criar conta</h1>
        <p class="text-muted">Escolha a opção que descreve você.</p>
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-md-6 col-lg-5">
            <a href="{{ route('cadastro.estudante') }}" class="card h-100 shadow-sm text-decoration-none text-body cartao-escolha">
                <div class="card-body p-4 text-center">
                    <div class="display-5 mb-3" aria-hidden="true">🎓</div>
                    <h2 class="h4">Sou estudante da UFES</h2>
                    <p class="text-muted mb-4">Quero participar dos projetos de extensão e atender às demandas das escolas.</p>
                    <p class="small text-muted mb-4">Você vai precisar do seu e-mail <strong>@edu.ufes.br</strong> e da sua matrícula.</p>
                    <span class="btn btn-primary">Cadastrar como estudante</span>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-5">
            <a href="{{ route('cadastro.demandante') }}" class="card h-100 shadow-sm text-decoration-none text-body cartao-escolha">
                <div class="card-body p-4 text-center">
                    <div class="display-5 mb-3" aria-hidden="true">🏫</div>
                    <h2 class="h4">Represento uma escola ou instituição</h2>
                    <p class="text-muted mb-4">Quero solicitar apoio tecnológico para a minha escola ou instituição.</p>
                    <p class="small text-muted mb-4">Você vai precisar dos dados da escola e do seu cargo.</p>
                    <span class="btn btn-primary">Cadastrar como demandante</span>
                </div>
            </a>
        </div>
    </div>

    <p class="text-center text-muted small mt-4">
        É professor(a) e vai coordenar um projeto de extensão? O acesso é feito por convite do administrador.<br>
        Já tem conta? <a href="{{ route('login') }}">Entrar</a>
    </p>
</div>
@endsection
