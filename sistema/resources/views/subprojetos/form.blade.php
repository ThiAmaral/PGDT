@extends('layouts.app')

@php($editando = $subprojeto->exists)

@section('titulo', $editando ? 'Editar sub-projeto' : 'Criar sub-projeto')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="mb-2">
                @if ($editando)
                    <a href="{{ route('subprojetos.show', $subprojeto) }}" class="small">&larr; {{ $subprojeto->titulo }}</a>
                @else
                    <a href="{{ route('demandas.show', $solicitacao) }}" class="small">&larr; {{ $solicitacao->titulo }}</a>
                @endif
            </div>

            <h1 class="h3 mb-1">{{ $editando ? 'Editar sub-projeto' : 'Criar sub-projeto' }}</h1>
            <p class="text-muted small mb-3">
                Demanda "{{ $solicitacao->titulo }}" · Projeto de extensão: {{ $solicitacao->projetoBase->titulo }}
            </p>

            @unless ($editaDados)
                <div class="alert alert-secondary small">
                    A demanda está {{ mb_strtolower($solicitacao->status->rotulo()) }}: só a carga horária estimada pode ser corrigida.
                    Cada correção fica registrada na linha do tempo.
                </div>
            @endunless

            <form method="POST" action="{{ $editando ? route('subprojetos.update', $subprojeto) : route('subprojetos.store', $solicitacao) }}" class="card shadow-sm" novalidate>
                @csrf
                @if ($editando)
                    @method('PUT')
                @endif

                <div class="card-body">
                    @if ($editaDados)
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                            <input id="titulo" type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo', $subprojeto->titulo) }}" required maxlength="150">
                            @error('titulo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="escopo" class="form-label">Escopo <span class="text-danger">*</span></label>
                            <textarea id="escopo" name="escopo" rows="6" class="form-control @error('escopo') is-invalid @enderror" required maxlength="5000">{{ old('escopo', $subprojeto->escopo) }}</textarea>
                            <div class="form-text">
                                O que a equipe vai fazer. Aparece na vitrine e na página pública: não inclua telefones, e-mails ou outros dados pessoais.
                                @unless ($editando) Veio preenchido com a descrição da demanda; revise antes de salvar. @endunless
                            </div>
                            @error('escopo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6 col-md-3">
                                <label for="data_inicio" class="form-label">Início <span class="text-danger">*</span></label>
                                <input id="data_inicio" type="date" name="data_inicio" class="form-control @error('data_inicio') is-invalid @enderror" value="{{ old('data_inicio', $subprojeto->data_inicio?->format('Y-m-d')) }}" required>
                                @error('data_inicio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="data_fim" class="form-label">Término <span class="text-danger">*</span></label>
                                <input id="data_fim" type="date" name="data_fim" class="form-control @error('data_fim') is-invalid @enderror" value="{{ old('data_fim', $subprojeto->data_fim?->format('Y-m-d')) }}" required>
                                @error('data_fim')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="vagas" class="form-label">Vagas <span class="text-danger">*</span></label>
                                <input id="vagas" type="number" name="vagas" min="1" max="{{ \App\Http\Controllers\SubprojetoController::VAGAS_MAXIMAS }}" class="form-control @error('vagas') is-invalid @enderror" value="{{ old('vagas', $subprojeto->vagas) }}" required>
                                <div class="form-text">Estudantes na equipe.</div>
                                @error('vagas')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <label for="carga_horaria_estimada" class="form-label">Carga horária <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <input id="carga_horaria_estimada" type="number" name="carga_horaria_estimada" min="1" max="{{ \App\Http\Controllers\SubprojetoController::CARGA_HORARIA_MAXIMA }}" class="form-control @error('carga_horaria_estimada') is-invalid @enderror" value="{{ old('carga_horaria_estimada', $subprojeto->carga_horaria_estimada) }}" required>
                                    <span class="input-group-text">h</span>
                                    @error('carga_horaria_estimada')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">Estimada, por estudante.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="form-label d-block">Modalidade <span class="text-danger">*</span></span>
                            <div class="btn-group" role="group" aria-label="Modalidade">
                                @foreach ($modalidades as $modalidade)
                                    <input type="radio" class="btn-check" name="modalidade" id="modalidade-{{ $modalidade->value }}" value="{{ $modalidade->value }}" autocomplete="off" @checked(old('modalidade', $subprojeto->modalidade?->value) === $modalidade->value)>
                                    <label class="btn btn-outline-secondary" for="modalidade-{{ $modalidade->value }}">{{ $modalidade->rotulo() }}</label>
                                @endforeach
                            </div>
                            @error('modalidade')
                                <div class="small text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($editando)
                            <div class="form-check mb-1">
                                <input type="hidden" name="candidaturas_abertas" value="0">
                                <input id="candidaturas_abertas" type="checkbox" name="candidaturas_abertas" value="1" class="form-check-input" @checked(old('candidaturas_abertas', $subprojeto->candidaturas_abertas))>
                                <label for="candidaturas_abertas" class="form-check-label">Receber candidaturas</label>
                            </div>
                            <div class="form-text mb-0">Desmarque para tirar o sub-projeto da vitrine. Com a equipe completa, as inscrições fecham sozinhas.</div>
                        @endif
                    @else
                        <div class="col-sm-6 col-md-4">
                            <label for="carga_horaria_estimada" class="form-label">Carga horária estimada <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <input id="carga_horaria_estimada" type="number" name="carga_horaria_estimada" min="1" max="{{ \App\Http\Controllers\SubprojetoController::CARGA_HORARIA_MAXIMA }}" class="form-control @error('carga_horaria_estimada') is-invalid @enderror" value="{{ old('carga_horaria_estimada', $subprojeto->carga_horaria_estimada) }}" required>
                                <span class="input-group-text">h</span>
                                @error('carga_horaria_estimada')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif
                </div>

                <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-end gap-2">
                    <a href="{{ $editando ? route('subprojetos.show', $subprojeto) : route('demandas.show', $solicitacao) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">{{ $editando ? 'Salvar alterações' : 'Criar sub-projeto' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
