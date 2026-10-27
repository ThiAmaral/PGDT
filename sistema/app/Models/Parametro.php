<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Parâmetro geral editado pelo admin (Fase 7). A lista abaixo define os parâmetros, o valor padrão
 * e o tipo; a tabela guarda só os valores alterados. Os valores ficam em cache e são relidos ao salvar.
 */
#[Fillable(['chave', 'valor', 'atualizado_por'])]
class Parametro extends Model
{
    public const CREATED_AT = null;

    /** Chave do cache com todos os valores gravados. */
    private const CACHE = 'parametros';

    /** Quantidade máxima de arquivos que o admin pode definir para as demandas e as entregas. */
    public const LIMITE_QUANTIDADE = 10;

    /**
     * @var array<string, array{grupo: string, rotulo: string, tipo: 'texto'|'email'|'booleano'|'inteiro', padrao: string, descricao: string}>
     */
    public const DEFINICOES = [
        'nome_rede' => [
            'grupo' => 'Identificação',
            'rotulo' => 'Nome da rede',
            'tipo' => 'texto',
            'padrao' => 'Rede de Apoio Tecnológico às Escolas — UFES/CCENS, Alegre-ES',
            'descricao' => 'Aparece no rodapé das páginas, na assinatura dos e-mails e no relatório em PDF.',
        ],
        'email_contato' => [
            'grupo' => 'Identificação',
            'rotulo' => 'E-mail de contato',
            'tipo' => 'email',
            'padrao' => '',
            'descricao' => 'Aparece no rodapé e nos e-mails para quem tiver dúvidas. Deixe em branco para não mostrar.',
        ],
        'notificacoes_email' => [
            'grupo' => 'Avisos por e-mail',
            'rotulo' => 'Enviar avisos por e-mail',
            'tipo' => 'booleano',
            'padrao' => '1',
            'descricao' => 'Avisos de nova demanda, mudança de status, mensagem no chat, candidaturas e entregas. Os e-mails de cadastro, convite e recuperação de senha são sempre enviados.',
        ],
        'demanda_anexos_quantidade' => [
            'grupo' => 'Arquivos',
            'rotulo' => 'Anexos por demanda',
            'tipo' => 'inteiro',
            'padrao' => '5',
            'descricao' => 'Quantidade máxima de anexos na abertura da demanda.',
        ],
        'demanda_anexos_tamanho_mb' => [
            'grupo' => 'Arquivos',
            'rotulo' => 'Tamanho de cada anexo da demanda (MB)',
            'tipo' => 'inteiro',
            'padrao' => '5',
            'descricao' => 'Tamanho máximo de cada anexo, em megabytes.',
        ],
        'entrega_arquivos_quantidade' => [
            'grupo' => 'Arquivos',
            'rotulo' => 'Arquivos por entrega',
            'tipo' => 'inteiro',
            'padrao' => '5',
            'descricao' => 'Quantidade máxima de arquivos em cada entrega da equipe.',
        ],
        'entrega_arquivos_tamanho_mb' => [
            'grupo' => 'Arquivos',
            'rotulo' => 'Tamanho de cada arquivo da entrega (MB)',
            'tipo' => 'inteiro',
            'padrao' => '20',
            'descricao' => 'Tamanho máximo de cada arquivo da entrega, em megabytes.',
        ],
    ];

    protected $table = 'parametros';

    protected $primaryKey = 'chave';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function atualizador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'atualizado_por');
    }

    /**
     * Valor atual do parâmetro: o gravado pelo admin ou, se não houver, o padrão.
     */
    public static function valor(string $chave): string
    {
        $gravados = Cache::rememberForever(self::CACHE, fn () => self::query()->pluck('valor', 'chave')->all());

        return (string) ($gravados[$chave] ?? self::DEFINICOES[$chave]['padrao']);
    }

    public static function inteiro(string $chave): int
    {
        return (int) self::valor($chave);
    }

    public static function ativo(string $chave): bool
    {
        return self::valor($chave) === '1';
    }

    /**
     * Grava os valores informados e limpa o cache. Valores iguais ao padrão também são gravados,
     * para a tela mostrar quem alterou por último.
     *
     * @param  array<string, string>  $valores
     */
    public static function salvar(array $valores, Usuario $usuario): void
    {
        foreach ($valores as $chave => $valor) {
            if (self::valor($chave) === $valor) {
                continue;
            }

            self::updateOrCreate(['chave' => $chave], ['valor' => $valor, 'atualizado_por' => $usuario->id]);
        }

        Cache::forget(self::CACHE);
    }

    /**
     * Maior tamanho de arquivo que o servidor aceita (upload_max_filesize do PHP), em MB.
     */
    public static function tamanhoMaximoDoServidorMb(): int
    {
        return max(1, intdiv(self::bytesDoIni('upload_max_filesize'), 1024 * 1024));
    }

    /**
     * Maior envio que o servidor aceita de uma vez (post_max_size do PHP), em MB.
     */
    public static function envioMaximoDoServidorMb(): int
    {
        return max(1, intdiv(self::bytesDoIni('post_max_size'), 1024 * 1024));
    }

    /**
     * Converte valores do php.ini como "50M" ou "2G" em bytes. Zero significa sem limite.
     */
    private static function bytesDoIni(string $diretiva): int
    {
        $valor = trim((string) ini_get($diretiva));
        $numero = (int) $valor;

        $bytes = match (strtoupper(substr($valor, -1))) {
            'G' => $numero * 1024 ** 3,
            'M' => $numero * 1024 ** 2,
            'K' => $numero * 1024,
            default => $numero,
        };

        return $bytes > 0 ? $bytes : PHP_INT_MAX;
    }
}
