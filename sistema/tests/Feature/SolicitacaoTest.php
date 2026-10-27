<?php

namespace Tests\Feature;

use App\Enums\Complexidade;
use App\Enums\StatusDemanda;
use App\Models\Anexo;
use App\Models\CategoriaDemanda;
use App\Models\Prioridade;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SolicitacaoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $demandante;

    private Usuario $coordenador;

    private ProjetoBase $projeto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->demandante = Usuario::factory()->demandante()->create();
        $this->coordenador = Usuario::factory()->coordenador()->create();
        $this->projeto = ProjetoBase::factory()->create(['coordenador_id' => $this->coordenador->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosValidos(array $extra = []): array
    {
        return array_merge([
            'projeto_base_id' => $this->projeto->id,
            'titulo' => 'Laboratório de informática sem internet',
            'descricao' => 'Os 20 computadores do laboratório não acessam a internet desde março.',
            'justificativa' => 'As aulas de pesquisa estão paradas.',
            'publico_afetado' => '300 alunos do ensino fundamental',
        ], $extra);
    }

    private function demandaDoProjeto(array $atributos = []): Solicitacao
    {
        return Solicitacao::factory()->create([
            'projeto_base_id' => $this->projeto->id,
            'demandante_id' => $this->demandante->id,
            ...$atributos,
        ]);
    }

    private function classificar(Solicitacao $solicitacao): void
    {
        $solicitacao->update([
            'categoria_id' => CategoriaDemanda::create(['nome' => 'Infraestrutura de Rede'])->id,
            'prioridade_id' => Prioridade::create(['nome' => 'Alta', 'ordem' => 2])->id,
            'complexidade' => Complexidade::Media,
        ]);
    }

    public function test_demandante_abre_demanda_com_anexo(): void
    {
        $this->actingAs($this->demandante)->get('/demandas/create')->assertOk()->assertSee($this->projeto->titulo);

        $resposta = $this->actingAs($this->demandante)->post('/demandas', $this->dadosValidos([
            'anexos' => [
                UploadedFile::fake()->create('oficio.pdf', 300, 'application/pdf'),
                UploadedFile::fake()->image('laboratorio.jpg'),
            ],
        ]));

        $solicitacao = Solicitacao::firstOrFail();
        $resposta->assertRedirect("/demandas/{$solicitacao->id}");
        $this->assertSame(StatusDemanda::Cadastrada, $solicitacao->status);
        $this->assertSame($this->demandante->id, $solicitacao->demandante_id);
        $this->assertSame($this->demandante->demandante->escola_instituicao_id, $solicitacao->escola_instituicao_id);

        $this->assertCount(2, $solicitacao->anexos);
        $anexo = $solicitacao->anexos->firstWhere('nome_original', 'oficio.pdf');
        $this->assertStringStartsWith("solicitacoes/{$solicitacao->id}/", $anexo->caminho);
        $this->assertSame($this->demandante->id, $anexo->enviado_por);
        Storage::disk('local')->assertExists($anexo->caminho);
    }

    public function test_anexos_sao_opcionais(): void
    {
        $this->actingAs($this->demandante)->post('/demandas', $this->dadosValidos())->assertSessionHasNoErrors();

        $this->assertDatabaseCount('solicitacoes', 1);
        $this->assertDatabaseCount('anexos', 0);
    }

    public function test_valida_campos_obrigatorios_e_titulo_de_ate_100_caracteres(): void
    {
        $this->actingAs($this->demandante)
            ->post('/demandas', $this->dadosValidos([
                'titulo' => str_repeat('a', 101),
                'descricao' => '',
                'justificativa' => '',
                'publico_afetado' => '',
            ]))
            ->assertSessionHasErrors(['titulo', 'descricao', 'justificativa', 'publico_afetado']);

        $this->assertDatabaseCount('solicitacoes', 0);
    }

    public function test_recusa_tipo_de_arquivo_nao_permitido_e_arquivo_maior_que_5_mb(): void
    {
        $this->actingAs($this->demandante)
            ->post('/demandas', $this->dadosValidos([
                'anexos' => [
                    UploadedFile::fake()->create('planilha.xlsx', 10),
                    UploadedFile::fake()->create('grande.pdf', 5121, 'application/pdf'),
                ],
            ]))
            ->assertSessionHasErrors([
                'anexos.0' => 'O arquivo "planilha.xlsx" não é PDF, JPG ou PNG.',
                'anexos.1' => 'O arquivo "grande.pdf" tem mais de 5 MB.',
            ]);

        $this->assertDatabaseCount('solicitacoes', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_limita_a_quantidade_de_anexos(): void
    {
        $arquivos = collect(range(1, Anexo::quantidadeMaxima() + 1))
            ->map(fn (int $numero) => UploadedFile::fake()->create("doc{$numero}.pdf", 10, 'application/pdf'))
            ->all();

        $this->actingAs($this->demandante)
            ->post('/demandas', $this->dadosValidos(['anexos' => $arquivos]))
            ->assertSessionHasErrors('anexos');

        $this->assertDatabaseCount('solicitacoes', 0);
    }

    public function test_nao_aceita_projeto_inativo(): void
    {
        $inativo = ProjetoBase::factory()->inativo()->create();

        $this->actingAs($this->demandante)
            ->post('/demandas', $this->dadosValidos(['projeto_base_id' => $inativo->id]))
            ->assertSessionHasErrors('projeto_base_id');
    }

    public function test_so_o_demandante_abre_demandas(): void
    {
        $estudante = Usuario::factory()->estudante()->create();

        $this->actingAs($this->coordenador)->get('/demandas/create')->assertForbidden();
        $this->actingAs($this->coordenador)->post('/demandas', $this->dadosValidos())->assertForbidden();
        $this->actingAs($estudante)->get('/demandas')->assertForbidden();

        $this->assertDatabaseCount('solicitacoes', 0);
    }

    public function test_minhas_demandas_mostra_so_as_do_demandante(): void
    {
        $this->demandaDoProjeto(['titulo' => 'Minha demanda']);
        Solicitacao::factory()->create(['titulo' => 'Demanda de outra escola']);

        $this->actingAs($this->demandante)->get('/demandas')
            ->assertOk()
            ->assertSee('Minha demanda')
            ->assertDontSee('Demanda de outra escola');
    }

    public function test_fila_do_coordenador_mostra_as_novas_dos_projetos_que_ele_coordena(): void
    {
        $this->demandaDoProjeto(['titulo' => 'Nova do meu projeto']);
        $this->demandaDoProjeto(['titulo' => 'Já em análise', 'status' => StatusDemanda::EmAnalise]);
        Solicitacao::factory()->create(['titulo' => 'Nova de outro projeto']);

        $this->actingAs($this->coordenador)->get('/demandas')
            ->assertOk()
            ->assertSee('Nova do meu projeto')
            ->assertDontSee('Já em análise')
            ->assertDontSee('Nova de outro projeto');

        $this->actingAs($this->coordenador)->get('/demandas?status=')
            ->assertSee('Nova do meu projeto')
            ->assertSee('Já em análise')
            ->assertDontSee('Nova de outro projeto');
    }

    public function test_admin_ve_as_demandas_de_todos_os_projetos(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $this->demandaDoProjeto(['titulo' => 'Nova do projeto A']);
        Solicitacao::factory()->create(['titulo' => 'Nova do projeto B']);

        $this->actingAs($admin)->get('/demandas')
            ->assertOk()
            ->assertSee('Nova do projeto A')
            ->assertSee('Nova do projeto B');
    }

    public function test_fila_ordena_pela_prioridade_e_depois_pela_mais_antiga(): void
    {
        $urgente = Prioridade::create(['nome' => 'Urgente', 'ordem' => 1]);
        $baixa = Prioridade::create(['nome' => 'Baixa', 'ordem' => 4]);

        $this->demandaDoProjeto(['titulo' => 'Sem prioridade', 'created_at' => now()->subDays(5)]);
        $this->demandaDoProjeto(['titulo' => 'Baixa antiga', 'prioridade_id' => $baixa->id, 'created_at' => now()->subDays(3)]);
        $this->demandaDoProjeto(['titulo' => 'Urgente recente', 'prioridade_id' => $urgente->id, 'created_at' => now()->subDay()]);

        $this->actingAs($this->coordenador)->get('/demandas')
            ->assertSeeInOrder(['Urgente recente', 'Baixa antiga', 'Sem prioridade']);
    }

    public function test_detalhes_so_para_quem_pode_ver_a_demanda(): void
    {
        $solicitacao = $this->demandaDoProjeto();
        $outroDemandante = Usuario::factory()->demandante()->create();
        $outroCoordenador = Usuario::factory()->coordenador()->create();

        $this->actingAs($this->demandante)->get("/demandas/{$solicitacao->id}")->assertOk()->assertDontSee('Salvar classificação');
        $this->actingAs($this->coordenador)->get("/demandas/{$solicitacao->id}")->assertOk()->assertSee('Salvar classificação');
        $this->actingAs($outroDemandante)->get("/demandas/{$solicitacao->id}")->assertForbidden();
        $this->actingAs($outroCoordenador)->get("/demandas/{$solicitacao->id}")->assertForbidden();
    }

    public function test_download_do_anexo_e_autorizado(): void
    {
        $this->actingAs($this->demandante)->post('/demandas', $this->dadosValidos([
            'anexos' => [UploadedFile::fake()->create('oficio.pdf', 50, 'application/pdf')],
        ]));
        $solicitacao = Solicitacao::firstOrFail();
        $anexo = $solicitacao->anexos->first();
        $url = "/demandas/{$solicitacao->id}/anexos/{$anexo->id}";

        $this->actingAs($this->demandante)->get($url)->assertOk()->assertDownload('oficio.pdf');
        $this->actingAs($this->coordenador)->get($url)->assertOk();
        $this->actingAs(Usuario::factory()->demandante()->create())->get($url)->assertForbidden();

        // O anexo precisa pertencer à demanda da URL.
        $outra = $this->demandaDoProjeto();
        $this->actingAs($this->demandante)->get("/demandas/{$outra->id}/anexos/{$anexo->id}")->assertNotFound();
    }

    public function test_coordenador_classifica_a_demanda(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::EmAnalise]);
        $categoria = CategoriaDemanda::create(['nome' => 'Infraestrutura de Rede']);
        $prioridade = Prioridade::create(['nome' => 'Alta', 'ordem' => 2]);

        $this->actingAs($this->coordenador)
            ->patch("/demandas/{$solicitacao->id}/triagem", [
                'categoria_id' => $categoria->id,
                'prioridade_id' => $prioridade->id,
                'complexidade' => 'alta',
            ])
            ->assertRedirect("/demandas/{$solicitacao->id}")
            ->assertSessionHasNoErrors();

        $solicitacao->refresh();
        $this->assertSame($categoria->id, $solicitacao->categoria_id);
        $this->assertSame($prioridade->id, $solicitacao->prioridade_id);
        $this->assertSame(Complexidade::Alta, $solicitacao->complexidade);
        $this->assertSame($this->coordenador->id, $solicitacao->triado_por);
        $this->assertNotNull($solicitacao->triado_em);
        // A triagem não muda o status sozinha.
        $this->assertSame(StatusDemanda::EmAnalise, $solicitacao->status);
    }

    public function test_triagem_exige_os_tres_campos_e_itens_ativos(): void
    {
        $solicitacao = $this->demandaDoProjeto();
        $inativa = CategoriaDemanda::create(['nome' => 'Antiga', 'ativo' => false]);

        $this->actingAs($this->coordenador)
            ->patch("/demandas/{$solicitacao->id}/triagem", ['categoria_id' => $inativa->id, 'complexidade' => 'enorme'])
            ->assertSessionHasErrors(['categoria_id', 'prioridade_id', 'complexidade']);
    }

    public function test_so_o_coordenador_do_projeto_e_o_admin_classificam(): void
    {
        $solicitacao = $this->demandaDoProjeto();
        $dados = [
            'categoria_id' => CategoriaDemanda::create(['nome' => 'Software'])->id,
            'prioridade_id' => Prioridade::create(['nome' => 'Média', 'ordem' => 3])->id,
            'complexidade' => 'baixa',
        ];

        $this->actingAs($this->demandante)->patch("/demandas/{$solicitacao->id}/triagem", $dados)->assertForbidden();
        $this->actingAs(Usuario::factory()->coordenador()->create())->patch("/demandas/{$solicitacao->id}/triagem", $dados)->assertForbidden();
        $this->actingAs(Usuario::factory()->admin()->create())->patch("/demandas/{$solicitacao->id}/triagem", $dados)->assertSessionHasNoErrors();
    }

    public function test_triagem_fica_bloqueada_depois_que_a_demanda_sai_da_analise(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::Recusada, 'motivo_status' => 'Fora do escopo']);

        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/triagem", [])->assertForbidden();
    }

    public function test_coordenador_conduz_cadastrada_em_analise_e_priorizada(): void
    {
        $solicitacao = $this->demandaDoProjeto();

        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'em_analise'])->assertSessionHasNoErrors();
        $this->assertSame(StatusDemanda::EmAnalise, $solicitacao->refresh()->status);

        $this->classificar($solicitacao);

        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'priorizada'])->assertSessionHasNoErrors();
        $this->assertSame(StatusDemanda::Priorizada, $solicitacao->refresh()->status);
    }

    public function test_nao_prioriza_sem_triagem(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::EmAnalise]);

        $this->actingAs($this->coordenador)
            ->patch("/demandas/{$solicitacao->id}/status", ['status' => 'priorizada'])
            ->assertSessionHasErrors('status');

        $this->assertSame(StatusDemanda::EmAnalise, $solicitacao->refresh()->status);
    }

    public function test_nao_pula_etapas_da_triagem(): void
    {
        $solicitacao = $this->demandaDoProjeto();
        $this->classificar($solicitacao);

        $this->actingAs($this->coordenador)
            ->patch("/demandas/{$solicitacao->id}/status", ['status' => 'priorizada'])
            ->assertSessionHasErrors('status');

        $this->actingAs($this->coordenador)
            ->patch("/demandas/{$solicitacao->id}/status", ['status' => 'concluida'])
            ->assertSessionHasErrors('status');

        $this->assertSame(StatusDemanda::Cadastrada, $solicitacao->refresh()->status);
    }

    public function test_recusar_exige_motivo(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::EmAnalise]);

        $this->actingAs($this->coordenador)
            ->patch("/demandas/{$solicitacao->id}/status", ['status' => 'recusada', 'motivo_status' => '  '])
            ->assertSessionHasErrors('motivo_status');

        $this->actingAs($this->coordenador)
            ->patch("/demandas/{$solicitacao->id}/status", ['status' => 'recusada', 'motivo_status' => 'Fora do escopo do projeto.'])
            ->assertSessionHasNoErrors();

        $solicitacao->refresh();
        $this->assertSame(StatusDemanda::Recusada, $solicitacao->status);
        $this->assertSame('Fora do escopo do projeto.', $solicitacao->motivo_status);

        $this->actingAs($this->demandante)->get("/demandas/{$solicitacao->id}")->assertSee('Fora do escopo do projeto.');
    }

    public function test_demandante_cancela_antes_do_desenvolvimento(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::EmAnalise]);

        $this->actingAs($this->demandante)
            ->patch("/demandas/{$solicitacao->id}/status", ['status' => 'em_analise'])
            ->assertSessionHasErrors('status');

        $this->actingAs($this->demandante)
            ->patch("/demandas/{$solicitacao->id}/status", ['status' => 'cancelada', 'motivo_status' => 'Resolvemos com a prefeitura.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(StatusDemanda::Cancelada, $solicitacao->refresh()->status);
    }

    public function test_demandante_nao_cancela_em_desenvolvimento_mas_o_admin_cancela(): void
    {
        $solicitacao = $this->demandaDoProjeto(['status' => StatusDemanda::EmDesenvolvimento]);

        $this->actingAs($this->demandante)
            ->patch("/demandas/{$solicitacao->id}/status", ['status' => 'cancelada', 'motivo_status' => 'Não precisa mais.'])
            ->assertForbidden();

        $this->actingAs(Usuario::factory()->admin()->create())
            ->patch("/demandas/{$solicitacao->id}/status", ['status' => 'cancelada', 'motivo_status' => 'Escola fechada.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(StatusDemanda::Cancelada, $solicitacao->refresh()->status);
    }

    public function test_demandante_abre_e_coordenador_classifica_e_prioriza(): void
    {
        $categoria = CategoriaDemanda::create(['nome' => 'Infraestrutura de Rede']);
        $prioridade = Prioridade::create(['nome' => 'Urgente', 'ordem' => 1]);

        $this->actingAs($this->demandante)->post('/demandas', $this->dadosValidos([
            'anexos' => [UploadedFile::fake()->image('rack.png')],
        ]));
        $solicitacao = Solicitacao::firstOrFail();

        $this->actingAs($this->coordenador)->get('/demandas')->assertSee($solicitacao->titulo);
        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'em_analise']);
        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/triagem", [
            'categoria_id' => $categoria->id,
            'prioridade_id' => $prioridade->id,
            'complexidade' => 'media',
        ]);
        $this->actingAs($this->coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'priorizada']);

        $this->actingAs($this->demandante)->get("/demandas/{$solicitacao->id}")
            ->assertOk()
            ->assertSee('Priorizada')
            ->assertSee('Infraestrutura de Rede')
            ->assertSee('Urgente')
            ->assertSee('rack.png');
    }
}
