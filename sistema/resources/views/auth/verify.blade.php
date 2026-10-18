@extends('layouts.app')

@section('titulo', 'Confirme seu e-mail')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Confirme seu e-mail</h1>

                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            Enviamos um novo link de confirmação para o seu e-mail.
                        </div>
                    @endif

                    <p>
                        Enviamos um link de confirmação para <strong>{{ auth()->user()->email }}</strong>.
                        Abra o e-mail e clique no link para continuar.
                    </p>
                    <p class="text-muted small">
                        Depois da confirmação, o seu cadastro vai para a análise do administrador.
                    </p>

                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        Não recebeu o e-mail?
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">Clique aqui para enviar de novo</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
