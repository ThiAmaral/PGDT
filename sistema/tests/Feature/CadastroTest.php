<?php

namespace Tests\Feature;

use App\Enums\Perfil;
use App\Enums\StatusUsuario;
use App\Models\Curso;
use App\Models\EscolaInstituicao;
use App\Models\Usuario;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CadastroTest extends TestCase
{
    use RefreshDatabase;

    public function test_tela_de_escolha_mostra_os_dois_tipos_de_cadastro(): void
    {
        $this->get('/cadastro')
            ->assertOk()
            ->assertSee('Sou estudante da UFES')
            ->assertSee('Represento uma escola ou instituição');
    }

    public function test_estudante_se_cadastra_como_pendente_e_recebe_confirmacao_de_email(): void
    {
        Notification::fake();
        $curso = Curso::factory()->create();

        $resposta = $this->post('/cadastro/estudante', [
            'nome' => 'Ana Souza',
            'email' => 'Ana.Souza@EDU.UFES.BR',
            'matricula' => '2023100123',
            'curso_id' => $curso->id,
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ]);

        $resposta->assertRedirect(route('verification.notice'));

        $usuario = Usuario::where('email', 'ana.souza@edu.ufes.br')->firstOrFail();
        $this->assertSame(Perfil::Estudante, $usuario->perfil);
        $this->assertSame(StatusUsuario::Pendente, $usuario->status);
        $this->assertNull($usuario->email_verified_at);
        $this->assertSame('2023100123', $usuario->estudante->matricula);
        Notification::assertSentTo($usuario, VerifyEmail::class);
    }

    public function test_estudante_com_email_fora_da_ufes_e_bloqueado(): void
    {
        $curso = Curso::factory()->create();

        $this->post('/cadastro/estudante', [
            'nome' => 'Ana Souza',
            'email' => 'ana@gmail.com',
            'matricula' => '2023100123',
            'curso_id' => $curso->id,
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('usuarios', ['email' => 'ana@gmail.com']);
    }

    public function test_estudante_sem_confirmar_email_nao_ve_a_tela_em_analise(): void
    {
        $estudante = Usuario::factory()->estudante()->pendente()->unverified()->create();

        $this->actingAs($estudante)->get('/cadastro-em-analise')->assertRedirect(route('verification.notice'));
    }

    public function test_demandante_se_cadastra_com_escola_da_lista(): void
    {
        $escola = EscolaInstituicao::factory()->create();

        $this->post('/cadastro/demandante', [
            'nome' => 'Carlos Lima',
            'email' => 'carlos@escola.es.gov.br',
            'telefone' => '(28) 99999-1234',
            'cargo' => 'Diretor',
            'escola_modo' => 'lista',
            'escola_instituicao_id' => $escola->id,
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ])->assertRedirect(route('cadastro.em-analise'));

        $usuario = Usuario::where('email', 'carlos@escola.es.gov.br')->firstOrFail();
        $this->assertSame(Perfil::Demandante, $usuario->perfil);
        $this->assertSame(StatusUsuario::Pendente, $usuario->status);
        $this->assertSame($escola->id, $usuario->demandante->escola_instituicao_id);

        $this->actingAs($usuario)->get('/cadastro-em-analise')->assertOk()->assertSee('Cadastro em análise');
    }

    public function test_demandante_digita_escola_nova_que_nasce_inativa(): void
    {
        $this->post('/cadastro/demandante', [
            'nome' => 'Carlos Lima',
            'email' => 'carlos@escola.es.gov.br',
            'telefone' => '(28) 3552-1234',
            'cargo' => 'Diretor',
            'escola_modo' => 'nova',
            'nova_escola_nome' => 'EEEFM Nova Escola',
            'nova_escola_tipo' => 'escola_publica',
            'nova_escola_cidade' => 'Alegre',
            'nova_escola_uf' => 'es',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ])->assertRedirect(route('cadastro.em-analise'));

        $this->assertDatabaseHas('escolas_instituicoes', ['nome' => 'EEEFM Nova Escola', 'uf' => 'ES', 'ativo' => false]);
    }

    public function test_demandante_exige_telefone_no_formato_correto(): void
    {
        $escola = EscolaInstituicao::factory()->create();

        $this->post('/cadastro/demandante', [
            'nome' => 'Carlos Lima',
            'email' => 'carlos@escola.es.gov.br',
            'telefone' => '2899991234',
            'cargo' => 'Diretor',
            'escola_modo' => 'lista',
            'escola_instituicao_id' => $escola->id,
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ])->assertSessionHasErrors('telefone');
    }

    public function test_usuario_pendente_nao_acessa_o_painel(): void
    {
        $demandante = Usuario::factory()->demandante()->pendente()->create();

        $this->actingAs($demandante)->get('/painel')->assertRedirect(route('cadastro.em-analise'));
    }
}
