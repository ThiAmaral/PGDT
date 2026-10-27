@extends('layouts.app')

@section('titulo', 'Nova demanda')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="mb-2">
                <a href="{{ route('demandas.index') }}" class="small">&larr; Minhas demandas</a>
            </div>
            <h1 class="h3 mb-1">Nova demanda</h1>
            <p class="text-muted mb-3">
                Descreva o apoio tecnológico de que {{ $escola?->nomeCompleto() ?? 'sua escola ou instituição' }} precisa.
                A coordenação do projeto analisa a demanda e você acompanha o andamento em "Minhas demandas".
            </p>

            @if ($errors->any())
                <div class="alert alert-danger">Confira os campos destacados abaixo.</div>
            @endif

            <form method="POST" action="{{ route('demandas.store') }}" enctype="multipart/form-data" class="card shadow-sm" novalidate data-enviando="Enviando...">
                @csrf

                <div class="card-body">
                    <div class="mb-3">
                        <label for="projeto_base_id" class="form-label">Projeto de extensão <span class="text-danger">*</span></label>
                        <select id="projeto_base_id" name="projeto_base_id" class="form-select @error('projeto_base_id') is-invalid @enderror" required data-descricoes-projeto="#descricao-projeto">
                            <option value="">Escolha o projeto...</option>
                            @foreach ($projetos as $projeto)
                                <option value="{{ $projeto->id }}" data-descricao="{{ $projeto->descricao }}" @selected((string) old('projeto_base_id', $projetoEscolhido) === (string) $projeto->id)>{{ $projeto->titulo }}</option>
                            @endforeach
                        </select>
                        <div id="descricao-projeto" class="form-text"></div>
                        <div class="form-text">Se nenhum projeto combinar com a sua necessidade, escolha "{{ \App\Models\ProjetoBase::DEMANDAS_GERAIS }}". <a href="{{ route('projetos-base.index') }}" target="_blank">Ver os projetos</a></div>
                        @error('projeto_base_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                        <input id="titulo" type="text" name="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo') }}" required maxlength="100" data-contador="#contador-titulo">
                        <div class="form-text d-flex justify-content-between">
                            <span>Resuma a demanda em uma frase. Ex.: "Laboratório de informática sem acesso à internet".</span>
                            <span id="contador-titulo" class="text-nowrap ms-2"></span>
                        </div>
                        @error('titulo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                        <textarea id="descricao" name="descricao" rows="5" class="form-control @error('descricao') is-invalid @enderror" required maxlength="5000">{{ old('descricao') }}</textarea>
                        <div class="form-text">O que está acontecendo, desde quando e o que já foi tentado.</div>
                        @error('descricao')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="justificativa" class="form-label">Justificativa <span class="text-danger">*</span></label>
                        <textarea id="justificativa" name="justificativa" rows="3" class="form-control @error('justificativa') is-invalid @enderror" required maxlength="5000">{{ old('justificativa') }}</textarea>
                        <div class="form-text">Por que a demanda é importante e o que melhora quando for atendida.</div>
                        @error('justificativa')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="publico_afetado" class="form-label">Público afetado <span class="text-danger">*</span></label>
                        <input id="publico_afetado" type="text" name="publico_afetado" class="form-control @error('publico_afetado') is-invalid @enderror" value="{{ old('publico_afetado') }}" required maxlength="255">
                        <div class="form-text">Quem é atendido e quantas pessoas, se souber. Ex.: "120 alunos do 6º ao 9º ano e 8 professores".</div>
                        @error('publico_afetado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-1">
                        <span class="form-label d-block">Anexos</span>
                        <div class="zona-upload rounded border border-2 p-4 text-center @if ($errors->has('anexos') || $errors->has('anexos.*')) border-danger @endif"
                             data-upload
                             data-extensoes="{{ implode(',', \App\Models\Anexo::EXTENSOES) }}"
                             data-tamanho-maximo="{{ \App\Models\Anexo::tamanhoMaximoMb() * 1024 * 1024 }}"
                             data-quantidade-maxima="{{ \App\Models\Anexo::quantidadeMaxima() }}">
                            <input id="anexos" type="file" name="anexos[]" class="d-none" multiple accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                            <p class="mb-2">Arraste os arquivos para cá ou</p>
                            <label for="anexos" class="btn btn-outline-primary btn-sm mb-2">Escolher arquivos</label>
                            <p class="small text-muted mb-0">PDF, JPG ou PNG · até {{ \App\Models\Anexo::tamanhoMaximoMb() }} MB cada · no máximo {{ \App\Models\Anexo::quantidadeMaxima() }} arquivos. Opcional.</p>
                        </div>
                        <ul class="list-group mt-2" data-upload-lista></ul>
                        <div class="small text-danger mt-1" data-upload-erro></div>
                        @error('anexos')
                            <div class="small text-danger mt-1">{{ $message }}</div>
                        @enderror
                        @foreach ($errors->get('anexos.*') as $mensagens)
                            @foreach ($mensagens as $mensagem)
                                <div class="small text-danger mt-1">{{ $mensagem }}</div>
                            @endforeach
                        @endforeach
                        @if ($errors->any() && ! $errors->has('anexos') && ! $errors->has('anexos.*'))
                            <div class="small text-muted mt-1">Por segurança, o navegador não guarda os arquivos depois de um erro: escolha-os de novo.</div>
                        @endif
                    </div>
                </div>

                <div class="card-footer bg-white d-flex flex-column flex-sm-row justify-content-end gap-2">
                    <a href="{{ route('demandas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Enviar demanda</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
