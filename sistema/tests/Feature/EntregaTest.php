<?php

namespace Tests\Feature;

use App\Enums\SituacaoEntrega;
use App\Enums\StatusDemanda;
use App\Enums\TipoEntrega;
use App\Enums\TipoHistorico;
use App\Models\Entrega;
use App\Models\MembroEquipe;
use App\Models\PapelEquipe;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * RF12: envio das entregas pela equipe do sub-projeto.
 */
class EntregaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $coordenador;

    private Usuario $estudante;

    private Subprojeto $subprojeto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->coordenador = Usuario::factory()->coordenador()->create();
        $this->estudante = Usuario::factory()->estudante()->create();

        $projeto = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id]);
        $solicitacao = Solicitacao::factory()->triada()->comStatus(StatusDemanda::EmDesenvolvimento)->create(['projeto_base_id' => $projeto->id]);
        $this->subprojeto = Subprojeto::factory()->create(['solicitacao_id' => $solicitacao->id]);

        $this->membro($this->estudante);
    }

    private function membro(Usuario $estudante): MembroEquipe
    {
        return MembroEquipe::create([
            'subprojeto_id' => $this->subprojeto->id,
            'estudante_id' => $estudante->id,
            'papel_id' => PapelEquipe::where('nome', PapelEquipe::PADRAO)->firstOrFail()->id,
        ]);
    }

    private function enviar(array $dados, ?Usuario $usuario = null)
    {
        return $this->actingAs($usuario ?? $this->estudante)->post("/subprojetos/{$this->subprojeto->id}/entregas", [
            'tipo' => 'link',
            'titulo' => 'Primeira versão do site',
            'descricao' => 'Páginas de notícias e calendário.',
            'url' => 'https://github.com/rede-apoio/site-escola',
            ...$dados,
        ]);
    }

    public function test_membro_envia_entrega_do_tipo_link_e_ela_vai_para_a_linha_do_tempo(): void
    {
        $this->enviar([])->assertRedirect()->assertSessionHas('sucesso');

        $entrega = $this->subprojeto->entregas()->sole();
        $this->assertSame(TipoEntrega::Link, $entrega->tipo);
        $this->assertSame(SituacaoEntrega::AguardandoTecnica, $entrega->situacao);
        $this->assertSame($this->estudante->id, $entrega->enviado_por);
        $this->assertSame('https://github.com/rede-apoio/site-escola', $entrega->url);

        $historico = $this->subprojeto->solicitacao->historicos()->where('tipo', TipoHistorico::Entrega)->sole();
        $this->assertSame(1, $entrega->numero);
        $this->assertSame('Entrega nº 1 enviada: Primeira versão do site', $historico->descricao);
        $this->assertSame($this->estudante->id, $historico->usuario_id);

        // O envio não muda o status da demanda (decisão da Fase 0).
        $this->assertSame(StatusDemanda::EmDesenvolvimento, $this->subprojeto->solicitacao->fresh()->status);
    }

    public function test_link_e_obrigatorio_e_precisa_ser_um_endereco_valido(): void
    {
        $this->enviar(['url' => ''])->assertSessionHasErrors('url');
        $this->enviar(['url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
        $this->enviar(['url' => 'site da escola'])->assertSessionHasErrors('url');

        $this->assertSame(0, Entrega::count());
    }

    public function test_entrega_de_arquivos_guarda_ate_cinco_arquivos_fora_da_pasta_publica(): void
    {
        $this->enviar([
            'tipo' => 'arquivos',
            'url' => 'https://ignorado.example',
            'arquivos' => [
                UploadedFile::fake()->image('tela-inicial.png'),
                UploadedFile::fake()->create('codigo.zip', 500, 'application/zip'),
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $entrega = Entrega::sole();
        $this->assertNull($entrega->url);
        $this->assertCount(2, $entrega->arquivos);
        $this->assertSame('tela-inicial.png', $entrega->arquivos[0]->nome_original);
        Storage::disk('local')->assertExists($entrega->arquivos[0]->caminho);
        $this->assertStringStartsWith("entregas/{$entrega->id}/", $entrega->arquivos[0]->caminho);
    }

    public function test_tipos_de_arquivo_quantidade_e_tamanho_sao_conferidos(): void
    {
        $this->enviar(['tipo' => 'arquivos', 'arquivos' => []])->assertSessionHasErrors('arquivos');
        $this->enviar(['tipo' => 'arquivos', 'arquivos' => [UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors('arquivos.0');
        $this->enviar(['tipo' => 'arquivos', 'arquivos' => [UploadedFile::fake()->create('grande.pdf', 20481, 'application/pdf')]])->assertSessionHasErrors('arquivos.0');
        $this->enviar(['tipo' => 'arquivos', 'arquivos' => array_map(
            fn (int $numero) => UploadedFile::fake()->create("foto{$numero}.jpg", 10, 'image/jpeg'),
            range(1, 6),
        )])->assertSessionHasErrors('arquivos');

        // O relatório técnico aceita só PDF, DOCX ou Excel.
        $this->enviar(['tipo' => 'relatorio', 'arquivos' => [UploadedFile::fake()->image('foto.jpg')]])->assertSessionHasErrors('arquivos.0');
        $this->enviar(['tipo' => 'relatorio', 'arquivos' => [UploadedFile::fake()->create('codigo.zip', 100, 'application/zip')]])->assertSessionHasErrors('arquivos.0');
        $this->enviar(['tipo' => 'relatorio', 'arquivos' => [UploadedFile::fake()->create('relatorio.pdf', 100, 'application/pdf')]])->assertSessionHasNoErrors();

        $this->assertSame(1, Entrega::count());
        $this->assertSame(TipoEntrega::Relatorio, Entrega::sole()->tipo);
    }

    public function test_relatorio_tecnico_aceita_planilha_do_excel(): void
    {
        $this->enviar(['tipo' => 'relatorio', 'arquivos' => [
            UploadedFile::fake()->createWithContent('custos.xlsx', file_get_contents(base_path('tests/Fixtures/planilha.xlsx'))),
        ]])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(TipoEntrega::Relatorio, Entrega::sole()->tipo);
        $this->assertSame('custos.xlsx', Entrega::sole()->arquivos()->sole()->nome_original);
    }

    public function test_arquivos_aceitam_planilhas_do_excel(): void
    {
        $this->enviar(['tipo' => 'arquivos', 'arquivos' => [
            UploadedFile::fake()->createWithContent('inventario.xlsx', file_get_contents(base_path('tests/Fixtures/planilha.xlsx'))),
        ]])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('inventario.xlsx', Entrega::sole()->arquivos()->sole()->nome_original);
    }

    public function test_so_membros_ativos_e_a_coordenacao_enviam_entregas(): void
    {
        $outroEstudante = Usuario::factory()->estudante()->create();
        $removido = Usuario::factory()->estudante()->create();
        $this->membro($removido)->update(['removido_em' => now()]);

        $this->enviar([], $outroEstudante)->assertForbidden();
        $this->enviar([], $removido)->assertForbidden();
        $this->enviar([], $this->subprojeto->solicitacao->demandante)->assertForbidden();
        $this->enviar([], Usuario::factory()->coordenador()->create())->assertForbidden();

        $this->assertSame(0, Entrega::count());
    }

    public function test_coordenador_e_admin_registram_entrega_em_nome_da_equipe(): void
    {
        $this->actingAs($this->coordenador)->get("/subprojetos/{$this->subprojeto->id}")
            ->assertOk()
            ->assertSee('Registrar entrega da equipe');
        $this->actingAs($this->coordenador)->get("/subprojetos/{$this->subprojeto->id}/entregas/criar")
            ->assertOk()
            ->assertSee('em nome da equipe');

        $this->enviar(['titulo' => 'Material recebido por e-mail'], $this->coordenador)
            ->assertRedirect()
            ->assertSessionHas('sucesso', fn (string $mensagem) => str_contains($mensagem, 'em nome da equipe'));
        $this->enviar(['titulo' => 'Fotos da instalação'], Usuario::factory()->admin()->create())->assertRedirect();

        $entrega = Entrega::where('titulo', 'Material recebido por e-mail')->sole();
        $this->assertSame($this->coordenador->id, $entrega->enviado_por);
        $this->assertSame(SituacaoEntrega::AguardandoTecnica, $entrega->situacao);
        $this->assertTrue($entrega->enviadaPelaCoordenacao());

        $historico = $this->subprojeto->solicitacao->historicos()->where('tipo', TipoHistorico::Entrega)->first();
        $this->assertSame('Entrega nº 1 enviada: Material recebido por e-mail (enviada pela coordenação em nome da equipe)', $historico->descricao);
        $this->assertTrue($historico->dados['em_nome_da_equipe']);

        // A entrega do próprio membro não leva a observação.
        $this->enviar(['titulo' => 'Versão da equipe']);
        $this->assertStringNotContainsString('em nome da equipe', $this->subprojeto->solicitacao->historicos()->where('descricao', 'like', '%Versão da equipe%')->sole()->descricao);
    }

    public function test_entregas_comecam_com_a_demanda_em_desenvolvimento(): void
    {
        $this->subprojeto->solicitacao->update(['status' => StatusDemanda::Priorizada]);

        $this->actingAs($this->estudante)->get("/subprojetos/{$this->subprojeto->id}/entregas/criar")
            ->assertRedirect("/subprojetos/{$this->subprojeto->id}")
            ->assertSessionHasErrors('entrega');
        $this->enviar([])->assertForbidden();

        $this->subprojeto->solicitacao->update(['status' => StatusDemanda::Concluida]);
        $this->enviar([])->assertForbidden();
    }

    public function test_varias_entregas_ao_mesmo_tempo_numeradas_na_ordem_de_envio(): void
    {
        $this->enviar(['titulo' => 'Cabeamento'])->assertRedirect();
        $this->enviar(['titulo' => 'Roteador configurado'])->assertRedirect()->assertSessionHasNoErrors();
        $this->enviar(['titulo' => 'Manual do laboratório'], $this->coordenador)->assertRedirect();

        $this->assertSame(
            ['Cabeamento' => 1, 'Roteador configurado' => 2, 'Manual do laboratório' => 3],
            $this->subprojeto->entregas()->pluck('numero', 'titulo')->all(),
        );
        $this->assertSame(3, Entrega::where('situacao', SituacaoEntrega::AguardandoTecnica)->count());

        // Mesmo com uma entrega aceita, a equipe continua enviando enquanto a demanda está em execução.
        Entrega::where('numero', 1)->update(['situacao' => SituacaoEntrega::Aceita]);
        $this->enviar(['titulo' => 'Ajuste final'])->assertRedirect();
        $this->assertSame(4, Entrega::where('titulo', 'Ajuste final')->sole()->numero);

        $this->actingAs($this->estudante)->get("/subprojetos/{$this->subprojeto->id}")
            ->assertOk()
            ->assertSeeInOrder(['nº 1', 'Cabeamento', 'nº 2', 'Roteador configurado', 'nº 3', 'Manual do laboratório', 'nº 4', 'Ajuste final']);
    }

    public function test_formulario_mostra_o_que_a_coordenacao_pediu_para_ajustar(): void
    {
        $entrega = Entrega::factory()->create(['subprojeto_id' => $this->subprojeto->id, 'enviado_por' => $this->estudante->id, 'titulo' => 'Versão 1']);
        $this->actingAs($this->coordenador)->post("/subprojetos/{$this->subprojeto->id}/entregas/{$entrega->id}/validacao", [
            'decisao' => 'devolvida',
            'feedback' => 'O calendário não abre no celular.',
        ]);

        $this->actingAs($this->estudante)->get("/subprojetos/{$this->subprojeto->id}/entregas/criar")
            ->assertOk()
            ->assertSee('Devolvida para ajustes')
            ->assertSee('O calendário não abre no celular.');
    }

    public function test_download_dos_arquivos_so_para_quem_ve_a_entrega(): void
    {
        $this->enviar(['tipo' => 'arquivos', 'arquivos' => [UploadedFile::fake()->create('manual.pdf', 50, 'application/pdf')]]);
        $entrega = Entrega::sole();
        $arquivo = $entrega->arquivos()->sole();
        $url = "/subprojetos/{$this->subprojeto->id}/entregas/{$entrega->id}/arquivos/{$arquivo->id}";
        $demandante = $this->subprojeto->solicitacao->demandante;

        $this->actingAs($this->estudante)->get($url)->assertOk()->assertDownload('manual.pdf');
        $this->actingAs($this->coordenador)->get($url)->assertOk();
        $this->actingAs(Usuario::factory()->estudante()->create())->get($url)->assertForbidden();

        // O demandante só vê a entrega depois da aprovação técnica.
        $this->actingAs($demandante)->get($url)->assertForbidden();
        $this->actingAs($demandante)->get("/subprojetos/{$this->subprojeto->id}/entregas/{$entrega->id}")->assertForbidden();
        $this->actingAs($demandante)->get("/subprojetos/{$this->subprojeto->id}")->assertOk()->assertDontSee('manual.pdf')->assertDontSee($entrega->titulo);

        $entrega->update(['situacao' => SituacaoEntrega::AguardandoAceite]);
        $this->actingAs($demandante)->get($url)->assertOk();
    }

    public function test_arquivo_de_outra_entrega_nao_e_baixado_pela_url_desta(): void
    {
        $this->enviar(['tipo' => 'arquivos', 'arquivos' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]]);
        $primeira = Entrega::sole();
        $this->enviar(['tipo' => 'link', 'titulo' => 'Segunda']);
        $segunda = Entrega::where('titulo', 'Segunda')->sole();

        $this->actingAs($this->estudante)
            ->get("/subprojetos/{$this->subprojeto->id}/entregas/{$segunda->id}/arquivos/{$primeira->arquivos()->sole()->id}")
            ->assertNotFound();
    }
}
