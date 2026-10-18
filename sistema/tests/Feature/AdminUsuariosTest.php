<?php

namespace Tests\Feature;

use App\Enums\Perfil;
use App\Enums\StatusUsuario;
use App\Models\Curso;
use App\Models\EscolaInstituicao;
use App\Models\Usuario;
use App\Notifications\CadastroAprovado;
use App\Notifications\CadastroRejeitado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUsuariosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Usuario::factory()->admin()->create();
    }

    public function test_filtra_usuarios_por_perfil_status_e_busca(): void
    {
        Usuario::factory()->estudante()->pendente()->create(['nome' => 'Bruna Estudante']);
        Usuario::factory()->demandante()->create(['nome' => 'Diego Demandante']);

        $this->actingAs($this->admin)->get('/admin/usuarios?perfil=estudante&status=pendente')
            ->assertOk()->assertSee('Bruna Estudante')->assertDontSee('Diego Demandante');

        $this->actingAs($this->admin)->get('/admin/usuarios?busca=Diego')
            ->assertOk()->assertSee('Diego Demandante')->assertDontSee('Bruna Estudante');
    }

    public function test_admin_aprova_estudante_e_demandante_pendentes(): void
    {
        Notification::fake();
        $estudante = Usuario::factory()->estudante()->pendente()->create();
        $demandante = Usuario::factory()->demandante()->pendente()->create();

        foreach ([$estudante, $demandante] as $usuario) {
            $this->actingAs($this->admin)
                ->patch("/admin/usuarios/{$usuario->id}/status", ['status' => 'ativo'])
                ->assertRedirect("/admin/usuarios/{$usuario->id}");

            $usuario->refresh();
            $this->assertSame(StatusUsuario::Ativo, $usuario->status);
            $this->assertSame($this->admin->id, $usuario->aprovado_por);
            $this->assertNotNull($usuario->aprovado_em);
            Notification::assertSentTo($usuario, CadastroAprovado::class);

            $this->actingAs($usuario)->get('/painel')->assertOk();
        }
    }

    public function test_aprovar_demandante_ativa_a_escola_digitada(): void
    {
        $demandante = Usuario::factory()->demandante()->pendente()->create();
        $escola = $demandante->demandante->escolaInstituicao;
        $escola->update(['ativo' => false]);

        $this->actingAs($this->admin)->patch("/admin/usuarios/{$demandante->id}/status", ['status' => 'ativo']);

        $this->assertTrue($escola->fresh()->ativo);
    }

    public function test_nao_aprova_estudante_sem_email_confirmado(): void
    {
        $estudante = Usuario::factory()->estudante()->pendente()->unverified()->create();

        $this->actingAs($this->admin)
            ->patch("/admin/usuarios/{$estudante->id}/status", ['status' => 'ativo'])
            ->assertSessionHasErrors('status');

        $this->assertSame(StatusUsuario::Pendente, $estudante->fresh()->status);
    }

    public function test_rejeitar_exige_motivo_e_envia_email(): void
    {
        Notification::fake();
        $demandante = Usuario::factory()->demandante()->pendente()->create();

        $this->actingAs($this->admin)
            ->patch("/admin/usuarios/{$demandante->id}/status", ['status' => 'rejeitado'])
            ->assertSessionHasErrors('motivo_status');

        $this->actingAs($this->admin)
            ->patch("/admin/usuarios/{$demandante->id}/status", ['status' => 'rejeitado', 'motivo_status' => 'Dados incompletos.']);

        $demandante->refresh();
        $this->assertSame(StatusUsuario::Rejeitado, $demandante->status);
        $this->assertSame('Dados incompletos.', $demandante->motivo_status);
        Notification::assertSentTo($demandante, CadastroRejeitado::class);
    }

    public function test_inativar_exige_motivo(): void
    {
        $estudante = Usuario::factory()->estudante()->create();

        $this->actingAs($this->admin)
            ->patch("/admin/usuarios/{$estudante->id}/status", ['status' => 'inativo', 'motivo_status' => ' '])
            ->assertSessionHasErrors('motivo_status');

        $this->assertSame(StatusUsuario::Ativo, $estudante->fresh()->status);
    }

    public function test_troca_perfil_de_estudante_para_coordenador(): void
    {
        $estudante = Usuario::factory()->estudante()->create();

        $this->actingAs($this->admin)
            ->patch("/admin/usuarios/{$estudante->id}/perfil", ['perfil' => 'coordenador', 'siape' => '1234567'])
            ->assertRedirect("/admin/usuarios/{$estudante->id}");

        $estudante->refresh();
        $this->assertSame(Perfil::Coordenador, $estudante->perfil);
        $this->assertSame('1234567', $estudante->coordenador->siape);
    }

    public function test_troca_para_demandante_exige_os_dados_do_perfil(): void
    {
        $coordenador = Usuario::factory()->coordenador()->create();

        $this->actingAs($this->admin)
            ->patch("/admin/usuarios/{$coordenador->id}/perfil", ['perfil' => 'demandante'])
            ->assertSessionHasErrors(['escola_instituicao_id', 'cargo', 'telefone']);

        $escola = EscolaInstituicao::factory()->create();

        $this->actingAs($this->admin)->patch("/admin/usuarios/{$coordenador->id}/perfil", [
            'perfil' => 'demandante',
            'escola_instituicao_id' => $escola->id,
            'cargo' => 'Pedagogo',
            'telefone' => '(28) 99999-0001',
        ])->assertSessionHasNoErrors();

        $this->assertSame(Perfil::Demandante, $coordenador->fresh()->perfil);
    }

    public function test_troca_para_estudante_exige_matricula_e_curso(): void
    {
        $demandante = Usuario::factory()->demandante()->create();
        $curso = Curso::factory()->create();

        $this->actingAs($this->admin)->patch("/admin/usuarios/{$demandante->id}/perfil", [
            'perfil' => 'estudante',
            'matricula' => '2020100999',
            'curso_id' => $curso->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(Perfil::Estudante, $demandante->fresh()->perfil);
    }

    public function test_admin_nao_altera_o_proprio_status_nem_perfil(): void
    {
        $this->actingAs($this->admin)
            ->patch("/admin/usuarios/{$this->admin->id}/status", ['status' => 'inativo', 'motivo_status' => 'teste'])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->patch("/admin/usuarios/{$this->admin->id}/perfil", ['perfil' => 'estudante'])
            ->assertForbidden();
    }

    public function test_admin_corrige_os_dados_da_escola_do_demandante(): void
    {
        $demandante = Usuario::factory()->demandante()->pendente()->create();

        $this->actingAs($this->admin)->patch("/admin/usuarios/{$demandante->id}/escola", [
            'nome' => 'EEEFM Corrigida',
            'tipo' => 'escola_publica',
            'codigo_inep' => '32012345',
            'cidade' => 'Alegre',
            'uf' => 'es',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('escolas_instituicoes', ['nome' => 'EEEFM Corrigida', 'codigo_inep' => '32012345', 'uf' => 'ES']);
    }
}
