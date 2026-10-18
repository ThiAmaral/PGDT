<?php

namespace Tests\Feature;

use App\Enums\Perfil;
use App\Enums\StatusUsuario;
use App\Models\Convite;
use App\Models\Usuario;
use App\Notifications\ConviteCoordenador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ConviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_envia_convite_por_email(): void
    {
        Notification::fake();
        $admin = Usuario::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/convites', ['email' => 'Prof@UFES.br'])
            ->assertRedirect('/admin/convites');

        $this->assertDatabaseHas('convites', ['email' => 'prof@ufes.br', 'criado_por' => $admin->id]);
        Notification::assertSentTo(new AnonymousNotifiable, ConviteCoordenador::class,
            fn (ConviteCoordenador $notificacao, array $canais, AnonymousNotifiable $destino) => $destino->routes['mail'] === 'prof@ufes.br');
    }

    public function test_convite_para_email_ja_cadastrado_e_recusado(): void
    {
        $admin = Usuario::factory()->admin()->create();
        $existente = Usuario::factory()->estudante()->create();

        $this->actingAs($admin)->post('/admin/convites', ['email' => $existente->email])
            ->assertSessionHasErrors('email');
    }

    public function test_coordenador_aceita_convite_e_ja_nasce_ativo(): void
    {
        $admin = Usuario::factory()->admin()->create();
        [$convite, $token] = Convite::gerar('prof@ufes.br', $admin);

        $this->get("/convite/{$token}")->assertOk()->assertSee('prof@ufes.br');

        $this->post("/convite/{$token}", [
            'nome' => 'Profa. Maria',
            'siape' => '1234567',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ])->assertRedirect('/painel');

        $coordenador = Usuario::where('email', 'prof@ufes.br')->firstOrFail();
        $this->assertSame(Perfil::Coordenador, $coordenador->perfil);
        $this->assertSame(StatusUsuario::Ativo, $coordenador->status);
        $this->assertTrue($coordenador->hasVerifiedEmail());
        $this->assertNotNull($convite->fresh()->usado_em);
        $this->assertAuthenticatedAs($coordenador);
    }

    public function test_convite_e_de_uso_unico(): void
    {
        $admin = Usuario::factory()->admin()->create();
        [, $token] = Convite::gerar('prof@ufes.br', $admin);

        $this->post("/convite/{$token}", [
            'nome' => 'Profa. Maria',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ]);
        auth()->logout();

        $this->get("/convite/{$token}")->assertOk()->assertSee('Convite inválido ou expirado');
    }

    public function test_convite_expirado_nao_vale(): void
    {
        $admin = Usuario::factory()->admin()->create();
        [$convite, $token] = Convite::gerar('prof@ufes.br', $admin);
        $convite->update(['expira_em' => now()->subMinute()]);

        $this->get("/convite/{$token}")->assertSee('Convite inválido ou expirado');
        $this->post("/convite/{$token}", [
            'nome' => 'Profa. Maria',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ])->assertSee('Convite inválido ou expirado');

        $this->assertDatabaseMissing('usuarios', ['email' => 'prof@ufes.br']);
    }

    public function test_admin_cancela_convite(): void
    {
        $admin = Usuario::factory()->admin()->create();
        [$convite, $token] = Convite::gerar('prof@ufes.br', $admin);

        $this->actingAs($admin)->patch("/admin/convites/{$convite->id}/cancelar")->assertRedirect('/admin/convites');
        auth()->logout();

        $this->get("/convite/{$token}")->assertSee('Convite inválido ou expirado');
    }
}
