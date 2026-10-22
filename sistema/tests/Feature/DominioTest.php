<?php

namespace Tests\Feature;

use App\Models\CategoriaDemanda;
use App\Models\Prioridade;
use App\Models\Tipologia;
use App\Models\Usuario;
use Database\Seeders\DominioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DominioTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Usuario::factory()->admin()->create();
    }

    public function test_seeder_cria_os_valores_iniciais(): void
    {
        $this->seed(DominioSeeder::class);
        $this->seed(DominioSeeder::class);

        $this->assertDatabaseHas('tipologias', ['nome' => 'Projeto']);
        $this->assertDatabaseHas('categorias_demanda', ['nome' => 'Outros']);
        $this->assertSame(['Urgente', 'Alta', 'Média', 'Baixa'], Prioridade::query()->ativos()->pluck('nome')->all());
        $this->assertSame(1, Tipologia::where('nome', 'Projeto')->count());
    }

    public function test_admin_inclui_e_edita_itens(): void
    {
        $this->actingAs($this->admin)->get('/admin/cadastros/categorias')->assertOk();

        $this->actingAs($this->admin)
            ->post('/admin/cadastros/categorias', ['nome' => 'Robótica educacional'])
            ->assertRedirect('/admin/cadastros/categorias');

        $categoria = CategoriaDemanda::where('nome', 'Robótica educacional')->firstOrFail();
        $this->assertTrue($categoria->ativo);

        $this->actingAs($this->admin)
            ->patch("/admin/cadastros/categorias/{$categoria->id}", ['nome' => 'Robótica'])
            ->assertRedirect('/admin/cadastros/categorias');

        $categoria->refresh();
        $this->assertSame('Robótica', $categoria->nome);
        $this->assertFalse($categoria->ativo);
    }

    public function test_prioridade_exige_ordem_e_nome_unico(): void
    {
        Prioridade::create(['nome' => 'Alta', 'ordem' => 10]);

        $this->actingAs($this->admin)
            ->post('/admin/cadastros/prioridades', ['nome' => 'Alta'])
            ->assertSessionHasErrors(['nome', 'ordem']);

        $this->actingAs($this->admin)
            ->post('/admin/cadastros/prioridades', ['nome' => 'Baixa', 'ordem' => 40])
            ->assertSessionHasNoErrors();
    }

    public function test_erro_na_edicao_fica_na_linha_salva(): void
    {
        Tipologia::factory()->create(['nome' => 'Curso']);
        $evento = Tipologia::factory()->create(['nome' => 'Evento']);

        $this->actingAs($this->admin)
            ->patch("/admin/cadastros/tipologias/{$evento->id}", ['nome' => 'Curso', 'ativo' => '1'])
            ->assertSessionHasErrorsIn("item{$evento->id}", 'nome');
    }

    public function test_tipo_invalido_retorna_404(): void
    {
        $this->actingAs($this->admin)->get('/admin/cadastros/usuarios')->assertNotFound();
    }

    public function test_so_o_admin_acessa(): void
    {
        $coordenador = Usuario::factory()->coordenador()->create();

        $this->actingAs($coordenador)->get('/admin/cadastros/tipologias')->assertForbidden();
        $this->actingAs($coordenador)->post('/admin/cadastros/tipologias', ['nome' => 'Nova'])->assertForbidden();
    }
}
