<?php

namespace Tests\Feature;

use App\Models\Anexo;
use App\Models\Parametro;
use App\Models\ProjetoBase;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 7: parâmetros gerais editados pelo admin.
 */
class ParametroTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function valores(array $alteracoes = []): array
    {
        return [
            'nome_rede' => 'Rede de Apoio Tecnológico às Escolas — UFES/CCENS, Alegre-ES',
            'email_contato' => '',
            'notificacoes_email' => '1',
            'demanda_anexos_quantidade' => '5',
            'demanda_anexos_tamanho_mb' => '5',
            'entrega_arquivos_quantidade' => '5',
            'entrega_arquivos_tamanho_mb' => '20',
            ...$alteracoes,
        ];
    }

    public function test_sem_alteracao_valem_os_valores_padrao(): void
    {
        $this->assertSame(5, Parametro::inteiro('demanda_anexos_quantidade'));
        $this->assertSame(20, Parametro::inteiro('entrega_arquivos_tamanho_mb'));
        $this->assertTrue(Parametro::ativo('notificacoes_email'));
        $this->assertSame('', Parametro::valor('email_contato'));
    }

    public function test_so_o_admin_acessa_os_parametros(): void
    {
        $this->actingAs(Usuario::factory()->admin()->create())->get('/admin/parametros')->assertOk()->assertSee('Parâmetros gerais');

        foreach (['coordenador', 'demandante', 'estudante'] as $perfil) {
            $usuario = Usuario::factory()->{$perfil}()->create();
            $this->actingAs($usuario)->get('/admin/parametros')->assertForbidden();
            $this->actingAs($usuario)->patch('/admin/parametros', $this->valores())->assertForbidden();
        }
    }

    public function test_admin_altera_os_parametros_e_eles_passam_a_valer(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->patch('/admin/parametros', $this->valores([
            'nome_rede' => 'Rede de Apoio — Teste',
            'email_contato' => 'contato@ufes.br',
            'notificacoes_email' => null,
            'demanda_anexos_quantidade' => '3',
        ]))->assertRedirect('/admin/parametros')->assertSessionHas('sucesso');

        $this->assertSame('Rede de Apoio — Teste', Parametro::valor('nome_rede'));
        $this->assertSame('contato@ufes.br', Parametro::valor('email_contato'));
        $this->assertFalse(Parametro::ativo('notificacoes_email'));
        $this->assertSame(3, Anexo::quantidadeMaxima());
        $this->assertSame($admin->id, Parametro::find('nome_rede')->atualizado_por);

        // Só os valores alterados são gravados.
        $this->assertNull(Parametro::find('entrega_arquivos_tamanho_mb'));

        $this->get('/')->assertSee('Rede de Apoio — Teste')->assertSee('contato@ufes.br');
    }

    public function test_valores_invalidos_sao_recusados(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->patch('/admin/parametros', $this->valores([
            'nome_rede' => '',
            'email_contato' => 'nao-e-email',
            'demanda_anexos_quantidade' => '0',
            'entrega_arquivos_quantidade' => (string) (Parametro::LIMITE_QUANTIDADE + 1),
            'entrega_arquivos_tamanho_mb' => (string) (Parametro::tamanhoMaximoDoServidorMb() + 1),
        ]))->assertSessionHasErrors(['nome_rede', 'email_contato', 'demanda_anexos_quantidade', 'entrega_arquivos_quantidade', 'entrega_arquivos_tamanho_mb']);

        $this->assertSame(0, Parametro::count());
    }

    public function test_demanda_respeita_os_limites_de_anexos_definidos_pelo_admin(): void
    {
        Storage::fake('local');
        Parametro::salvar(['demanda_anexos_quantidade' => '1', 'demanda_anexos_tamanho_mb' => '1'], Usuario::factory()->admin()->create());

        $demandante = Usuario::factory()->demandante()->create();
        $projeto = ProjetoBase::factory()->create();
        $dados = [
            'projeto_base_id' => $projeto->id,
            'titulo' => 'Rede caiu',
            'descricao' => 'Sem internet.',
            'justificativa' => 'Aulas paradas.',
            'publico_afetado' => 'Toda a escola',
        ];

        $this->actingAs($demandante)->post('/demandas', [...$dados, 'anexos' => [
            UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('b.pdf', 100, 'application/pdf'),
        ]])->assertSessionHasErrors(['anexos' => 'Envie no máximo 1 arquivos.']);

        $this->actingAs($demandante)->post('/demandas', [...$dados, 'anexos' => [
            UploadedFile::fake()->create('grande.pdf', 2048, 'application/pdf'),
        ]])->assertSessionHasErrors(['anexos.0' => 'O arquivo "grande.pdf" tem mais de 1 MB.']);
    }
}
