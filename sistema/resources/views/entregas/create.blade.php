@extends('layouts.app')

@section('titulo', 'Nova entrega')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="mb-2">
                <a href="{{ route('subprojetos.show', $subprojeto) }}" class="small">&larr; {{ $subprojeto->titulo }}</a>
            </div>

            <h1 class="h3 mb-1">Nova entrega <span class="text-muted fw-normal">(nº {{ $proximoNumero }})</span></h1>
            <p class="text-muted small mb-3">
                A coordenação faz a validação técnica e, depois, o demandante aceita a solução.
                Cada entrega é avaliada separadamente; acompanhe as decisões na tela da entrega e na linha do tempo da demanda.
            </p>

            @if ($emNomeDaEquipe)
                <div class="alert alert-info small">
                    Você está registrando esta entrega <strong>em nome da equipe</strong> (por exemplo, material recebido por e-mail).
                    A linha do tempo vai indicar que ela foi enviada pela coordenação. Depois, faça a validação técnica normalmente.
                </div>
            @endif

            {{-- Devoluções e pendências, para quem envia saber o que ajustar. --}}
            @foreach ($ajustes as $ajuste)
                @if ($feedback = $ajuste->ultimoFeedback())
                    <div class="alert alert-warning">
                        <div class="fw-semibold">{{ $ajuste->rotulo() }} — {{ $ajuste->situacao->rotulo() }}: "{{ $ajuste->titulo }}"</div>
                        <div style="white-space: pre-line">{{ $feedback->feedback }}</div>
                        <div class="small text-muted mt-1">{{ $feedback->usuario->nome }} · {{ $feedback->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                @endif
            @endforeach

            <form method="POST" action="{{ route('subprojetos.entregas.store', $subprojeto) }}" enctype="multipart/form-data" class="card shadow-sm" novalidate data-enviando="Enviando..." data-tipo-entrega>
                @csrf

                <div class="card-body">
                    <div class="mb-3">
                        <span class="form-label d-block">Tipo de entrega <span class="text-danger">*</span></span>
                        <div class="row g-2">
                            @foreach ($tipos as $tipo)
                                @php($dica = $tipo->rotuloDosArquivos().' · até '.\App\Models\Entrega::tamanhoMaximoMb().' MB cada · no máximo '.\App\Models\Entrega::quantidadeMaxima().' arquivos.')
                                <div class="col-sm-4">
                                    <input type="radio" class="btn-check" name="tipo" id="tipo-{{ $tipo->value }}" value="{{ $tipo->value }}" autocomplete="off"
                                           data-exige-arquivo="{{ $tipo->exigeArquivo() ? 1 : 0 }}"
                                           data-extensoes="{{ implode(',', $tipo->extensoes()) }}"
                                           data-rotulo-tipos="{{ $tipo->rotuloDosArquivos() }}"
                                           data-dica="{{ $dica }}"
                                           @checked(old('tipo') === $tipo->value)>
                                    <label class="btn btn-outline-primary w-100 h-100" for="tipo-{{ $tipo->value }}">{{ $tipo->rotulo() }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('tipo')
                            <div class="small text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                        <input id="titulo" type="text" name="titulo" value="{{ old('titulo') }}" maxlength="150" class="form-control @error('titulo') is-invalid @enderror" required placeholder="Ex.: Primeira versão do site">
                        @error('titulo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="4" maxlength="5000" class="form-control @error('descricao') is-invalid @enderror" placeholder="O que foi feito, como testar e o que ficou pendente.">{{ old('descricao') }}</textarea>
                        @error('descricao')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" data-campo-url>
                        <label for="url" class="form-label">Link ou repositório <span class="text-danger">*</span></label>
                        <input id="url" type="url" name="url" value="{{ old('url') }}" maxlength="500" class="form-control @error('url') is-invalid @enderror" placeholder="https://">
                        <div class="form-text">Endereço do site publicado, do repositório (GitHub, GitLab) ou da pasta compartilhada.</div>
                        @error('url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-1" data-campo-arquivos>
                        <span class="form-label d-block">Arquivos <span class="text-danger">*</span></span>
                        <div class="zona-upload rounded border border-2 p-4 text-center @if ($errors->has('arquivos') || $errors->has('arquivos.*')) border-danger @endif"
                             data-upload
                             data-extensoes="{{ implode(',', \App\Enums\TipoEntrega::Arquivos->extensoes()) }}"
                             data-tamanho-maximo="{{ \App\Models\Entrega::tamanhoMaximoMb() * 1024 * 1024 }}"
                             data-quantidade-maxima="{{ \App\Models\Entrega::quantidadeMaxima() }}">
                            <input id="arquivos" type="file" name="arquivos[]" class="d-none" multiple>
                            <p class="mb-2">Arraste os arquivos para cá ou</p>
                            <label for="arquivos" class="btn btn-outline-primary btn-sm mb-2">Escolher arquivos</label>
                            <p class="small text-muted mb-0" data-dica-arquivos></p>
                        </div>
                        <ul class="list-group mt-2" data-upload-lista></ul>
                        <div class="small text-danger mt-1" data-upload-erro></div>
                        @error('arquivos')
                            <div class="small text-danger mt-1">{{ $message }}</div>
                        @enderror
                        @foreach ($errors->get('arquivos.*') as $mensagens)
                            @foreach ($mensagens as $mensagem)
                                <div class="small text-danger mt-1">{{ $mensagem }}</div>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('subprojetos.show', $subprojeto) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Enviar entrega</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
