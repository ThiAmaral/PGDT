<?php

namespace Tests\Feature;

use App\Enums\SituacaoEntrega;
use App\Enums\StatusDemanda;
use App\Models\Candidatura;
use App\Models\Entrega;
use App\Models\MembroEquipe;
use App\Models\PapelEquipe;
use App\Models\Parametro;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
use App\Models\Usuario;
use App\Notifications\Aviso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Fase 7: avisos por e-mail de nova demanda, status, chat, candidaturas e entregas.
 */
class NotificacaoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $coordenador;

    private Usuario $estudante;

    private Usuario $demandante;

    private ProjetoBase $projeto;

    private Solicitacao $solicitacao;

    private Subprojeto $subprojeto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coordenador = Usuario::factory()->coordenador()->create();
        $this->estudante = Usuario::factory()->estudante()->create();
        $this->projeto = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id]);
        $this->solicitacao = Solicitacao::factory()->triada()->comStatus(StatusDemanda::EmDesenvolvimento)->create(['projeto_base_id' => $this->projeto->id]);
        $this->demandante = $this->solicitacao->demandante;
        $this->subprojeto = Subprojeto::factory()->create(['solicitacao_id' => $this->solicitacao->id]);

        MembroEquipe::create([
            'subprojeto_id' => $this->subprojeto->id,
            'estudante_id' => $this->estudante->id,
            'papel_id' => PapelEquipe::where('nome', PapelEquipe::PADRAO)->firstOrFail()->id,
        ]);

        Notification::fake();
    }

    private function entrega(SituacaoEntrega $situacao = SituacaoEntrega::AguardandoTecnica): Entrega
    {
        return Entrega::factory()->comSituacao($situacao)->create([
            'subprojeto_id' => $this->subprojeto->id,
            'enviado_por' => $this->estudante->id,
            'titulo' => 'Site versão 1',
        ]);
    }

    public function test_nova_demanda_avisa_o_coordenador_do_projeto(): void
    {
        $demandante = Usuario::factory()->demandante()->create();

        $this->actingAs($demandante)->post('/demandas', [
            'projeto_base_id' => $this->projeto->id,
            'titulo' => 'Projetor queimado',
            'descricao' => 'Não liga.',
            'justificativa' => 'Aulas paradas.',
            'publico_afetado' => '120 alunos',
        ])->assertRedirect();

        Notification::assertSentTo($this->coordenador, Aviso::class, fn (Aviso $aviso) => $aviso->assunto === 'Nova demanda: Projetor queimado');
        Notification::assertNotSentTo($demandante, Aviso::class);
    }

    public function test_demanda_sem_coordenador_avisa_os_admins(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $semCoordenador = ProjetoBase::factory()->create(['coordenador_id' => null]);

        Solicitacao::factory()->create(['projeto_base_id' => $semCoordenador->id]);

        Notification::assertSentTo($admin, Aviso::class);
    }

    public function test_mudanca_de_status_avisa_o_demandante_e_a_equipe_mas_nao_quem_mudou(): void
    {
        $this->entrega();

        $this->actingAs($this->coordenador)->patch("/demandas/{$this->solicitacao->id}/status", ['status' => 'em_validacao'])->assertRedirect();

        $assunto = fn (Aviso $aviso) => $aviso->assunto === "Demanda \"{$this->solicitacao->titulo}\": Em Validação";
        Notification::assertSentTo($this->demandante, Aviso::class, $assunto);
        Notification::assertSentTo($this->estudante, Aviso::class, $assunto);
        Notification::assertNotSentTo($this->coordenador, Aviso::class, $assunto);
    }

    public function test_mensagem_no_chat_avisa_os_outros_participantes(): void
    {
        $this->actingAs($this->demandante)->post("/demandas/{$this->solicitacao->id}/mensagens", ['texto' => 'Quando começa a instalação?'])->assertRedirect();

        $temOTexto = fn (Aviso $aviso) => in_array('Quando começa a instalação?', $aviso->linhas, true);
        Notification::assertSentTo($this->coordenador, Aviso::class, $temOTexto);
        Notification::assertSentTo($this->estudante, Aviso::class, $temOTexto);
        Notification::assertNotSentTo($this->demandante, Aviso::class);
    }

    public function test_candidatura_avisa_a_coordenacao_e_depois_o_estudante(): void
    {
        $candidato = Usuario::factory()->estudante()->create();
        $subprojeto = Subprojeto::factory()->create([
            'solicitacao_id' => Solicitacao::factory()->triada()->comStatus(StatusDemanda::Priorizada)->create(['projeto_base_id' => $this->projeto->id])->id,
        ]);

        $this->actingAs($candidato)->post("/oportunidades/{$subprojeto->id}/candidatura")->assertRedirect();
        Notification::assertSentTo($this->coordenador, Aviso::class, fn (Aviso $aviso) => str_starts_with($aviso->assunto, 'Nova candidatura'));

        $candidatura = Candidatura::where('estudante_id', $candidato->id)->sole();
        $this->actingAs($this->coordenador)->patch("/subprojetos/{$subprojeto->id}/candidaturas/{$candidatura->id}/aprovar", [
            'papel_id' => PapelEquipe::where('nome', PapelEquipe::PADRAO)->firstOrFail()->id,
        ])->assertRedirect();

        Notification::assertSentTo($candidato, Aviso::class, fn (Aviso $aviso) => str_starts_with($aviso->assunto, 'Candidatura aprovada'));
    }

    public function test_candidatura_recusada_avisa_o_estudante(): void
    {
        $candidato = Usuario::factory()->estudante()->create();
        $candidatura = Candidatura::create(['subprojeto_id' => $this->subprojeto->id, 'estudante_id' => $candidato->id]);

        $this->actingAs($this->coordenador)->patch("/subprojetos/{$this->subprojeto->id}/candidaturas/{$candidatura->id}/recusar")->assertRedirect();

        Notification::assertSentTo($candidato, Aviso::class, fn (Aviso $aviso) => str_starts_with($aviso->assunto, 'Candidatura recusada'));
    }

    public function test_entrega_enviada_avisa_a_coordenacao(): void
    {
        $this->actingAs($this->estudante);
        $this->entrega();

        Notification::assertSentTo($this->coordenador, Aviso::class, fn (Aviso $aviso) => str_starts_with($aviso->assunto, 'Nova entrega para validar'));
        Notification::assertNotSentTo($this->estudante, Aviso::class);
    }

    public function test_aprovacao_tecnica_chama_o_demandante_para_o_aceite(): void
    {
        $entrega = $this->entrega();

        $this->actingAs($this->coordenador)->post("/subprojetos/{$this->subprojeto->id}/entregas/{$entrega->id}/validacao", ['decisao' => 'aprovada'])->assertRedirect();

        Notification::assertSentTo($this->demandante, Aviso::class, fn (Aviso $aviso) => in_array('A entrega aguarda o aceite do demandante.', $aviso->linhas, true));
        Notification::assertSentTo($this->estudante, Aviso::class);
    }

    public function test_entrega_devolvida_avisa_so_a_equipe_com_o_comentario(): void
    {
        $entrega = $this->entrega();

        $this->actingAs($this->coordenador)->post("/subprojetos/{$this->subprojeto->id}/entregas/{$entrega->id}/validacao", [
            'decisao' => 'devolvida',
            'feedback' => 'Falta o manual.',
        ])->assertRedirect();

        Notification::assertSentTo($this->estudante, Aviso::class, fn (Aviso $aviso) => in_array('Comentário: Falta o manual.', $aviso->linhas, true));
        Notification::assertNotSentTo($this->demandante, Aviso::class);
    }

    public function test_aceite_avisa_a_equipe_e_a_coordenacao(): void
    {
        $entrega = $this->entrega(SituacaoEntrega::AguardandoAceite);

        $this->actingAs($this->demandante)->post("/subprojetos/{$this->subprojeto->id}/entregas/{$entrega->id}/aceite", ['decisao' => 'aceita'])->assertRedirect();

        $aceita = fn (Aviso $aviso) => str_starts_with($aviso->assunto, 'Solução aceita');
        Notification::assertSentTo($this->estudante, Aviso::class, $aceita);
        Notification::assertSentTo($this->coordenador, Aviso::class, $aceita);
    }

    public function test_contas_inativas_nao_recebem_avisos(): void
    {
        $this->demandante->update(['status' => 'inativo']);

        $this->actingAs($this->coordenador)->post("/demandas/{$this->solicitacao->id}/mensagens", ['texto' => 'Olá']);

        Notification::assertNotSentTo($this->demandante, Aviso::class);
        Notification::assertSentTo($this->estudante, Aviso::class);
    }

    public function test_admin_pode_desligar_os_avisos(): void
    {
        Parametro::salvar(['notificacoes_email' => '0'], Usuario::factory()->admin()->create());

        $this->actingAs($this->demandante)->post("/demandas/{$this->solicitacao->id}/mensagens", ['texto' => 'Olá']);

        Notification::assertNothingSent();
    }

    public function test_e_mail_traz_o_botao_e_a_assinatura_da_rede(): void
    {
        Parametro::salvar(['email_contato' => 'contato@ufes.br'], Usuario::factory()->admin()->create());

        $email = (new Aviso('Assunto', ['Linha 1'], 'Ver a demanda', 'http://localhost/demandas/1'))->toMail($this->demandante);

        $this->assertSame('Assunto', $email->subject);
        $this->assertSame('Ver a demanda', $email->actionText);
        $this->assertSame(Parametro::valor('nome_rede'), $email->salutation);
        $this->assertContains('Dúvidas: contato@ufes.br.', $email->outroLines);
    }
}
