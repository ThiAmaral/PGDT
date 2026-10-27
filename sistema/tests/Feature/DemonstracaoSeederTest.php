<?php

namespace Tests\Feature;

use App\Enums\SituacaoEntrega;
use App\Enums\StatusDemanda;
use App\Enums\StatusUsuario;
use App\Models\Entrega;
use App\Models\Historico;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Database\Seeders\DemonstracaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
