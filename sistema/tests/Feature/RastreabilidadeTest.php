<?php

namespace Tests\Feature;

use App\Enums\Perfil;
use App\Enums\StatusDemanda;
use App\Enums\TipoHistorico;
use App\Models\CategoriaDemanda;
use App\Models\Historico;
use App\Models\Mensagem;
use App\Models\Prioridade;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * RF08: linha do tempo, barra de progresso e chat da demanda.
 */
class RastreabilidadeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $demandante;

    private Usuario $coordenador;

    private ProjetoBase $projeto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->demandante = Usuario::factory()->demandante()->create();
        $this->coordenador = Usuario::factory()->coordenador()->create();
        $this->projeto = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id]);
    }

    private function demandaDoProjeto(array $atributos = []): Solicitacao
    {
        return Solicitacao::factory()->create([
            'projeto_base_id' => $this->projeto->id,
            'demandante_id' => $this->demandante->id,
            ...$atributos,
        ]);
    }

    public function test_abertura_da_demanda_entra_na_linha_do_tempo(): void
    {
        $this->actingAs($this->demandante)->post('/demandas', [
            'projeto_base_id' => $this->projeto->id,
            'titulo' => 'Projetor da sala 3 queimado',
            'descricao' => 'O projetor não liga.',
            'justificativa' => 'Aulas de ciências dependem dele.',
            'publico_afetado' => '120 alunos',
        ]);

        $historico = Solicitacao::firstOrFail()->historicos()->sole();
        $this->assertSame(TipoHistorico::Status, $historico->tipo);
        $this->assertSame('Demanda aberta', $historico->descricao);
        $this->assertSame($this->demandante->id, $historico->usuario_id);
        $this->assertSame(['de' => null, 'para' => 'cadastrada'], $historico->dados);
    }

    public function test_cada_mudanca_de_status_fica_registrada_com_autor_e_motivo(): void
    {
        $solicitacao = $this->demandaDoProjeto();

        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'em_analise']);
        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", [
            'status' => 'recusada',
            'motivo_status' => 'Fora do escopo do projeto',
        ]);

        $mudancas = $solicitacao->historicos()->where('tipo', TipoHistorico::Status)->get();
        $this->assertCount(3, $mudancas);

        $this->assertSame('Status alterado de "Cadastrada" para "Em Análise"', $mudancas[1]->descricao);
        $this->assertSame($this->coordenador->id, $mudancas[1]->usuario_id);
        $this->assertSame(['de' => 'em_analise', 'para' => 'recusada', 'motivo' => 'Fora do escopo do projeto'], $mudancas[2]->dados);
    }

    public function test_status_recusado_por_erro_de_validacao_nao_gera_registro(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::EmAnalise]);
        $antes = $solicitacao->historicos()->count();

        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'priorizada'])->assertSessionHasErrors('status');

        $this->assertSame($antes, $solicitacao->historicos()->count());
    }

    public function test_classificacao_e_revisao_ficam_registradas_com_os_nomes(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::EmAnalise]);
        $categoria = CategoriaDemanda::create(['nome' => 'Infraestrutura de Rede']);
        $media = Prioridade::create(['nome' => 'Média', 'ordem' => 3]);
        $alta = Prioridade::create(['nome' => 'Alta', 'ordem' => 2]);

        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/triagem", [
            'categoria_id' => $categoria->id,
            'prioridade_id' => $media->id,
            'complexidade' => 'baixa',
        ]);
        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/triagem", [
            'categoria_id' => $categoria->id,
            'prioridade_id' => $alta->id,
            'complexidade' => 'baixa',
        ]);

        [$primeira, $revisao] = $solicitacao->historicos()->where('tipo', TipoHistorico::Triagem)->get();

        $this->assertSame('Demanda classificada', $primeira->descricao);
        $this->assertSame([
            'categoria' => ['de' => null, 'para' => 'Infraestrutura de Rede'],
            'prioridade' => ['de' => null, 'para' => 'Média'],
            'complexidade' => ['de' => null, 'para' => 'Baixa'],
        ], $primeira->dados);

        // Na revisão, só o que mudou.
        $this->assertSame('Classificação revista', $revisao->descricao);
        $this->assertSame(['prioridade' => ['de' => 'Média', 'para' => 'Alta']], $revisao->dados);
    }

    public function test_salvar_a_mesma_classificacao_nao_gera_registro(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::EmAnalise]);
        $dados = [
            'categoria_id' => CategoriaDemanda::create(['nome' => 'Software'])->id,
            'prioridade_id' => Prioridade::create(['nome' => 'Baixa', 'ordem' => 4])->id,
            'complexidade' => 'media',
        ];

        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/triagem", $dados);
        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/triagem", $dados);

        $this->assertSame(1, $solicitacao->historicos()->where('tipo', TipoHistorico::Triagem)->count());
    }

    public function test_historico_nao_pode_ser_editado_nem_apagado(): void
    {
        $historico = $this->demandaDoProjeto()->historicos()->firstOrFail();

        try {
            $historico->update(['descricao' => 'Alterado']);
            $this->fail('O histórico foi editado.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $historico->delete();
    }

    public function test_tela_mostra_linha_do_tempo_e_barra_de_progresso(): void
    {
        $solicitacao = $this->demandaDoProjeto();
        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'em_analise']);

        $this->actingAs($this->demandante)->get("/demandas/{$solicitacao->id}")
            ->assertOk()
            ->assertSee('Linha do tempo')
            ->assertSee('Demanda aberta')
            ->assertSee('Status alterado de &quot;Cadastrada&quot; para &quot;Em Análise&quot;', false)
            ->assertSee($this->coordenador->nome)
            ->assertSee('Andamento da demanda');
    }

    public function test_barra_de_progresso_acompanha_o_status(): void
    {
        $this->assertSame(0, $this->demandaDoProjeto()->etapaDoProgresso());
        $this->assertSame(2, $this->demandaDoProjeto(['status' => StatusDemanda::Priorizada])->etapaDoProgresso());
        $this->assertSame(4, $this->demandaDoProjeto(['status' => StatusDemanda::AguardandoAjustes])->etapaDoProgresso());
        $this->assertSame(5, $this->demandaDoProjeto(['status' => StatusDemanda::Concluida])->etapaDoProgresso());
    }

    public function test_demanda_recusada_mostra_a_etapa_em_que_parou(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::EmAnalise]);
        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", [
            'status' => 'recusada',
            'motivo_status' => 'Sem equipe disponível',
        ]);

        $solicitacao->refresh();
        $this->assertSame(1, $solicitacao->etapaDoProgresso());

        $this->actingAs($this->demandante)->get("/demandas/{$solicitacao->id}")
            ->assertSee('Demanda recusada na etapa "Em Análise".', false)
            ->assertSee('Motivo: Sem equipe disponível');
    }

    public function test_demandante_e_coordenador_trocam_mensagens(): void
    {
        $solicitacao = $this->demandaDoProjeto();

        $this->actingAs($this->demandante)
            ->post("/demandas/{$solicitacao->id}/mensagens", ['texto' => 'O laboratório fica aberto à tarde.'])
            ->assertRedirect("/demandas/{$solicitacao->id}#chat")
            ->assertSessionHasNoErrors();

        $this->actingAs($this->coordenador)
            ->post("/demandas/{$solicitacao->id}/mensagens", ['texto' => 'Combinado, vamos na quinta.'])
            ->assertSessionHasNoErrors();

        [$primeira, $segunda] = $solicitacao->mensagens()->get();
        $this->assertSame($this->demandante->id, $primeira->usuario_id);
        $this->assertSame(Perfil::Demandante, $primeira->perfil);
        $this->assertSame(Perfil::Coordenador, $segunda->perfil);

        $this->actingAs($this->demandante)->get("/demandas/{$solicitacao->id}")
            ->assertSeeInOrder(['O laboratório fica aberto à tarde.', 'Combinado, vamos na quinta.'])
            ->assertSee($this->coordenador->nome)
            ->assertSee(Perfil::Coordenador->rotulo())
            ->assertSee('Enviar Comentário');
    }

    public function test_admin_tambem_comenta(): void
    {
        $solicitacao = $this->demandaDoProjeto();

        $this->actingAs(Usuario::factory()->admin()->create())
            ->post("/demandas/{$solicitacao->id}/mensagens", ['texto' => 'Acompanhando.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(Perfil::Admin, Mensagem::sole()->perfil);
    }

    public function test_comentario_vazio_ou_longo_demais_e_recusado(): void
    {
        $solicitacao = $this->demandaDoProjeto();

        $this->actingAs($this->demandante)->post("/demandas/{$solicitacao->id}/mensagens", ['texto' => '   '])->assertSessionHasErrors('texto');
        $this->actingAs($this->demandante)
            ->post("/demandas/{$solicitacao->id}/mensagens", ['texto' => str_repeat('a', Mensagem::TAMANHO_MAXIMO + 1)])
            ->assertSessionHasErrors('texto');

        $this->assertSame(0, Mensagem::count());
    }

    public function test_chat_e_linha_do_tempo_so_para_os_envolvidos(): void
    {
        $solicitacao = $this->demandaDoProjeto();
        Mensagem::create([
            'solicitacao_id' => $solicitacao->id,
            'usuario_id' => $this->demandante->id,
            'perfil' => Perfil::Demandante,
            'texto' => 'Mensagem reservada',
        ]);

        $estranhos = [
            Usuario::factory()->demandante()->create(),
            Usuario::factory()->coordenador()->create(),
            Usuario::factory()->estudante()->create(),
        ];

        foreach ($estranhos as $estranho) {
            $this->actingAs($estranho)->get("/demandas/{$solicitacao->id}")->assertForbidden();
            $this->actingAs($estranho)->post("/demandas/{$solicitacao->id}/mensagens", ['texto' => 'Oi'])->assertForbidden();
        }

        $this->assertSame(1, Mensagem::count());
    }

    public function test_chat_fica_so_para_leitura_quando_a_demanda_e_cancelada(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::Cancelada, 'motivo_status' => 'Resolvido internamente']);

        $this->actingAs($this->demandante)->post("/demandas/{$solicitacao->id}/mensagens", ['texto' => 'Oi'])->assertForbidden();
        $this->actingAs($this->demandante)->get("/demandas/{$solicitacao->id}")
            ->assertOk()
            ->assertDontSee('Enviar Comentário')
            ->assertSee('A conversa está encerrada');
    }

    public function test_demanda_recusada_continua_aceitando_comentarios(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::Recusada, 'motivo_status' => 'Fora do escopo']);

        $this->actingAs($this->demandante)
            ->post("/demandas/{$solicitacao->id}/mensagens", ['texto' => 'Podem reconsiderar?'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $solicitacao->mensagens()->count());
    }

    public function test_historico_de_status_tem_a_etapa_registrada_antes_do_recusado(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::Priorizada]);
        $this->actingAs(Usuario::factory()->admin()->create())->patch("/demandas/{$solicitacao->id}/status", [
            'status' => 'cancelada',
            'motivo_status' => 'Duplicada',
        ]);

        $this->assertSame(2, $solicitacao->refresh()->etapaDoProgresso());
        $this->assertSame(2, Historico::where('solicitacao_id', $solicitacao->id)->where('tipo', TipoHistorico::Status)->count());
    }
}
