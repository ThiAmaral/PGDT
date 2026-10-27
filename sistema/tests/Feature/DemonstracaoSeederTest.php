<?php

namespace Tests\Feature;

use App\Enums\SituacaoEntrega;
use App\Enums\StatusDemanda;
use App\Enums\StatusUsuario;
use App\Models\CategoriaDemanda;
use App\Models\Entrega;
use App\Models\Historico;
use App\Models\PapelEquipe;
use App\Models\Prioridade;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Database\Seeders\DemonstracaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 8: dados de demonstração para a apresentação e os testes com usuários.
 */
class DemonstracaoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['pgdt.demonstracao.senha' => 'demo12345']);
    }

    public function test_cria_uma_demanda_em_cada_status_com_contas_de_todos_os_perfis(): void
    {
        $this->seed(DemonstracaoSeeder::class);

        $this->assertEqualsCanonicalizing(
            array_column(StatusDemanda::cases(), 'value'),
            Solicitacao::query()->distinct()->pluck('status')->map->value->all(),
        );

        $contas = Usuario::where('email', 'like', '%@'.DemonstracaoSeeder::DOMINIO)->get();
        $this->assertEqualsCanonicalizing(['coordenador', 'demandante', 'estudante'], $contas->pluck('perfil')->map->value->unique()->values()->all());
        $this->assertTrue($contas->where('status', StatusUsuario::Pendente)->count() >= 2, 'A fila de aprovação do admin deve ter cadastros.');
        $this->assertTrue(Hash::check('demo12345', $contas->first()->password));

        // Entregas em todas as situações e arquivos que abrem de verdade.
        $this->assertEqualsCanonicalizing(
            array_column(SituacaoEntrega::cases(), 'value'),
            Entrega::query()->distinct()->pluck('situacao')->map->value->all(),
        );
        foreach (Solicitacao::with('anexos')->get()->pluck('anexos')->flatten() as $anexo) {
            Storage::disk('local')->assertExists($anexo->caminho);
        }
    }

    public function test_linha_do_tempo_fica_na_ordem_e_com_o_autor_de_cada_passo(): void
    {
        $this->seed(DemonstracaoSeeder::class);

        foreach (Solicitacao::all() as $solicitacao) {
            $passos = Historico::where('solicitacao_id', $solicitacao->id)->orderBy('id')->get();

            $momentos = $passos->map(fn (Historico $passo) => $passo->created_at->timestamp)->all();
            $ordenados = $momentos;
            sort($ordenados);

            $this->assertSame($ordenados, $momentos, $solicitacao->titulo);
            $this->assertNull($passos->firstWhere('usuario_id', null), "Passo sem autor em \"{$solicitacao->titulo}\".");
            $this->assertTrue($passos->last()->created_at->isPast());
        }
    }

    public function test_demonstracao_abre_nas_telas_dos_perfis(): void
    {
        $this->seed(DemonstracaoSeeder::class);

        $estudante = Usuario::where('email', 'ana@'.DemonstracaoSeeder::DOMINIO)->firstOrFail();
        $this->actingAs($estudante)->get('/painel')->assertOk();
        $this->actingAs($estudante)->get('/oportunidades')->assertOk()->assertSee('Oficina de robótica com Arduino para o 8º ano');
        $this->actingAs($estudante)->get('/relatorios/extensionistas')->assertOk()->assertSee('Manutenção dos 15 computadores do laboratório');

        $demandante = Usuario::where('email', 'sonia.ribeiro@'.DemonstracaoSeeder::DOMINIO)->firstOrFail();
        $this->actingAs($demandante)->get('/demandas')->assertOk()->assertSee('Site institucional da escola');

        $coordenador = Usuario::where('email', 'mariana.lopes@'.DemonstracaoSeeder::DOMINIO)->firstOrFail();
        $this->actingAs($coordenador)->get('/dashboard')->assertOk();

        $this->get('/projetos')->assertOk()->assertSee('Robótica Educacional nas Escolas');
    }

    /**
     * As tarefas do roteiro de testes com usuários, na ordem das sessões: demandante, estudante,
     * coordenadora e admin. Garante que cada tarefa pode ser concluída com os dados de demonstração.
     */
    public function test_tarefas_do_roteiro_de_testes_com_usuarios_funcionam(): void
    {
        $this->seed(DemonstracaoSeeder::class);

        $conta = fn (string $usuario) => Usuario::where('email', "{$usuario}@".DemonstracaoSeeder::DOMINIO)->firstOrFail();
        $demanda = fn (string $titulo) => Solicitacao::where('titulo', $titulo)->firstOrFail();

        // Demandante (João): chat, nova demanda com anexo, aceite e motivo da recusa.
        $joao = $conta('joao.moreira');
        $wifi = $demanda('Wi-Fi não chega às salas do segundo andar');
        $this->actingAs($joao)->get("/demandas/{$wifi->id}")->assertOk()->assertSee('Em Análise');
        $this->actingAs($joao)->post("/demandas/{$wifi->id}/mensagens", ['texto' => 'A tomada fica ao lado da porta.'])->assertSessionHasNoErrors();

        $this->actingAs($joao)->post('/demandas', [
            'projeto_base_id' => $wifi->projeto_base_id,
            'titulo' => 'Projetor da sala de vídeo não liga',
            'descricao' => 'O projetor parou de ligar depois da queda de energia.',
            'justificativa' => 'As aulas de história usam vídeos.',
            'publico_afetado' => '5 turmas',
            'anexos' => [UploadedFile::fake()->create('foto.pdf', 50, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $biblioteca = $demanda('Sistema de empréstimo da biblioteca');
        $entrega = $biblioteca->subprojeto->entregas()->where('situacao', SituacaoEntrega::AguardandoAceite)->firstOrFail();
        $this->actingAs($joao)->post("/subprojetos/{$biblioteca->subprojeto->id}/entregas/{$entrega->id}/aceite", ['decisao' => 'aceita'])->assertSessionHasNoErrors();
        $this->assertSame(SituacaoEntrega::Aceita, $entrega->fresh()->situacao);

        $impressora = $demanda('Conserto da impressora da secretaria');
        $this->actingAs($joao)->get("/demandas/{$impressora->id}")->assertOk()->assertSee('não faz parte dos projetos de extensão');

        // Estudante (Ana): candidatura, entrega, chat e relatório.
        $ana = $conta('ana');
        $oficina = $demanda('Oficina de robótica com Arduino para o 8º ano')->subprojeto;
        $this->actingAs($ana)->post("/oportunidades/{$oficina->id}/candidatura")->assertSessionHasNoErrors();

        $site = $demanda('Site institucional da escola');
        $this->actingAs($ana)->post("/subprojetos/{$site->subprojeto->id}/entregas", [
            'tipo' => 'link',
            'titulo' => 'Página de avisos',
            'url' => 'https://prototipo.demo.pgdt.example/avisos',
        ])->assertSessionHasNoErrors();
        $this->actingAs($ana)->post("/demandas/{$site->id}/mensagens", ['texto' => 'Enviamos a página de avisos.'])->assertSessionHasNoErrors();
        $this->actingAs($ana)->get('/relatorios/extensionistas/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');

        // Coordenadora (Mariana): triagem, sub-projeto, candidaturas, validação técnica e conclusão.
        $mariana = $conta('mariana.lopes');
        $planilha = $demanda('Planilha para controlar as doações');
        $this->actingAs($mariana)->patch("/demandas/{$planilha->id}/status", ['status' => 'em_analise'])->assertSessionHasNoErrors();
        $this->actingAs($mariana)->patch("/demandas/{$planilha->id}/triagem", [
            'categoria_id' => CategoriaDemanda::where('nome', 'Desenvolvimento de sistema ou aplicativo')->value('id'),
            'prioridade_id' => Prioridade::where('nome', 'Média')->value('id'),
            'complexidade' => 'baixa',
        ])->assertSessionHasNoErrors();
        $this->actingAs($mariana)->patch("/demandas/{$planilha->id}/status", ['status' => 'priorizada'])->assertSessionHasNoErrors();
        $this->actingAs($mariana)->post("/demandas/{$planilha->id}/subprojeto", [
            'titulo' => 'Planilha de doações', 'escopo' => 'Montar e ensinar a usar a planilha.',
            'data_inicio' => now()->toDateString(), 'data_fim' => now()->addMonth()->toDateString(),
            'carga_horaria_estimada' => 12, 'vagas' => 1, 'modalidade' => 'remoto',
        ])->assertSessionHasNoErrors();

        $candidatura = $oficina->candidaturas()->where('estudante_id', $conta('camila')->id)->firstOrFail();
        $this->actingAs($mariana)->patch("/subprojetos/{$oficina->id}/candidaturas/{$candidatura->id}/aprovar", [
            'papel_id' => PapelEquipe::where('nome', 'Membro Desenvolvedor')->value('id'),
        ])->assertSessionHasNoErrors();

        $prototipo = $site->subprojeto->entregas()->where('numero', 1)->firstOrFail();
        $this->actingAs($mariana)->post("/subprojetos/{$site->subprojeto->id}/entregas/{$prototipo->id}/validacao", ['decisao' => 'aprovada'])->assertSessionHasNoErrors();
        $this->actingAs($mariana)->patch("/demandas/{$biblioteca->id}/status", ['status' => 'concluida'])->assertSessionHasNoErrors();
        $this->assertSame(StatusDemanda::Concluida, $biblioteca->fresh()->status);
        $this->actingAs($mariana)->get('/dashboard')->assertOk();

        // Admin: fila de aprovação, convite e relatório geral.
        $admin = Usuario::where('perfil', 'admin')->firstOrFail();
        foreach (['gabriel', 'paulo.dias'] as $pendente) {
            $this->actingAs($admin)->patch("/admin/usuarios/{$conta($pendente)->id}/status", ['status' => 'ativo'])->assertSessionHasNoErrors();
        }
        $this->assertTrue($conta('paulo.dias')->demandante->escolaInstituicao->ativo);
        $this->actingAs($admin)->post('/admin/convites', ['email' => 'novo.coordenador@'.DemonstracaoSeeder::DOMINIO])->assertSessionHasNoErrors();
        $this->actingAs($admin)->get('/relatorios/extensionistas/pdf?status=todos')->assertOk();
    }

    public function test_nao_duplica_os_dados_se_rodar_de_novo(): void
    {
        $this->seed(DemonstracaoSeeder::class);
        $total = Solicitacao::count();

        $this->seed(DemonstracaoSeeder::class);

        $this->assertSame($total, Solicitacao::count());
    }

    public function test_nao_roda_em_producao(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        // O db:seed pede confirmação em produção; aqui o seeder é chamado direto.
        $this->app->make(DemonstracaoSeeder::class)->run();

        $this->assertSame(0, Solicitacao::count());
        $this->assertSame(0, Usuario::count());
    }
}
