<?php

namespace Tests\Feature;

use App\Enums\StatusUsuario;
use App\Models\Usuario;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_ativo_entra_e_vai_para_o_painel(): void
    {
        $usuario = Usuario::factory()->coordenador()->create();

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])
            ->assertRedirect('/painel');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_usuario_rejeitado_nao_entra_e_ve_o_motivo(): void
    {
        $usuario = Usuario::factory()->demandante()->create([
            'status' => StatusUsuario::Rejeitado,
            'motivo_status' => 'Escola fora da área de atendimento.',
        ]);

        $this->from('/login')->post('/login', ['email' => $usuario->email, 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Seu cadastro não foi aprovado. Motivo: Escola fora da área de atendimento. Em caso de dúvida, procure a coordenação do projeto.']);

        $this->assertGuest();
    }

    public function test_usuario_inativo_nao_entra(): void
    {
        $usuario = Usuario::factory()->estudante()->comStatus(StatusUsuario::Inativo)->create();

        $this->post('/login', ['email' => $usuario->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_inativado_durante_a_sessao_e_desconectado(): void
    {
        $usuario = Usuario::factory()->estudante()->comStatus(StatusUsuario::Inativo)->create();

        $this->actingAs($usuario)->get('/painel')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_esqueci_minha_senha_envia_o_link_por_email(): void
    {
        Notification::fake();
        $usuario = Usuario::factory()->demandante()->create();

        $this->post('/password/email', ['email' => $usuario->email])->assertSessionHas('status');

        Notification::assertSentTo($usuario, ResetPassword::class);
    }

    public function test_cada_perfil_ve_so_o_seu_menu(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $estudante = Usuario::factory()->estudante()->create();

        $this->actingAs($admin)->get('/painel')->assertOk()->assertSee('Convites');
        $this->actingAs($estudante)->get('/painel')->assertOk()->assertDontSee('Convites')->assertSee('Oportunidades');
    }

    public function test_apenas_admin_acessa_o_painel_de_usuarios(): void
    {
        foreach (['coordenador', 'demandante', 'estudante'] as $perfil) {
            $usuario = Usuario::factory()->{$perfil}()->create();

            $this->actingAs($usuario)->get('/admin/usuarios')->assertForbidden();
        }
    }
}
