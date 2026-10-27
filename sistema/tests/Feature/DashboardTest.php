<?php

namespace Tests\Feature;

use App\Enums\StatusDemanda;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 7: dashboard gerencial.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_so_admin_e_coordenador_acessam_o_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->actingAs(Usuario::factory()->admin()->create())->get('/dashboard')->assertOk();
        $this->actingAs(Usuario::factory()->coordenador()->create())->get('/dashboard')->assertOk();
        $this->actingAs(Usuario::factory()->demandante()->create())->get('/dashboard')->assertForbidden();
        $this->actingAs(Usuario::factory()->estudante()->create())->get('/dashboard')->assertForbidden();
    }

    public function test_conta_as_demandas_e_calcula_os_tempos_pela_linha_do_tempo(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $this->travel(-10)->days();
        $concluida = Solicitacao::factory()->create(['titulo' => 'Rede da escola']);
        $this->travelBack();

        $this->travel(-8)->days();
        $concluida->update(['status' => StatusDemanda::EmAnalise]);
        $this->travelBack();
        $concluida->update(['status' => StatusDemanda::Concluida]);

        Solicitacao::factory()->count(2)->create();

        $resposta = $this->actingAs($admin)->get('/dashboard')->assertOk();

        $this->assertSame(3, $resposta->viewData('total'));
        $this->assertSame(1, $resposta->viewData('concluidas'));
        $this->assertSame(2, $resposta->viewData('porStatus')['Cadastrada']);
        $this->assertSame(1, $resposta->viewData('porStatus')['Concluída']);
        $this->assertEqualsWithDelta(10.0, $resposta->viewData('tempoDeAtendimento')['dias'], 0.1);
        $this->assertSame(1, $resposta->viewData('tempoDeAtendimento')['demandas']);
        $this->assertEqualsWithDelta(2.0, $resposta->viewData('tempoDePrimeiraResposta')['dias'], 0.1);
        $this->assertSame(3, $resposta->viewData('porCategoria')['Sem categoria (não classificada)']);
        $resposta->assertSee('Tempo médio de atendimento');
    }

    public function test_coordenador_ve_so_as_demandas_dos_seus_projetos(): void
    {
        $coordenador = Usuario::factory()->coordenador()->create();
        $meuProjeto = ProjetoBase::factory()->create(['coordenador_id' => $coordenador->id]);
        $outroProjeto = ProjetoBase::factory()->create();

        Solicitacao::factory()->count(2)->create(['projeto_base_id' => $meuProjeto->id]);
        Solicitacao::factory()->create(['projeto_base_id' => $outroProjeto->id]);

        $resposta = $this->actingAs($coordenador)->get('/dashboard')->assertOk();

        $this->assertSame(2, $resposta->viewData('total'));
        $this->assertSame([$meuProjeto->titulo => 2], $resposta->viewData('porProjeto')->all());
        $resposta->assertDontSee($outroProjeto->titulo);
    }

    public function test_filtra_por_periodo_de_abertura_e_projeto(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $projeto = ProjetoBase::factory()->create();

        $this->travelTo('2026-03-10');
        Solicitacao::factory()->create(['projeto_base_id' => $projeto->id]);
        $this->travelTo('2026-06-10');
        Solicitacao::factory()->create(['projeto_base_id' => $projeto->id]);
        Solicitacao::factory()->create();
        $this->travelBack();

        $this->actingAs($admin)->get('/dashboard?de=2026-06-01&ate=2026-06-30')->assertViewHas('total', 2);
        $this->actingAs($admin)->get("/dashboard?projeto={$projeto->id}")->assertViewHas('total', 2);
        $this->actingAs($admin)->get("/dashboard?de=2026-06-01&projeto={$projeto->id}")->assertViewHas('total', 1);
        $this->actingAs($admin)->get('/dashboard?de=2026-06-30&ate=2026-06-01')->assertSessionHasErrors('ate');
    }
}
