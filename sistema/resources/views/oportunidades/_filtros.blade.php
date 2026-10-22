{{-- Busca e filtros comuns à vitrine (RF10) e à página pública (RF14). --}}
<form method="GET" action="{{ $acao }}" class="card card-body shadow-sm mb-3" role="search">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="busca" class="form-label small">Buscar</label>
            <input id="busca" type="search" name="busca" class="form-control" value="{{ $filtros['busca'] ?? '' }}" placeholder="Título ou escopo" maxlength="100">
        </div>
        <div class="col-6 col-md-2">
            <label for="modalidade" class="form-label small">Modalidade</label>
            <select id="modalidade" name="modalidade" class="form-select">
                <option value="">Todas</option>
                @foreach ($modalidades as $modalidade)
                    <option value="{{ $modalidade->value }}" @selected(($filtros['modalidade'] ?? '') === $modalidade->value)>{{ $modalidade->rotulo() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label for="projeto" class="form-label small">Projeto de extensão</label>
            <select id="projeto" name="projeto" class="form-select">
                <option value="">Todos</option>
                @foreach ($projetos as $projeto)
                    <option value="{{ $projeto->id }}" @selected((string) ($filtros['projeto'] ?? '') === (string) $projeto->id)>{{ $projeto->titulo }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
            <a href="{{ $acao }}" class="btn btn-outline-secondary" title="Limpar filtros">Limpar</a>
        </div>
    </div>
</form>
