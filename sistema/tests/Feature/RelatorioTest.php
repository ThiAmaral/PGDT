<?php

namespace Tests\Feature;

use App\Enums\StatusDemanda;
use App\Models\MembroEquipe;
use App\Models\PapelEquipe;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RF15: relatório de demandas executadas por extensionista.
 */
class RelatorioTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $coordenador;

    private ProjetoBase $projeto;

    private Usuario $ana;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordenador = Usuario::factory()->coordenador()->create();
        $this->projeto = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id, 'titulo' => 'Robótica nas Escolas']);
        $this->ana = Usuario::factory()->estudante()->create(['nome' => 'Ana Extensionista']);
    }

    private function subprojeto(string $titulo, StatusDemanda $status = StatusDemanda::Concluida, array $atributos = [], ?ProjetoBase $projeto = null): Subprojeto
    {
        $solicitacao = Solicitacao::factory()->triada()->comStatus($status)->create(['projeto_base_id' => ($projeto ?? $this->projeto)->id]);

        return Subprojeto::factory()->create([
            'solicitacao_id' => $solicitacao->id,
            'titulo' => $titulo,
            'data_inicio' => '2026-03-01',
            'data_fim' => '2026-06-30',
            'carga_horaria_estimada' => 40,
            ...$atributos,
        ]);
    }

    private function participar(Subprojeto $subprojeto, Usuario $estudante, string $papel = 'Responsável Técnico'): MembroEquipe
    {
        return MembroEquipe::create([
            'subprojeto_id' => $subprojeto->id,
            'estudante_id' => $estudante->id,
            'papel_id' => PapelEquipe::where('nome', $papel)->firstOrFail()->id,
        ]);
    }

    public function test_estudante_ve_os_sub_projetos_com_datas_carga_horaria_e_total(): void
    {
        $site = $this->subprojeto('Site da escola', atributos: ['carga_horaria_estimada' => 48]);
        $this->participar($site, $this->ana);
        $this->participar($this->subprojeto('Laboratório de informática', atributos: ['data_inicio' => '2026-07-01', 'data_fim' => '2026-09-30']), $this->ana, 'Apoio de Campo');

        $this->actingAs($this->ana)->get('/relatorios/extensionistas')
            ->assertOk()
            ->assertSee('Meu relatório de demandas executadas')
            ->assertSeeInOrder(['Site da escola', 'Robótica nas Escolas', $site->solicitacao->escolaInstituicao->nomeCompleto(), 'Responsável Técnico', '01/03/2026', '30/06/2026', '48 h'])
            ->assertSeeInOrder(['Laboratório de informática', 'Apoio de Campo', '01/07/2026', '30/09/2026', '40 h'])
            ->assertSee('Total: 88 h');
    }

    public function test_estudante_nao_ve_o_relatorio_de_outro_estudante(): void
    {
        $bruno = Usuario::factory()->estudante()->create(['nome' => 'Bruno Colega']);
        $this->participar($this->subprojeto('Sub-projeto do Bruno'), $bruno);

        $this->actingAs($this->ana)->get("/relatorios/extensionistas?estudante={$bruno->id}")
            ->assertOk()
            ->assertDontSee('Sub-projeto do Bruno')
            ->assertDontSee('Bruno Colega');
    }

    public function test_por_padrao_so_demandas_concluidas_e_filtro_de_status(): void
    {
        $this->participar($this->subprojeto('Concluído'), $this->ana);
        $this->participar($this->subprojeto('Em andamento', StatusDemanda::EmDesenvolvimento), $this->ana);

        $this->actingAs($this->ana)->get('/relatorios/extensionistas')
            ->assertSee('Concluído')
            ->assertDontSee('Em andamento</td>', false);

        $this->actingAs($this->ana)->get('/relatorios/extensionistas?status=todos')
            ->assertSee('Concluído')
            ->assertSee('Em andamento');

        $this->actingAs($this->ana)->get('/relatorios/extensionistas?status=em_desenvolvimento')
            ->assertSee('Em andamento')
            ->assertSee('Total: 40 h');
    }

    public function test_filtros_de_periodo_e_projeto(): void
    {
        $outroProjeto = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id, 'titulo' => 'Inclusão Digital']);
        $this->participar($this->subprojeto('Primeiro semestre'), $this->ana);
        $this->participar($this->subprojeto('Segundo semestre', atributos: ['data_inicio' => '2026-08-01', 'data_fim' => '2026-12-15'], projeto: $outroProjeto), $this->ana);

        $this->actingAs($this->ana)->get('/relatorios/extensionistas?de=2026-07-01&ate=2026-12-31')
            ->assertDontSee('Primeiro semestre')
            ->assertSee('Segundo semestre');

        // Só entram os sub-projetos que começaram e terminaram dentro das datas.
        $this->actingAs($this->ana)->get('/relatorios/extensionistas?de=2026-04-01&ate=2026-12-31')
            ->assertDontSee('Primeiro semestre')
            ->assertSee('Segundo semestre');
        $this->actingAs($this->ana)->get('/relatorios/extensionistas?de=2026-01-01&ate=2026-11-30')
            ->assertSee('Primeiro semestre')
            ->assertDontSee('Segundo semestre');

        $this->actingAs($this->ana)->get("/relatorios/extensionistas?projeto={$this->projeto->id}")
            ->assertSee('Primeiro semestre')
            ->assertDontSee('Segundo semestre');

        $this->actingAs($this->ana)->get('/relatorios/extensionistas?de=2026-12-01&ate=2026-01-01')
            ->assertSessionHasErrors('ate');
    }

    public function test_correcao_da_carga_horaria_aparece_no_relatorio(): void
    {
        $subprojeto = $this->subprojeto('Site da escola');
        $this->participar($subprojeto, $this->ana);

        $this->actingAs($this->coordenador)->patch("/subprojetos/{$subprojeto->id}", ['carga_horaria_estimada' => 60])->assertRedirect();

        $this->actingAs($this->ana)->get('/relatorios/extensionistas')->assertSee('Total: 60 h');
    }

    public function test_membro_removido_nao_aparece(): void
    {
        $this->participar($this->subprojeto('Saiu da equipe'), $this->ana)->update(['removido_em' => now()]);

        $this->actingAs($this->ana)->get('/relatorios/extensionistas')
            ->assertDontSee('Saiu da equipe')
            ->assertSee('Nenhum sub-projeto encontrado');
    }

    public function test_coordenador_ve_so_os_estudantes_dos_seus_projetos_e_admin_ve_todos(): void
    {
        $outroCoordenador = Usuario::factory()->coordenador()->create();
        $projetoAlheio = ProjetoBase::factory()->create(['coordenador_id' => $outroCoordenador->id]);
        $bruno = Usuario::factory()->estudante()->create(['nome' => 'Bruno de Outro Projeto']);

        $this->participar($this->subprojeto('Do meu projeto'), $this->ana);
        $this->participar($this->subprojeto('De outro projeto', projeto: $projetoAlheio), $bruno);

        $this->actingAs($this->coordenador)->get('/relatorios/extensionistas')
            ->assertOk()
            ->assertSee('Ana Extensionista')
            ->assertSee('Do meu projeto')
            ->assertDontSee('Bruno de Outro Projeto')
            ->assertDontSee('De outro projeto');

        $this->actingAs($this->coordenador)->get("/relatorios/extensionistas?estudante={$bruno->id}")
            ->assertDontSee('De outro projeto');

        $this->actingAs(Usuario::factory()->admin()->create())->get("/relatorios/extensionistas?estudante={$bruno->id}")
            ->assertSee('De outro projeto')
            ->assertDontSee('Do meu projeto');
    }

    public function test_demandante_nao_acessa_o_relatorio(): void
    {
        $this->actingAs(Usuario::factory()->demandante()->create())->get('/relatorios/extensionistas')->assertForbidden();
        $this->actingAs(Usuario::factory()->demandante()->create())->get('/relatorios/extensionistas/pdf')->assertForbidden();
    }

    public function test_exporta_o_relatorio_em_pdf(): void
    {
        $this->participar($this->subprojeto('Site da escola'), $this->ana);

        $resposta = $this->actingAs($this->ana)->get('/relatorios/extensionistas/pdf');

        $resposta->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $resposta->getContent());
        $this->assertStringContainsString('relatorio-extensionistas-', $resposta->headers->get('content-disposition'));
    }
}
