<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de demandas executadas por extensionista</title>
    <style>
        @page { margin: 1.5cm 1.5cm 1.8cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #212529; }
        h1 { font-size: 14pt; margin: 0 0 2pt; }
        h2 { font-size: 11pt; margin: 0; }
        .muted { color: #6c757d; }
        .cabecalho { border-bottom: 1px solid #adb5bd; padding-bottom: 6pt; margin-bottom: 10pt; }
        .estudante { margin-bottom: 14pt; page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; margin-top: 4pt; }
        th, td { border: 1px solid #ced4da; padding: 3pt 4pt; text-align: left; vertical-align: top; }
        thead th { background: #e9ecef; }
        .direita { text-align: right; white-space: nowrap; }
        .data { white-space: nowrap; }
        tfoot th { background: #f8f9fa; }
        .rodape { position: fixed; bottom: -1.2cm; left: 0; right: 0; font-size: 7.5pt; color: #6c757d; }
    </style>
</head>
<body>
    <div class="cabecalho">
        <h1>Relatório de demandas executadas por extensionista</h1>
        <div>{{ \App\Models\Parametro::valor('nome_rede') }}</div>
        <div class="muted">
            Status: {{ $filtros['status'] === \App\Http\Controllers\RelatorioController::TODOS_OS_STATUS ? 'todos' : \App\Enums\StatusDemanda::from($filtros['status'])->rotulo() }}
            @if ($projeto) · Projeto: {{ $projeto->titulo }} @endif
            @if ($filtros['de'] ?? null) · De {{ \Illuminate\Support\Carbon::parse($filtros['de'])->format('d/m/Y') }} @endif
            @if ($filtros['ate'] ?? null) · Até {{ \Illuminate\Support\Carbon::parse($filtros['ate'])->format('d/m/Y') }} @endif
        </div>
    </div>

    @forelse ($porEstudante as $secao)
        <div class="estudante">
            <h2>{{ $secao['estudante']->nome }}</h2>
            <div class="muted">
                {{ $secao['estudante']->estudante?->curso?->nome }}
                @if ($secao['estudante']->estudante?->matricula) · Matrícula {{ $secao['estudante']->estudante->matricula }} @endif
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Sub-projeto</th>
                        <th>Projeto de extensão</th>
                        <th>Escola/instituição</th>
                        <th>Papel</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th class="direita">Carga horária</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($secao['participacoes'] as $membro)
                        @php($subprojeto = $membro->subprojeto)
                        <tr>
                            <td>{{ $subprojeto->titulo }}<br><span class="muted">{{ $subprojeto->solicitacao->status->rotulo() }}</span></td>
                            <td>{{ $subprojeto->solicitacao->projetoBase->titulo }}</td>
                            <td>{{ $subprojeto->solicitacao->escolaInstituicao->nomeCompleto() }}</td>
                            <td>{{ $membro->papel->nome }}</td>
                            <td class="data">{{ $subprojeto->data_inicio->format('d/m/Y') }}</td>
                            <td class="data">{{ $subprojeto->data_fim->format('d/m/Y') }}</td>
                            <td class="direita">{{ $subprojeto->carga_horaria_estimada }} h</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="direita">Total</th>
                        <th class="direita">{{ $secao['total'] }} h</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @empty
        <p class="muted">Nenhum sub-projeto encontrado com estes filtros.</p>
    @endforelse

    <div class="rodape">
        Gerado em {{ now()->format('d/m/Y \à\s H:i') }} por {{ $geradoPor->nome }}. Carga horária estimada pela coordenação de cada sub-projeto.
    </div>
</body>
</html>
