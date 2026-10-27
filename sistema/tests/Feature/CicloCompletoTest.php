<?php

namespace Tests\Feature;

use App\Enums\SituacaoEntrega;
use App\Enums\StatusDemanda;
use App\Enums\TipoHistorico;
use App\Models\Candidatura;
use App\Models\Entrega;
use App\Models\PapelEquipe;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Critério de conclusão da Fase 6: demanda → sub-projeto → entrega → aprovação técnica → aceite
 * → concluída, e o relatório do estudante mostra o sub-projeto com datas e carga horária.
 */
class CicloCompletoTest extends TestCase
{
    use RefreshDatabase;

    public function test_ciclo_completo_da_demanda_ate_o_relatorio_do_estudante(): void
    {
        $coordenador = Usuario::factory()->coordenador()->create();
        $estudante = Usuario::factory()->estudante()->create(['nome' => 'Carla Extensionista']);
        $projeto = ProjetoBase::factory()->create(['coordenador_id' => $coordenador->id, 'titulo' => 'Tecnologia na Escola']);
        $solicitacao = Solicitacao::factory()->triada()->comStatus(StatusDemanda::Priorizada)->create(['projeto_base_id' => $projeto->id]);
        $demandante = $solicitacao->demandante;

        // Sub-projeto (RF09) e equipe (RF10 e RF11).
        $this->actingAs($coordenador)->post("/demandas/{$solicitacao->id}/subprojeto", [
            'titulo' => 'Site da escola',
            'escopo' => 'Site institucional com notícias.',
            'data_inicio' => '2026-11-03',
            'data_fim' => '2027-02-26',
            'carga_horaria_estimada' => 60,
            'vagas' => 2,
            'modalidade' => 'remoto',
        ])->assertRedirect();
        $subprojeto = $solicitacao->subprojeto()->sole();

        $this->actingAs($estudante)->post("/oportunidades/{$subprojeto->id}/candidatura")->assertRedirect();
        $candidatura = Candidatura::sole();
        $this->actingAs($coordenador)->patch("/subprojetos/{$subprojeto->id}/candidaturas/{$candidatura->id}/aprovar", [
            'papel_id' => PapelEquipe::where('nome', 'Responsável Técnico')->firstOrFail()->id,
        ])->assertRedirect();

        $this->actingAs($coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'em_desenvolvimento'])->assertSessionHasNoErrors();

        // RF12: entrega da equipe.
        $this->actingAs($estudante)->post("/subprojetos/{$subprojeto->id}/entregas", [
            'tipo' => 'link',
            'titulo' => 'Site publicado',
            'url' => 'https://escola.example/site',
        ])->assertSessionHasNoErrors();
        $entrega = Entrega::sole();

        $this->actingAs($coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'em_validacao'])->assertSessionHasNoErrors();

        // RF13: aprovação técnica e aceite.
        $this->actingAs($coordenador)->post("/subprojetos/{$subprojeto->id}/entregas/{$entrega->id}/validacao", ['decisao' => 'aprovada'])->assertSessionHasNoErrors();
        $this->actingAs($demandante)->get("/subprojetos/{$subprojeto->id}/entregas/{$entrega->id}")->assertOk()->assertSee('Aceitar Solução');
        $this->actingAs($demandante)->post("/subprojetos/{$subprojeto->id}/entregas/{$entrega->id}/aceite", ['decisao' => 'aceita'])->assertSessionHasNoErrors();
        $this->assertSame(SituacaoEntrega::Aceita, $entrega->fresh()->situacao);

        $this->actingAs($coordenador)->patch("/demandas/{$solicitacao->id}/status", ['status' => 'concluida'])->assertSessionHasNoErrors();
        $this->assertSame(StatusDemanda::Concluida, $solicitacao->fresh()->status);

        // RF08: cada passo ficou na linha do tempo.
        $tipos = $solicitacao->historicos()->pluck('tipo');
        $this->assertTrue($tipos->contains(TipoHistorico::Entrega));
        $this->assertSame(2, $tipos->filter(fn (TipoHistorico $tipo) => $tipo === TipoHistorico::Validacao)->count());

        // RF15: o relatório do estudante mostra o sub-projeto com as datas e a carga horária.
        $this->actingAs($estudante)->get('/relatorios/extensionistas')
            ->assertOk()
            ->assertSeeInOrder(['Site da escola', 'Tecnologia na Escola', 'Responsável Técnico', '03/11/2026', '26/02/2027', '60 h'])
            ->assertSee('Total: 60 h');
    }
}
