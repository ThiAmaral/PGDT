<?php

namespace Tests\Feature;

use App\Enums\DecisaoValidacao;
use App\Enums\EtapaValidacao;
use App\Enums\SituacaoEntrega;
use App\Enums\StatusDemanda;
use App\Enums\TipoHistorico;
use App\Models\Entrega;
use App\Models\MembroEquipe;
use App\Models\PapelEquipe;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
use App\Models\Usuario;
use App\Models\Validacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * RF13: validação técnica, aceite e mudança manual de status com sugestão.
 */
class ValidacaoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $coordenador;

    private Usuario $estudante;

    private Usuario $demandante;

    private Solicitacao $solicitacao;

    private Subprojeto $subprojeto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordenador = Usuario::factory()->coordenador()->create();
        $this->estudante = Usuario::factory()->estudante()->create();

        $projeto = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id]);
        $this->solicitacao = Solicitacao::factory()->triada()->comStatus(StatusDemanda::EmDesenvolvimento)->create(['projeto_base_id' => $projeto->id]);
        $this->demandante = $this->solicitacao->demandante;
        $this->subprojeto = Subprojeto::factory()->create(['solicitacao_id' => $this->solicitacao->id]);

        MembroEquipe::create([
            'subprojeto_id' => $this->subprojeto->id,
            'estudante_id' => $this->estudante->id,
            'papel_id' => PapelEquipe::where('nome', PapelEquipe::PADRAO)->firstOrFail()->id,
        ]);
    }

    private function entrega(SituacaoEntrega $situacao = SituacaoEntrega::AguardandoTecnica): Entrega
    {
        return Entrega::factory()->comSituacao($situacao)->create([
            'subprojeto_id' => $this->subprojeto->id,
            'enviado_por' => $this->estudante->id,
            'titulo' => 'Site versão 1',
        ]);
    }

    private function validar(Entrega $entrega, string $decisao, ?string $feedback = null, ?Usuario $usuario = null)
    {
        return $this->actingAs($usuario ?? $this->coordenador)
            ->post("/subprojetos/{$this->subprojeto->id}/entregas/{$entrega->id}/validacao", ['decisao' => $decisao, 'feedback' => $feedback]);
    }

    private function aceitar(Entrega $entrega, string $decisao, ?string $feedback = null, ?Usuario $usuario = null)
    {
        return $this->actingAs($usuario ?? $this->demandante)
            ->post("/subprojetos/{$this->subprojeto->id}/entregas/{$entrega->id}/aceite", ['decisao' => $decisao, 'feedback' => $feedback]);
    }

    public function test_coordenador_aprova_tecnicamente_e_a_entrega_segue_para_o_demandante(): void
    {
        $entrega = $this->entrega();

        $this->validar($entrega, 'aprovada')->assertRedirect()->assertSessionHas('sucesso');

        $this->assertSame(SituacaoEntrega::AguardandoAceite, $entrega->fresh()->situacao);
        $validacao = $entrega->validacoes()->sole();
        $this->assertSame(EtapaValidacao::Tecnica, $validacao->etapa);
        $this->assertSame(DecisaoValidacao::Aprovada, $validacao->decisao);
        $this->assertSame($this->coordenador->id, $validacao->usuario_id);

        $historico = $this->solicitacao->historicos()->where('tipo', TipoHistorico::Validacao)->sole();
        $this->assertSame('Validação técnica da entrega nº 1 "Site versão 1": Aprovada tecnicamente', $historico->descricao);
        $this->assertSame($this->coordenador->id, $historico->usuario_id);

        // A decisão não muda o status da demanda (decisão da Fase 0).
        $this->assertSame(StatusDemanda::EmDesenvolvimento, $this->solicitacao->fresh()->status);
    }

    public function test_devolver_para_ajustes_exige_feedback_que_vai_para_a_linha_do_tempo(): void
    {
        $entrega = $this->entrega();

        $this->validar($entrega, 'devolvida', '   ')->assertSessionHasErrors('feedback');
        $this->assertSame(SituacaoEntrega::AguardandoTecnica, $entrega->fresh()->situacao);
        $this->assertSame(0, Validacao::count());

        $this->validar($entrega, 'devolvida', 'Falta o calendário de eventos.')->assertRedirect();

        $this->assertSame(SituacaoEntrega::Devolvida, $entrega->fresh()->situacao);
        $historico = $this->solicitacao->historicos()->where('tipo', TipoHistorico::Validacao)->sole();
        $this->assertSame('Falta o calendário de eventos.', $historico->dados['feedback']);

        $this->actingAs($this->estudante)->get("/demandas/{$this->solicitacao->id}")
            ->assertOk()
            ->assertSee('Falta o calendário de eventos.');
    }

    public function test_so_quem_gerencia_a_demanda_faz_a_validacao_tecnica(): void
    {
        $entrega = $this->entrega();

        $this->validar($entrega, 'aprovada', null, $this->estudante)->assertForbidden();
        $this->validar($entrega, 'aprovada', null, $this->demandante)->assertForbidden();
        $this->validar($entrega, 'aprovada', null, Usuario::factory()->coordenador()->create())->assertForbidden();

        // As decisões do aceite não valem na etapa técnica.
        $this->validar($entrega, 'aceita')->assertSessionHasErrors('decisao');

        $this->validar($entrega, 'aprovada', null, Usuario::factory()->admin()->create())->assertRedirect();
        $this->assertSame(SituacaoEntrega::AguardandoAceite, $entrega->fresh()->situacao);

        // Já decidida, a etapa técnica não é repetida.
        $this->validar($entrega, 'devolvida', 'Mudei de ideia')->assertForbidden();
    }

    public function test_demandante_aceita_a_solucao(): void
    {
        $entrega = $this->entrega(SituacaoEntrega::AguardandoAceite);

        $this->actingAs($this->demandante)->get("/demandas/{$this->solicitacao->id}")
            ->assertOk()
            ->assertSee('A coordenação aprovou a entrega');

        $this->aceitar($entrega, 'aceita')->assertRedirect()->assertSessionHas('sucesso');

        $this->assertSame(SituacaoEntrega::Aceita, $entrega->fresh()->situacao);
        $validacao = $entrega->validacoes()->sole();
        $this->assertSame(EtapaValidacao::Aceite, $validacao->etapa);
        $this->assertSame($this->demandante->id, $validacao->usuario_id);
    }

    public function test_reportar_pendencia_exige_descricao_e_devolve_para_a_equipe(): void
    {
        $entrega = $this->entrega(SituacaoEntrega::AguardandoAceite);

        $this->aceitar($entrega, 'pendencia')->assertSessionHasErrors('feedback');
        $this->aceitar($entrega, 'pendencia', 'O formulário de contato não envia.')->assertRedirect();

        $this->assertSame(SituacaoEntrega::Pendencia, $entrega->fresh()->situacao);
        $this->assertNull($this->subprojeto->impedimentoDeEntrega($this->estudante));
    }

    public function test_aceite_so_depois_da_aprovacao_tecnica_e_so_por_quem_pode(): void
    {
        $aguardando = $this->entrega();
        $this->aceitar($aguardando, 'aceita')->assertForbidden();
        $this->aceitar($aguardando, 'aceita', null, $this->coordenador)->assertForbidden();

        $aguardando->update(['situacao' => SituacaoEntrega::AguardandoAceite]);
        $this->aceitar($aguardando, 'aceita', null, $this->estudante)->assertForbidden();
        $this->aceitar($aguardando, 'aceita', null, Usuario::factory()->coordenador()->create())->assertForbidden();
        $this->aceitar($aguardando, 'aceita', null, Usuario::factory()->demandante()->create())->assertForbidden();
        $this->aceitar($aguardando, 'aprovada')->assertSessionHasErrors('decisao');

        $this->assertSame(SituacaoEntrega::AguardandoAceite, $aguardando->fresh()->situacao);
    }

    public function test_coordenador_registra_o_aceite_em_nome_do_demandante(): void
    {
        $entrega = $this->entrega(SituacaoEntrega::AguardandoAceite);

        $this->actingAs($this->coordenador)->get("/subprojetos/{$this->subprojeto->id}/entregas/{$entrega->id}")
            ->assertOk()
            ->assertSee('Aceitar Solução')
            ->assertSee('Reportar Pendência')
            ->assertSee('registre a decisão em nome dele');

        $this->aceitar($entrega, 'aceita', null, $this->coordenador)
            ->assertRedirect()
            ->assertSessionHas('sucesso', fn (string $mensagem) => str_contains($mensagem, 'em nome do demandante'));

        $this->assertSame(SituacaoEntrega::Aceita, $entrega->fresh()->situacao);
        $this->assertSame($this->coordenador->id, $entrega->validacoes()->sole()->usuario_id);

        $historico = $this->solicitacao->historicos()->where('tipo', TipoHistorico::Validacao)->sole();
        $this->assertSame('Aceite da entrega nº 1 "Site versão 1": Solução aceita (registrado pela coordenação em nome do demandante)', $historico->descricao);
        $this->assertTrue($historico->dados['em_nome_do_demandante']);
    }

    public function test_admin_reporta_pendencia_em_nome_do_demandante(): void
    {
        $entrega = $this->entrega(SituacaoEntrega::AguardandoAceite);

        $this->aceitar($entrega, 'pendencia', 'A escola pediu para incluir a galeria de fotos.', Usuario::factory()->admin()->create())->assertRedirect();

        $this->assertSame(SituacaoEntrega::Pendencia, $entrega->fresh()->situacao);
        $this->assertStringContainsString('em nome do demandante', $this->solicitacao->historicos()->where('tipo', TipoHistorico::Validacao)->sole()->descricao);
    }

    public function test_aceite_do_proprio_demandante_nao_leva_a_observacao(): void
    {
        $entrega = $this->entrega(SituacaoEntrega::AguardandoAceite);

        $this->aceitar($entrega, 'aceita');

        $this->assertSame('Aceite da entrega nº 1 "Site versão 1": Solução aceita', $this->solicitacao->historicos()->where('tipo', TipoHistorico::Validacao)->sole()->descricao);
    }

    public function test_validacoes_nao_podem_ser_editadas_nem_apagadas(): void
    {
        $entrega = $this->entrega();
        $this->validar($entrega, 'aprovada');
        $validacao = Validacao::sole();

        $this->expectException(LogicException::class);
        $validacao->update(['feedback' => 'alterado']);
    }

    public function test_em_validacao_so_com_entrega_e_concluida_so_com_aceite(): void
    {
        $url = "/demandas/{$this->solicitacao->id}/status";

        // Sem entrega, o coordenador não tem status para aplicar; o admin ainda pode cancelar.
        $this->actingAs($this->coordenador)->patch($url, ['status' => 'em_validacao'])->assertForbidden();
        $this->actingAs(Usuario::factory()->admin()->create())->patch($url, ['status' => 'em_validacao'])->assertSessionHasErrors('status');

        $entrega = $this->entrega();
        $this->actingAs($this->coordenador)->patch($url, ['status' => 'concluida'])->assertSessionHasErrors('status');
        $this->actingAs($this->coordenador)->patch($url, ['status' => 'em_validacao'])->assertSessionHasNoErrors();
        $this->assertSame(StatusDemanda::EmValidacao, $this->solicitacao->fresh()->status);

        $entrega->update(['situacao' => SituacaoEntrega::Aceita]);
        $this->actingAs($this->coordenador)->patch($url, ['status' => 'concluida'])->assertSessionHasNoErrors();
        $this->assertSame(StatusDemanda::Concluida, $this->solicitacao->fresh()->status);
    }

    public function test_tela_sugere_o_proximo_status_conforme_as_entregas(): void
    {
        $this->solicitacao->update(['status' => StatusDemanda::EmValidacao]);
        $primeira = $this->entrega();

        $this->validar($primeira, 'devolvida', 'Ajustar o menu.');

        $this->actingAs($this->coordenador)->get("/demandas/{$this->solicitacao->id}")
            ->assertOk()
            ->assertSee('Mudar para "Aguardando Ajustes"?', false)
            ->assertSee('Aguardando Ajustes</option>', false)
            ->assertDontSee('Concluída</option>', false);

        // Nova entrega em avaliação: a sugestão volta a ser "Em Validação".
        $this->solicitacao->update(['status' => StatusDemanda::AguardandoAjustes]);
        $segunda = $this->entrega();
        $this->assertSame(StatusDemanda::EmValidacao, $this->solicitacao->fresh()->statusSugerido());

        // Uma aceita e outra ainda em avaliação: "Concluída" já aparece, com aviso.
        $segunda->update(['situacao' => SituacaoEntrega::Aceita]);
        $terceira = $this->entrega();
        $this->assertSame(StatusDemanda::EmValidacao, $this->solicitacao->fresh()->statusSugerido());
        $this->actingAs($this->coordenador)->get("/demandas/{$this->solicitacao->id}")
            ->assertSee('Concluída</option>', false)
            ->assertSee('Uma entrega ainda está em avaliação.');

        // Tudo avaliado e a mais recente aceita: sugere concluir.
        $terceira->update(['situacao' => SituacaoEntrega::Aceita]);
        $this->assertSame(StatusDemanda::Concluida, $this->solicitacao->fresh()->statusSugerido());
        $this->actingAs($this->coordenador)->get("/demandas/{$this->solicitacao->id}")
            ->assertSee('Mudar para "Concluída"?', false);
    }

    public function test_demandante_ve_um_aviso_para_cada_entrega_aguardando_aceite(): void
    {
        $this->entrega(SituacaoEntrega::AguardandoAceite);
        Entrega::factory()->comSituacao(SituacaoEntrega::AguardandoAceite)->create([
            'subprojeto_id' => $this->subprojeto->id, 'enviado_por' => $this->estudante->id, 'titulo' => 'Manual',
        ]);
        $this->entrega(); // ainda na etapa técnica: o demandante não vê.

        $this->actingAs($this->demandante)->get("/demandas/{$this->solicitacao->id}")
            ->assertOk()
            ->assertSee('A coordenação aprovou a entrega nº 1')
            ->assertSee('A coordenação aprovou a entrega nº 2')
            ->assertDontSee('entrega nº 3');
    }

    public function test_painel_mostra_as_entregas_que_esperam_decisao(): void
    {
        $entrega = $this->entrega();

        $this->actingAs($this->coordenador)->get('/painel')
            ->assertOk()
            ->assertSeeInOrder(['Entregas para validar', '1']);
        $this->actingAs($this->demandante)->get('/painel')->assertDontSee('aguarda o seu aceite');

        $entrega->update(['situacao' => SituacaoEntrega::AguardandoAceite]);

        $this->actingAs($this->demandante)->get('/painel')->assertSee('Uma entrega aprovada pela coordenação aguarda o seu aceite');
    }

    public function test_demanda_encerrada_nao_recebe_mais_decisoes(): void
    {
        $entrega = $this->entrega();
        $this->solicitacao->update(['status' => StatusDemanda::Cancelada]);

        $this->validar($entrega, 'aprovada')->assertForbidden();
    }
}
