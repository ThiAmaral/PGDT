<?php

namespace Tests\Feature;

use App\Enums\Modalidade;
use App\Enums\StatusCandidatura;
use App\Enums\StatusDemanda;
use App\Enums\TipoHistorico;
use App\Models\Candidatura;
use App\Models\MembroEquipe;
use App\Models\PapelEquipe;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RF09: criação do sub-projeto e carga horária com histórico.
 */
class SubprojetoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $coordenador;

    private ProjetoBase $projeto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordenador = Usuario::factory()->coordenador()->create();
        $this->projeto = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id]);
    }

    private function demandaPriorizada(): Solicitacao
    {
        return Solicitacao::factory()->triada()->comStatus(StatusDemanda::Priorizada)->create([
            'projeto_base_id' => $this->projeto->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosValidos(array $alteracoes = []): array
    {
        return [
            'titulo' => 'Site da escola',
            'escopo' => 'Criar o site institucional com notícias e calendário.',
            'data_inicio' => '2026-11-01',
            'data_fim' => '2027-02-28',
            'carga_horaria_estimada' => 60,
            'vagas' => 3,
            'modalidade' => 'hibrido',
            ...$alteracoes,
        ];
    }

    public function test_formulario_vem_preenchido_com_os_dados_da_demanda(): void
    {
        $solicitacao = $this->demandaPriorizada();

        $this->actingAs($this->coordenador)->get("/demandas/{$solicitacao->id}/subprojeto/criar")
            ->assertOk()
            ->assertSee($solicitacao->titulo)
            ->assertSee($solicitacao->descricao);
    }

    public function test_coordenador_cria_o_sub_projeto_e_vira_o_responsavel(): void
    {
        $solicitacao = $this->demandaPriorizada();

        $this->actingAs($this->coordenador)->post("/demandas/{$solicitacao->id}/subprojeto", $this->dadosValidos())
            ->assertRedirect("/demandas/{$solicitacao->id}")
            ->assertSessionHas('sucesso', fn (string $mensagem) => str_contains($mensagem, 'Em Desenvolvimento'));

        $subprojeto = $solicitacao->subprojeto()->sole();
        $this->assertSame($this->coordenador->id, $subprojeto->coordenador_id);
        $this->assertSame(Modalidade::Hibrido, $subprojeto->modalidade);
        $this->assertSame(60, $subprojeto->carga_horaria_estimada);
        $this->assertTrue($subprojeto->candidaturas_abertas);

        // Criar o sub-projeto não muda o status da demanda (decisão da Fase 0).
        $this->assertSame(StatusDemanda::Priorizada, $solicitacao->fresh()->status);

        $historico = $solicitacao->historicos()->where('tipo', TipoHistorico::Subprojeto)->sole();
        $this->assertSame('Sub-projeto criado', $historico->descricao);
        $this->assertSame(['de' => null, 'para' => '60 h'], $historico->dados['carga horária estimada']);
    }

    public function test_sub_projeto_so_nasce_de_demanda_priorizada_e_uma_unica_vez(): void
    {
        $emAnalise = Solicitacao::factory()->triada()->create(['projeto_base_id' => $this->projeto->id]);
        $this->actingAs($this->coordenador)->post("/demandas/{$emAnalise->id}/subprojeto", $this->dadosValidos())->assertForbidden();

        $subprojeto = Subprojeto::factory()->create(['solicitacao_id' => $this->demandaPriorizada()->id]);
        $this->actingAs($this->coordenador)->post("/demandas/{$subprojeto->solicitacao_id}/subprojeto", $this->dadosValidos())->assertForbidden();

        $this->assertSame(1, Subprojeto::count());
    }

    public function test_so_quem_gerencia_a_demanda_cria_o_sub_projeto(): void
    {
        $solicitacao = $this->demandaPriorizada();
        $outroCoordenador = Usuario::factory()->coordenador()->create();

        $this->actingAs($outroCoordenador)->post("/demandas/{$solicitacao->id}/subprojeto", $this->dadosValidos())->assertForbidden();
        $this->actingAs($solicitacao->demandante)->post("/demandas/{$solicitacao->id}/subprojeto", $this->dadosValidos())->assertForbidden();

        // Criado pelo admin, o responsável é o coordenador do projeto.
        $admin = Usuario::factory()->admin()->create();
        $this->actingAs($admin)->post("/demandas/{$solicitacao->id}/subprojeto", $this->dadosValidos())->assertRedirect();
        $this->assertSame($this->coordenador->id, $solicitacao->subprojeto()->sole()->coordenador_id);
    }

    public function test_admin_vira_o_responsavel_quando_o_projeto_nao_tem_coordenador(): void
    {
        $semCoordenador = ProjetoBase::factory()->create(['coordenador_id' => null]);
        $solicitacao = Solicitacao::factory()->triada()->comStatus(StatusDemanda::Priorizada)->create(['projeto_base_id' => $semCoordenador->id]);
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->post("/demandas/{$solicitacao->id}/subprojeto", $this->dadosValidos())->assertRedirect();
        $this->assertSame($admin->id, $solicitacao->subprojeto()->sole()->coordenador_id);
    }

    public function test_valida_escopo_datas_vagas_e_modalidade(): void
    {
        $solicitacao = $this->demandaPriorizada();

        $this->actingAs($this->coordenador)->post("/demandas/{$solicitacao->id}/subprojeto", $this->dadosValidos([
            'escopo' => '',
            'data_fim' => '2026-10-01',
            'vagas' => 0,
            'modalidade' => 'teletransporte',
            'carga_horaria_estimada' => '',
        ]))->assertSessionHasErrors(['escopo', 'data_fim', 'vagas', 'modalidade', 'carga_horaria_estimada']);

        $this->assertSame(0, Subprojeto::count());
    }

    public function test_em_desenvolvimento_so_aparece_depois_do_sub_projeto(): void
    {
        $solicitacao = $this->demandaPriorizada();

        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'em_desenvolvimento'])
            ->assertSessionHasErrors('status');

        Subprojeto::factory()->create(['solicitacao_id' => $solicitacao->id]);

        $this->actingAs($this->coordenador)->get("/demandas/{$solicitacao->id}")
            ->assertSee('Sub-projeto criado. Se a equipe já pode começar, mude para "Em Desenvolvimento".', false);

        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'em_desenvolvimento'])
            ->assertSessionHasNoErrors();
        $this->assertSame(StatusDemanda::EmDesenvolvimento, $solicitacao->fresh()->status);
    }

    public function test_alteracao_da_carga_horaria_fica_no_historico_com_valor_anterior_e_novo(): void
    {
        $subprojeto = Subprojeto::factory()->create(['solicitacao_id' => $this->demandaPriorizada()->id, 'carga_horaria_estimada' => 40]);

        $this->actingAs($this->coordenador)->put("/subprojetos/{$subprojeto->id}", $this->dadosValidos([
            'titulo' => $subprojeto->titulo,
            'carga_horaria_estimada' => 75,
            'candidaturas_abertas' => '1',
        ]))->assertRedirect("/subprojetos/{$subprojeto->id}");

        $registro = $subprojeto->solicitacao->historicos()->where('tipo', TipoHistorico::CargaHoraria)->sole();
        $this->assertSame('Carga horária estimada alterada de 40 h para 75 h', $registro->descricao);
        $this->assertSame(['de' => 40, 'para' => 75], $registro->dados);
        $this->assertSame($this->coordenador->id, $registro->usuario_id);
    }

    public function test_carga_horaria_pode_ser_corrigida_depois_de_concluida_mas_o_resto_nao(): void
    {
        $subprojeto = Subprojeto::factory()->create(['solicitacao_id' => $this->demandaPriorizada()->id, 'carga_horaria_estimada' => 40, 'vagas' => 3]);
        $subprojeto->solicitacao->update(['status' => StatusDemanda::Concluida]);

        $this->actingAs($this->coordenador)->get("/subprojetos/{$subprojeto->id}/edit")
            ->assertOk()
            ->assertSee('só a carga horária estimada pode ser corrigida');

        $this->actingAs($this->coordenador)->put("/subprojetos/{$subprojeto->id}", [
            'carga_horaria_estimada' => 48,
            'vagas' => 10,
        ])->assertRedirect();

        $subprojeto->refresh();
        $this->assertSame(48, $subprojeto->carga_horaria_estimada);
        $this->assertSame(3, $subprojeto->vagas);
    }

    public function test_vagas_nao_ficam_abaixo_do_numero_de_membros(): void
    {
        $subprojeto = Subprojeto::factory()->create(['solicitacao_id' => $this->demandaPriorizada()->id, 'vagas' => 3]);
        $papel = PapelEquipe::firstOrFail();

        foreach (Usuario::factory()->estudante()->count(2)->create() as $estudante) {
            MembroEquipe::create(['subprojeto_id' => $subprojeto->id, 'estudante_id' => $estudante->id, 'papel_id' => $papel->id]);
        }

        $this->actingAs($this->coordenador)->put("/subprojetos/{$subprojeto->id}", $this->dadosValidos(['vagas' => 1]))
            ->assertSessionHasErrors(['vagas' => 'A equipe já tem 2 membros: as vagas não podem ser menos que isso.']);
    }

    public function test_outras_alteracoes_ficam_no_historico_sem_repetir_a_carga_horaria(): void
    {
        $subprojeto = Subprojeto::factory()->create([
            'solicitacao_id' => $this->demandaPriorizada()->id,
            'vagas' => 3,
            'modalidade' => Modalidade::Remoto,
            'carga_horaria_estimada' => 60,
        ]);

        $this->actingAs($this->coordenador)->put("/subprojetos/{$subprojeto->id}", $this->dadosValidos([
            'titulo' => $subprojeto->titulo,
            'escopo' => $subprojeto->escopo,
            'data_inicio' => $subprojeto->data_inicio->format('Y-m-d'),
            'data_fim' => $subprojeto->data_fim->format('Y-m-d'),
            'vagas' => 4,
            'modalidade' => 'presencial',
            'candidaturas_abertas' => '1',
        ]));

        $registro = $subprojeto->solicitacao->historicos()->where('descricao', 'Sub-projeto atualizado')->sole();
        $this->assertSame([
            'vagas' => ['de' => '3', 'para' => '4'],
            'modalidade' => ['de' => 'Remoto', 'para' => 'Presencial'],
        ], $registro->dados);
        $this->assertFalse($subprojeto->solicitacao->historicos()->where('tipo', TipoHistorico::CargaHoraria)->exists());
    }

    public function test_candidaturas_pendentes_aparecem_no_painel_do_coordenador(): void
    {
        $subprojeto = Subprojeto::factory()->create(['solicitacao_id' => $this->demandaPriorizada()->id]);
        Candidatura::create(['subprojeto_id' => $subprojeto->id, 'estudante_id' => Usuario::factory()->estudante()->create()->id]);

        $this->actingAs($this->coordenador)->get('/painel')
            ->assertOk()
            ->assertViewHas('totalCandidaturasPendentes', 1);

        $this->actingAs($this->coordenador)->get('/subprojetos')
            ->assertOk()
            ->assertSee($subprojeto->titulo)
            ->assertSee('1 pendente(s)');

        $this->assertSame(StatusCandidatura::Pendente, Candidatura::sole()->status);
    }
}
