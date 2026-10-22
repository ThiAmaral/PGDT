<?php

namespace Tests\Feature;

use App\Models\ProjetoBase;
use App\Models\Tipologia;
use App\Models\Usuario;
use Database\Seeders\DominioSeeder;
use Database\Seeders\ProjetoBaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjetoBaseTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $coordenador;

    private Tipologia $tipologia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordenador = Usuario::factory()->coordenador()->create();
        $this->tipologia = Tipologia::factory()->create(['nome' => 'Projeto']);
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosValidos(array $extra = []): array
    {
        return array_merge([
            'titulo' => 'Inclusão Digital nas Escolas',
            'descricao' => 'Apoio tecnológico às escolas da região.',
            'escopo_preliminar' => 'Redes, manutenção e capacitação.',
            'data_inicio' => '2026-11-01',
            'data_fim' => '2027-12-31',
            'tipologia_id' => $this->tipologia->id,
            'publico' => '1',
            'ativo' => '1',
        ], $extra);
    }

    public function test_coordenador_cadastra_projeto_e_vira_o_responsavel(): void
    {
        $this->actingAs($this->coordenador)->get('/projetos-base/create')->assertOk();

        $resposta = $this->actingAs($this->coordenador)->post('/projetos-base', $this->dadosValidos());

        $projeto = ProjetoBase::firstOrFail();
        $resposta->assertRedirect("/projetos-base/{$projeto->id}");
        $this->assertSame($this->coordenador->id, $projeto->coordenador_id);
        $this->assertTrue($projeto->publico);
        $this->assertTrue($projeto->ativo);
    }

    public function test_coordenador_nao_escolhe_outro_coordenador(): void
    {
        $outro = Usuario::factory()->coordenador()->create();

        $this->actingAs($this->coordenador)
            ->post('/projetos-base', $this->dadosValidos(['coordenador_id' => $outro->id]))
            ->assertSessionHasErrors('coordenador_id');

        $this->assertDatabaseCount('projetos_base', 0);
    }

    public function test_projeto_cadastrado_aparece_disponivel_para_selecao(): void
    {
        $this->actingAs($this->coordenador)->post('/projetos-base', $this->dadosValidos());
        ProjetoBase::factory()->inativo()->create(['titulo' => 'Projeto Encerrado']);

        $disponiveis = ProjetoBase::query()->disponiveis()->pluck('titulo');
        $this->assertSame(['Inclusão Digital nas Escolas'], $disponiveis->all());

        $demandante = Usuario::factory()->demandante()->create();
        $this->actingAs($demandante)->get('/projetos-base')
            ->assertOk()
            ->assertSee('Inclusão Digital nas Escolas')
            ->assertDontSee('Projeto Encerrado')
            ->assertDontSee('Novo Projeto Base');
    }

    public function test_admin_cadastra_projeto_escolhendo_o_coordenador(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/projetos-base', $this->dadosValidos())
            ->assertSessionHasErrors('coordenador_id');

        $this->actingAs($admin)
            ->post('/projetos-base', $this->dadosValidos(['coordenador_id' => $admin->id]))
            ->assertSessionHasErrors('coordenador_id');

        $this->actingAs($admin)
            ->post('/projetos-base', $this->dadosValidos(['coordenador_id' => $this->coordenador->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($this->coordenador->id, ProjetoBase::firstOrFail()->coordenador_id);
    }

    public function test_valida_campos_obrigatorios_e_datas(): void
    {
        $this->actingAs($this->coordenador)
            ->post('/projetos-base', $this->dadosValidos([
                'titulo' => '',
                'descricao' => '',
                'tipologia_id' => '',
                'data_fim' => '2026-10-01',
            ]))
            ->assertSessionHasErrors(['titulo', 'descricao', 'tipologia_id', 'data_fim']);
    }

    public function test_nao_aceita_tipologia_inativa_em_projeto_novo(): void
    {
        $inativa = Tipologia::factory()->create(['ativo' => false]);

        $this->actingAs($this->coordenador)
            ->post('/projetos-base', $this->dadosValidos(['tipologia_id' => $inativa->id]))
            ->assertSessionHasErrors('tipologia_id');
    }

    public function test_demandante_e_estudante_nao_cadastram_nem_editam(): void
    {
        $projeto = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id]);

        foreach ([Usuario::factory()->demandante()->create(), Usuario::factory()->estudante()->create()] as $usuario) {
            $this->actingAs($usuario)->get('/projetos-base/create')->assertForbidden();
            $this->actingAs($usuario)->post('/projetos-base', $this->dadosValidos())->assertForbidden();
            $this->actingAs($usuario)->get("/projetos-base/{$projeto->id}/edit")->assertForbidden();
            $this->actingAs($usuario)->put("/projetos-base/{$projeto->id}", $this->dadosValidos())->assertForbidden();
            $this->actingAs($usuario)->get("/projetos-base/{$projeto->id}")->assertOk();
        }
    }

    public function test_demandante_nao_ve_projeto_inativo(): void
    {
        $projeto = ProjetoBase::factory()->inativo()->create();

        $this->actingAs(Usuario::factory()->demandante()->create())
            ->get("/projetos-base/{$projeto->id}")
            ->assertForbidden();
    }

    public function test_coordenador_edita_so_os_proprios_projetos(): void
    {
        $meu = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id]);
        $deOutro = ProjetoBase::factory()->create();

        $this->actingAs($this->coordenador)
            ->put("/projetos-base/{$meu->id}", $this->dadosValidos(['titulo' => 'Título novo', 'publico' => null]))
            ->assertRedirect("/projetos-base/{$meu->id}");

        $meu->refresh();
        $this->assertSame('Título novo', $meu->titulo);
        $this->assertFalse($meu->publico);

        $this->actingAs($this->coordenador)->get("/projetos-base/{$deOutro->id}/edit")->assertForbidden();
        $this->actingAs($this->coordenador)->put("/projetos-base/{$deOutro->id}", $this->dadosValidos())->assertForbidden();
    }

    public function test_edicao_mantem_tipologia_que_foi_inativada(): void
    {
        $projeto = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id]);
        $projeto->tipologia->update(['ativo' => false]);

        $this->actingAs($this->coordenador)
            ->put("/projetos-base/{$projeto->id}", $this->dadosValidos(['tipologia_id' => $projeto->tipologia_id]))
            ->assertSessionHasNoErrors();
    }

    public function test_admin_define_coordenador_do_projeto_demandas_gerais(): void
    {
        $this->seed([DominioSeeder::class, ProjetoBaseSeeder::class]);
        $admin = Usuario::factory()->admin()->create();
        $geral = ProjetoBase::where('titulo', ProjetoBase::DEMANDAS_GERAIS)->firstOrFail();
        $this->assertNull($geral->coordenador_id);

        $this->actingAs($admin)->get('/projetos-base')->assertSee('sem coordenador responsável');

        $this->actingAs($admin)
            ->put("/projetos-base/{$geral->id}", [
                'titulo' => $geral->titulo,
                'descricao' => $geral->descricao,
                'data_inicio' => $geral->data_inicio->format('Y-m-d'),
                'tipologia_id' => $geral->tipologia_id,
                'coordenador_id' => $this->coordenador->id,
                'publico' => '1',
                'ativo' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->coordenador->id, $geral->fresh()->coordenador_id);
    }

    public function test_filtra_projetos_do_coordenador(): void
    {
        ProjetoBase::factory()->create(['titulo' => 'Meu Projeto', 'coordenador_id' => $this->coordenador->id]);
        ProjetoBase::factory()->create(['titulo' => 'Projeto Alheio']);

        $this->actingAs($this->coordenador)->get('/projetos-base?meus=1')
            ->assertOk()->assertSee('Meu Projeto')->assertDontSee('Projeto Alheio');
    }

    public function test_visitante_e_redirecionado_para_o_login(): void
    {
        $this->get('/projetos-base')->assertRedirect('/login');
    }
}
