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
 * RF10: vitrine e candidatura. RF11: avaliação das candidaturas e gestão da equipe.
 */
class EquipeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $coordenador;

    private Usuario $estudante;

    private Subprojeto $subprojeto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordenador = Usuario::factory()->coordenador()->create();
        $this->estudante = Usuario::factory()->estudante()->create();
        $this->subprojeto = $this->subprojetoDoProjeto(['titulo' => 'Site da Escola Aurora', 'vagas' => 2]);
    }

    private function subprojetoDoProjeto(array $atributos = [], ?ProjetoBase $projeto = null): Subprojeto
    {
        $projeto ??= ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id]);
        $solicitacao = Solicitacao::factory()->triada()->comStatus(StatusDemanda::Priorizada)->create(['projeto_base_id' => $projeto->id]);

        return Subprojeto::factory()->create(['solicitacao_id' => $solicitacao->id, ...$atributos]);
    }

    private function candidatura(?Usuario $estudante = null): Candidatura
    {
        return Candidatura::create(['subprojeto_id' => $this->subprojeto->id, 'estudante_id' => ($estudante ?? $this->estudante)->id]);
    }

    private function papel(string $nome = PapelEquipe::PADRAO): PapelEquipe
    {
        return PapelEquipe::where('nome', $nome)->firstOrFail();
    }

    private function aprovar(Candidatura $candidatura, ?PapelEquipe $papel = null)
    {
        return $this->actingAs($this->coordenador)->patch(
            "/subprojetos/{$this->subprojeto->id}/candidaturas/{$candidatura->id}/aprovar",
            ['papel_id' => ($papel ?? $this->papel())->id],
        );
    }

    public function test_vitrine_mostra_so_sub_projetos_que_recebem_candidaturas(): void
    {
        $fechado = $this->subprojetoDoProjeto(['titulo' => 'Fechado pelo coordenador', 'candidaturas_abertas' => false]);
        $completo = $this->subprojetoDoProjeto(['titulo' => 'Equipe completa', 'vagas' => 1]);
        MembroEquipe::create(['subprojeto_id' => $completo->id, 'estudante_id' => Usuario::factory()->estudante()->create()->id, 'papel_id' => $this->papel()->id]);
        $cancelado = $this->subprojetoDoProjeto(['titulo' => 'Demanda cancelada']);
        $cancelado->solicitacao->update(['status' => StatusDemanda::Cancelada, 'motivo_status' => 'Não precisa mais']);
        $projetoInativo = $this->subprojetoDoProjeto(['titulo' => 'Projeto inativo'], ProjetoBase::factory()->inativo()->create());

        $this->actingAs($this->estudante)->get('/oportunidades')
            ->assertOk()
            ->assertSee('Site da Escola Aurora')
            ->assertSee('2 vagas livres de 2')
            ->assertDontSee($fechado->titulo)
            ->assertDontSee($completo->titulo)
            ->assertDontSee($cancelado->titulo)
            ->assertDontSee($projetoInativo->titulo);
    }

    public function test_vitrine_busca_e_filtra_por_modalidade(): void
    {
        $presencial = $this->subprojetoDoProjeto(['titulo' => 'Rede da sala de informática', 'modalidade' => Modalidade::Presencial]);

        $this->actingAs($this->estudante)->get('/oportunidades?modalidade=presencial')
            ->assertSee($presencial->titulo)
            ->assertDontSee($this->subprojeto->titulo);

        $this->actingAs($this->estudante)->get('/oportunidades?busca=Aurora')
            ->assertSee($this->subprojeto->titulo)
            ->assertDontSee($presencial->titulo);
    }

    public function test_estudante_se_candidata_com_um_clique(): void
    {
        $this->actingAs($this->estudante)->post("/oportunidades/{$this->subprojeto->id}/candidatura")
            ->assertRedirect("/oportunidades/{$this->subprojeto->id}");

        $candidatura = Candidatura::sole();
        $this->assertSame($this->estudante->id, $candidatura->estudante_id);
        $this->assertSame(StatusCandidatura::Pendente, $candidatura->status);

        // Não dá para se candidatar duas vezes.
        $this->actingAs($this->estudante)->post("/oportunidades/{$this->subprojeto->id}/candidatura")->assertForbidden();
        $this->actingAs($this->estudante)->get("/oportunidades/{$this->subprojeto->id}")
            ->assertSee('Sua candidatura está aguardando a avaliação da coordenação.');
    }

    public function test_so_estudante_ativo_se_candidata(): void
    {
        $demandante = Usuario::factory()->demandante()->create();

        $this->actingAs($demandante)->post("/oportunidades/{$this->subprojeto->id}/candidatura")->assertForbidden();
        $this->actingAs($this->coordenador)->post("/oportunidades/{$this->subprojeto->id}/candidatura")->assertForbidden();
        $this->actingAs($demandante)->get("/oportunidades/{$this->subprojeto->id}")
            ->assertSee('Só estudantes extensionistas podem se inscrever nos sub-projetos.');

        $this->assertSame(0, Candidatura::count());
    }

    public function test_estudante_cancela_e_pode_se_candidatar_de_novo(): void
    {
        $candidatura = $this->candidatura();

        $this->actingAs($this->estudante)->patch("/subprojetos/{$this->subprojeto->id}/candidaturas/{$candidatura->id}/cancelar")
            ->assertSessionHasNoErrors();
        $this->assertSame(StatusCandidatura::Cancelada, $candidatura->fresh()->status);

        $this->actingAs($this->estudante)->post("/oportunidades/{$this->subprojeto->id}/candidatura")->assertRedirect();
        $this->assertSame(StatusCandidatura::Pendente, Candidatura::sole()->status);
    }

    public function test_estudante_nao_cancela_a_candidatura_de_outro(): void
    {
        $candidatura = $this->candidatura();
        $outro = Usuario::factory()->estudante()->create();

        $this->actingAs($outro)->patch("/subprojetos/{$this->subprojeto->id}/candidaturas/{$candidatura->id}/cancelar")->assertForbidden();
        $this->assertSame(StatusCandidatura::Pendente, $candidatura->fresh()->status);
    }

    public function test_coordenador_aprova_e_o_estudante_entra_na_equipe_com_o_papel(): void
    {
        $candidatura = $this->candidatura();

        $this->aprovar($candidatura, $this->papel('Responsável Técnico'))->assertRedirect("/subprojetos/{$this->subprojeto->id}");

        $candidatura->refresh();
        $this->assertSame(StatusCandidatura::Aprovada, $candidatura->status);
        $this->assertSame($this->coordenador->id, $candidatura->avaliado_por);

        $membro = MembroEquipe::sole();
        $this->assertSame($this->estudante->id, $membro->estudante_id);
        $this->assertSame('Responsável Técnico', $membro->papel->nome);
        $this->assertSame($candidatura->id, $membro->candidatura_id);

        $historico = $this->subprojeto->solicitacao->historicos()->where('tipo', TipoHistorico::Equipe)->sole();
        $this->assertSame("{$this->estudante->nome} entrou na equipe como Responsável Técnico", $historico->descricao);

        $this->actingAs($this->coordenador)->get("/subprojetos/{$this->subprojeto->id}")
            ->assertSee('Equipe (1 de 2 vagas)')
            ->assertSee($this->estudante->nome);
    }

    public function test_aprovacao_respeita_o_numero_de_vagas(): void
    {
        $this->subprojeto->update(['vagas' => 1]);
        $primeira = $this->candidatura();
        $segunda = $this->candidatura(Usuario::factory()->estudante()->create());

        $this->aprovar($primeira)->assertSessionHasNoErrors();
        $this->aprovar($segunda)->assertSessionHasErrors(['candidatura' => 'A equipe está completa. Aumente o número de vagas ou recuse a candidatura.']);

        $this->assertSame(1, MembroEquipe::count());
        $this->assertSame(StatusCandidatura::Pendente, $segunda->fresh()->status);
    }

    public function test_coordenador_recusa_e_o_estudante_nao_se_candidata_de_novo(): void
    {
        $candidatura = $this->candidatura();

        $this->actingAs($this->coordenador)->patch("/subprojetos/{$this->subprojeto->id}/candidaturas/{$candidatura->id}/recusar")
            ->assertRedirect();

        $this->assertSame(StatusCandidatura::Recusada, $candidatura->fresh()->status);
        $this->assertSame(0, MembroEquipe::count());
        $this->actingAs($this->estudante)->post("/oportunidades/{$this->subprojeto->id}/candidatura")->assertForbidden();
    }

    public function test_coordenador_de_outro_projeto_nao_avalia_candidaturas(): void
    {
        $candidatura = $this->candidatura();
        $outro = Usuario::factory()->coordenador()->create();

        $this->actingAs($outro)->patch("/subprojetos/{$this->subprojeto->id}/candidaturas/{$candidatura->id}/aprovar", ['papel_id' => $this->papel()->id])
            ->assertForbidden();
        $this->actingAs($outro)->get("/subprojetos/{$this->subprojeto->id}")->assertForbidden();
    }

    public function test_candidatura_de_outro_sub_projeto_nao_passa_pela_rota(): void
    {
        $outroSubprojeto = $this->subprojetoDoProjeto();
        $candidatura = Candidatura::create(['subprojeto_id' => $outroSubprojeto->id, 'estudante_id' => $this->estudante->id]);

        $this->aprovar($candidatura)->assertNotFound();
    }

    public function test_troca_de_papel_e_remocao_ficam_no_historico(): void
    {
        $this->aprovar($this->candidatura());
        $membro = MembroEquipe::sole();

        $this->actingAs($this->coordenador)->patch("/subprojetos/{$this->subprojeto->id}/membros/{$membro->id}/papel", ['papel_id' => $this->papel('Apoio de Campo')->id])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->coordenador)->patch("/subprojetos/{$this->subprojeto->id}/membros/{$membro->id}/remover")
            ->assertSessionHasNoErrors();

        $membro->refresh();
        $this->assertNotNull($membro->removido_em);
        $this->assertSame('Apoio de Campo', $membro->papel->nome);

        $descricoes = $this->subprojeto->solicitacao->historicos()->where('tipo', TipoHistorico::Equipe)->pluck('descricao')->all();
        $this->assertSame([
            "{$this->estudante->nome} entrou na equipe como Membro Desenvolvedor",
            "Papel de {$this->estudante->nome} alterado de Membro Desenvolvedor para Apoio de Campo",
            "{$this->estudante->nome} foi removido(a) da equipe",
        ], $descricoes);

        // Removido, o estudante não ocupa vaga e não se candidata de novo.
        $this->assertSame(2, $this->subprojeto->fresh()->vagasLivres());
        $this->actingAs($this->estudante)->get("/oportunidades/{$this->subprojeto->id}")->assertSee('Você já participou desta equipe.');
    }

    public function test_membro_da_equipe_ve_a_demanda_e_conversa_no_chat_sem_os_contatos_do_demandante(): void
    {
        $solicitacao = $this->subprojeto->solicitacao;
        $this->actingAs($this->estudante)->get("/demandas/{$solicitacao->id}")->assertForbidden();

        $this->aprovar($this->candidatura());

        $this->actingAs($this->estudante)->get("/demandas/{$solicitacao->id}")
            ->assertOk()
            ->assertSee($solicitacao->demandante->nome)
            ->assertDontSee($solicitacao->demandante->email)
            ->assertDontSee($solicitacao->demandante->telefone);

        $this->actingAs($this->estudante)->post("/demandas/{$solicitacao->id}/mensagens", ['texto' => 'Começo na segunda-feira.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('estudante', $solicitacao->mensagens()->sole()->perfil->value);

        $this->actingAs($this->estudante)->get('/subprojetos')->assertSee($this->subprojeto->titulo);
        $this->actingAs($this->estudante)->get("/subprojetos/{$this->subprojeto->id}")->assertOk()->assertDontSee('Candidaturas pendentes');
    }

    public function test_membro_removido_perde_o_acesso_a_demanda(): void
    {
        $this->aprovar($this->candidatura());
        $membro = MembroEquipe::sole();
        $this->actingAs($this->coordenador)->patch("/subprojetos/{$this->subprojeto->id}/membros/{$membro->id}/remover");

        $this->actingAs($this->estudante)->get("/demandas/{$this->subprojeto->solicitacao_id}")->assertForbidden();
    }

    public function test_equipe_nao_muda_com_a_demanda_concluida(): void
    {
        $candidatura = $this->candidatura();
        $this->subprojeto->solicitacao->update(['status' => StatusDemanda::Concluida]);

        $this->aprovar($candidatura)->assertForbidden();
    }

    public function test_admin_cadastra_novos_papeis(): void
    {
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/cadastros/papeis', ['nome' => 'Designer'])
            ->assertSessionHas('sucesso', 'Papel "Designer" incluído.');

        $this->assertTrue(PapelEquipe::where('nome', 'Designer')->where('ativo', true)->exists());
    }
}
