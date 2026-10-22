@extends('layouts.app')

@php($editando = $projeto->exists)

@section('titulo', $editando ? 'Editar Projeto Base' : 'Novo Projeto Base')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h1 class="h3 mb-3">{{ $editando ? 'Editar Projeto Base' : 'Novo Projeto Base' }}</h1>

            <form method="POST" action="{{ $editando ? route('projetos-base.update', $projeto) : route('projetos-base.store') }}" class="card shadow-sm" novalidate>
                @csrf
                @if ($editando)
                    @method('PUT')
                @endif

                <div class="card-body">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                        <input id="titulo" type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo', $projeto->titulo) }}" required maxlength="150" autofocus>
                        @error('titulo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                        <textarea id="descricao" name="descricao" rows="4" class="form-control @error('descricao') is-invalid @enderror" required maxlength="5000">{{ old('descricao', $projeto->descricao) }}</textarea>
                        <div class="form-text">Aparece para as escolas na escolha do projeto e na página pública.</div>
                        @error('descricao')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="escopo_preliminar" class="form-label">Escopo preliminar</label>
                        <textarea id="escopo_preliminar" name="escopo_preliminar" rows="3" class="form-control @error('escopo_preliminar') is-invalid @enderror" maxlength="5000">{{ old('escopo_preliminar', $projeto->escopo_preliminar) }}</textarea>
                        <div class="form-text">Que tipo de demanda este projeto atende.</div>
                        @error('escopo_preliminar')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6 col-md-4">
                            <label for="data_inicio" class="form-label">Data de início <span class="text-danger">*</span></label>
                            <input id="data_inicio" type="date" name="data_inicio" class="form-control @error('data_inicio') is-invalid @enderror" value="{{ old('data_inicio', $projeto->data_inicio?->format('Y-m-d')) }}" required>
                            @error('data_inicio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <label for="data_fim" class="form-label">Data de término</label>
                            <input id="data_fim" type="date" name="data_fim" class="form-control @error('data_fim') is-invalid @enderror" value="{{ old('data_fim', $projeto->data_fim?->format('Y-m-d')) }}">
                            <div class="form-text">Deixe em branco se não houver previsão.</div>
                            @error('data_fim')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="tipologia_id" class="form-label">Tipologia <span class="text-danger">*</span></label>
                            <select id="tipologia_id" name="tipologia_id" class="form-select @error('tipologia_id') is-invalid @enderror" required>
                                <option value="">Selecione...</option>
                                @foreach ($tipologias as $tipologia)
                                    <option value="{{ $tipologia->id }}" @selected((string) old('tipologia_id', $projeto->tipologia_id) === (string) $tipologia->id)>{{ $tipologia->nome }}</option>
                                @endforeach
                            </select>
                            @error('tipologia_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="coordenador_id" class="form-label">Coordenador responsável @if ($escolheCoordenador)<span class="text-danger">*</span>@endif</label>
                        @if ($escolheCoordenador)
                            <select id="coordenador_id" name="coordenador_id" class="form-select @error('coordenador_id') is-invalid @enderror" required>
                                <option value="">Selecione...</option>
                                @foreach ($coordenadores as $coordenador)
                                    <option value="{{ $coordenador->id }}" @selected((string) old('coordenador_id', $projeto->coordenador_id) === (string) $coordenador->id)>{{ $coordenador->nome }} ({{ $coordenador->email }})</option>
                                @endforeach
                            </select>
                            @if ($coordenadores->isEmpty())
                                <div class="form-text text-warning-emphasis">
                                    Nenhum coordenador ativo. Envie um <a href="{{ route('admin.convites.index') }}">convite</a> primeiro.
                                </div>
                            @endif
                            @error('coordenador_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @else
                            <input id="coordenador_id" type="text" class="form-control" value="{{ $projeto->coordenador?->nome ?? auth()->user()->nome }}" disabled>
                            <div class="form-text">Quem cadastra o projeto é o coordenador responsável. Só o administrador pode trocar.</div>
                        @endif
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input id="publico" type="checkbox" name="publico" value="1" class="form-check-input" role="switch" @checked($errors->any() ? old('publico') : $projeto->publico)>
                        <label for="publico" class="form-check-label">Mostrar na página pública de projetos</label>
                    </div>
                    <div class="form-check form-switch">
                        <input id="ativo" type="checkbox" name="ativo" value="1" class="form-check-input" role="switch" @checked($errors->any() ? old('ativo') : $projeto->ativo)>
                        <label for="ativo" class="form-check-label">Ativo (recebe novas demandas)</label>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <a href="{{ $editando ? route('projetos-base.show', $projeto) : route('projetos-base.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">{{ $editando ? 'Salvar alterações' : 'Cadastrar projeto' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
