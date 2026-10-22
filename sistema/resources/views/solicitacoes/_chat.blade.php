<div class="card shadow-sm" id="chat">
    <div class="card-body">
        <h2 class="h6 text-muted">Conversa</h2>
        <p class="small text-muted">
            Mensagens entre o demandante e a coordenação do projeto. Elas não podem ser editadas nem apagadas.
        </p>

        <div class="chat-demanda mb-3" data-chat-rolagem>
            @forelse ($solicitacao->mensagens as $mensagem)
                @php($minha = $mensagem->usuario_id === auth()->id())
                <div class="d-flex mb-2 {{ $minha ? 'justify-content-end' : '' }}">
                    <div class="balao rounded-3 px-3 py-2 {{ $minha ? 'bg-primary-subtle' : 'bg-body-tertiary border' }}">
                        <div class="small mb-1">
                            <strong>{{ $minha ? 'Você' : $mensagem->autor->nome }}</strong>
                            <span class="text-muted">&middot; {{ $mensagem->perfil->rotulo() }}</span>
                        </div>
                        <div class="texto">{{ $mensagem->texto }}</div>
                        <div class="small text-muted text-end mt-1">{{ $mensagem->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">Nenhum comentário ainda.</p>
            @endforelse
        </div>

        @can('comentar', $solicitacao)
            <form method="POST" action="{{ route('demandas.mensagens.store', $solicitacao) }}" data-enviando="Enviando..." novalidate>
                @csrf
                <label for="texto" class="form-label small mb-1">Novo comentário</label>
                <textarea id="texto" name="texto" rows="3" maxlength="{{ \App\Models\Mensagem::TAMANHO_MAXIMO }}" class="form-control @error('texto') is-invalid @enderror" required>{{ old('texto') }}</textarea>
                @error('texto')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="d-flex justify-content-end mt-2">
                    <button type="submit" class="btn btn-primary btn-sm">Enviar Comentário</button>
                </div>
            </form>
        @else
            <p class="small text-muted mb-0">A conversa está encerrada porque a demanda foi {{ mb_strtolower($solicitacao->status->rotulo()) }}.</p>
        @endcan
    </div>
</div>
