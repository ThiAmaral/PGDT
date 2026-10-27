<?php

namespace App\Enums;

/**
 * Tipos de entrega da equipe (RF12). Cada tipo pede campos diferentes no formulário.
 */
enum TipoEntrega: string
{
    case Link = 'link';
    case Relatorio = 'relatorio';
    case Arquivos = 'arquivos';

    public function rotulo(): string
    {
        return match ($this) {
            self::Link => 'Link ou repositório',
            self::Relatorio => 'Relatório técnico',
            self::Arquivos => 'Arquivos ou fotos',
        };
    }

    /**
     * Extensões aceitas para os arquivos deste tipo (decisão da Fase 0: PDF, DOCX, JPG, PNG e ZIP;
     * planilhas do Excel incluídas pelo autor na revisão da Fase 6).
     *
     * @return list<string>
     */
    public function extensoes(): array
    {
        return match ($this) {
            self::Relatorio => ['pdf', 'docx', 'xlsx', 'xls'],
            default => ['pdf', 'docx', 'xlsx', 'xls', 'jpg', 'jpeg', 'png', 'zip'],
        };
    }

    /**
     * Tipos aceitos por extenso, para as mensagens e a dica do formulário.
     */
    public function rotuloDosArquivos(): string
    {
        return match ($this) {
            self::Relatorio => 'PDF, DOCX ou Excel (XLSX ou XLS)',
            default => 'PDF, DOCX, Excel (XLSX ou XLS), JPG, PNG ou ZIP',
        };
    }

    /**
     * O link é o conteúdo da entrega; os outros tipos precisam de pelo menos um arquivo.
     */
    public function exigeArquivo(): bool
    {
        return $this !== self::Link;
    }

    public function exigeUrl(): bool
    {
        return $this === self::Link;
    }
}
