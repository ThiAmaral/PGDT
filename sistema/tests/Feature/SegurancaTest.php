<?php

namespace Tests\Feature;

use App\Enums\StatusDemanda;
use App\Models\Convite;
use App\Models\Curso;
use App\Models\MembroEquipe;
use App\Models\PapelEquipe;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
use App\Models\Tipologia;
use App\Models\Usuario;
use App\Notifications\Aviso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as Rota;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 8: revisão de segurança. Confere, rota por rota, que nada interno abre sem login e que
 * a área do admin fica fechada para os outros perfis; mais os limites de envio, os cabeçalhos,
 * o conteúdo real dos uploads e o texto dos usuários nos e-mails.
 */
class SegurancaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rotas que usam o middleware informado.
     *
     * @return list<Rota>
     */
    private function rotasCom(string $middleware): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            fn (Rota $rota) => in_array($middleware, $rota->gatherMiddleware(), true),
        ));
    }

    /**
     * Endereço da rota com cada parâmetro trocado pelo valor informado (ou 1).
     *
     * @param  array<string, string|int>  $valores
     */
    private function enderecoDe(Rota $rota, array $valores = []): string
    {
        return '/'.ltrim(preg_replace_callback('/\{(\w+)\??\}/', fn (array $parte) => (string) ($valores[$parte[1]] ?? 1), $rota->uri()), '/');
    }

    public function test_visitante_vai_para_o_login_em_todas_as_rotas_internas(): void
    {
        $rotas = $this->rotasCom('auth');
        $this->assertNotEmpty($rotas);

        foreach ($rotas as $rota) {
            $metodo = $rota->methods()[0];
            $endereco = $this->enderecoDe($rota, ['tipo' => 'tipologias']);
            $resposta = $this->call($metodo, $endereco);

            $this->assertSame(302, $resposta->getStatusCode(), "{$metodo} {$endereco}");
            $this->assertSame(url('/login'), $resposta->headers->get('Location'), "{$metodo} {$endereco}");
        }
    }

    public function test_area_do_admin_fica_fechada_para_os_outros_perfis(): void
    {
        $alvo = Usuario::factory()->estudante()->create();
        [$convite] = Convite::gerar('prof@ufes.br', Usuario::factory()->admin()->create());
        $tipologia = Tipologia::factory()->create();

        $valores = ['usuario' => $alvo->id, 'convite' => $convite->id, 'tipo' => 'tipologias', 'id' => $tipologia->id];
        $rotas = array_filter($this->rotasCom('perfil:admin'), fn (Rota $rota) => ! $rota->getAction('redirect'));
        $this->assertNotEmpty($rotas);

        foreach ([Usuario::factory()->coordenador()->create(), Usuario::factory()->demandante()->create(), $alvo] as $usuario) {
            foreach ($rotas as $rota) {
                $this->actingAs($usuario)->call($rota->methods()[0], $this->enderecoDe($rota, $valores))->assertForbidden();
            }
        }
    }

    public function test_dashboard_fica_fechado_para_demandante_e_estudante(): void
    {
        $this->actingAs(Usuario::factory()->demandante()->create())->get('/dashboard')->assertForbidden();
        $this->actingAs(Usuario::factory()->estudante()->create())->get('/dashboard')->assertForbidden();
    }

    public function test_respostas_trazem_os_cabecalhos_de_seguranca(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_cadastro_limita_os_envios_por_minuto(): void
    {
        $curso = Curso::create(['nome' => 'Sistemas de Informação']);

        foreach (range(1, 10) as $tentativa) {
            $this->post('/cadastro/estudante', ['curso_id' => $curso->id])->assertSessionHasErrors();
        }

        $this->post('/cadastro/estudante', ['curso_id' => $curso->id])->assertTooManyRequests();
    }

    public function test_anexo_com_extensao_trocada_e_recusado_pelo_conteudo(): void
    {
        Storage::fake('local');

        $demandante = Usuario::factory()->demandante()->create();
        $projeto = ProjetoBase::factory()->create();

        $caminho = tempnam(sys_get_temp_dir(), 'pgdt');
        file_put_contents($caminho, '<?php echo "ola";');

        $this->actingAs($demandante)->post('/demandas', [
            'projeto_base_id' => $projeto->id,
            'titulo' => 'Projetor queimado',
            'descricao' => 'O projetor da sala 3 não liga.',
            'justificativa' => 'As aulas usam slides.',
            'publico_afetado' => '30 alunos',
            'anexos' => [new UploadedFile($caminho, 'oficio.pdf', 'application/pdf', null, true)],
        ])->assertSessionHasErrors('anexos.0');

        $this->assertSame(0, Solicitacao::count());
    }

    public function test_texto_do_chat_nao_vira_link_no_email(): void
    {
        $destinatario = Usuario::factory()->demandante()->create(['nome' => 'Maria [admin](https://golpe.example)']);

        $aviso = new Aviso('Nova mensagem', ['Clique [aqui](https://golpe.example) e **confirme**'], 'Responder', 'http://localhost/demandas/1');
        $html = (string) $aviso->toMail($destinatario)->render();

        $this->assertStringNotContainsString('href="https://golpe.example"', $html);
        $this->assertStringNotContainsString('<strong>confirme</strong>', $html);
        $this->assertStringContainsString('[aqui](https://golpe.example)', $html);
        $this->assertStringContainsString('href="http://localhost/demandas/1"', $html);
    }

    public function test_pdf_do_relatorio_nao_mostra_projeto_de_outro_coordenador(): void
    {
        $coordenador = Usuario::factory()->coordenador()->create();
        $meu = ProjetoBase::factory()->create(['coordenador_id' => $coordenador->id]);
        $outro = ProjetoBase::factory()->create(['titulo' => 'Projeto Sigiloso de Outro Coordenador']);

        $solicitacao = Solicitacao::factory()->triada()->comStatus(StatusDemanda::Concluida)->create(['projeto_base_id' => $meu->id]);
        $subprojeto = Subprojeto::factory()->create(['solicitacao_id' => $solicitacao->id]);
        MembroEquipe::create([
            'subprojeto_id' => $subprojeto->id,
            'estudante_id' => Usuario::factory()->estudante()->create()->id,
            'papel_id' => PapelEquipe::firstOrFail()->id,
        ]);

        $resposta = $this->actingAs($coordenador)->get("/relatorios/extensionistas/pdf?projeto={$outro->id}")->assertOk();

        $this->assertStringNotContainsString($outro->titulo, $resposta->getContent());
    }
}
