<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaDemanda;
use App\Models\PapelEquipe;
use App\Models\Prioridade;
use App\Models\Tipologia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Cadastros de apoio do admin: tipologias, categorias de demanda, prioridades e papéis da equipe.
 * Os itens não são apagados, só inativados, para não quebrar os registros que já os usam.
 */
class DominioController extends Controller
{
    /**
     * @var array<string, array{model: class-string<Model>, titulo: string, singular: string, feminino: bool, descricao: string, ordem: bool}>
     */
    public const TIPOS = [
        'tipologias' => [
            'model' => Tipologia::class,
            'titulo' => 'Tipologias',
            'singular' => 'tipologia',
            'feminino' => true,
            'descricao' => 'Tipo de ação de extensão de cada Projeto Base.',
            'ordem' => false,
        ],
        'categorias' => [
            'model' => CategoriaDemanda::class,
            'titulo' => 'Categorias de demanda',
            'singular' => 'categoria',
            'feminino' => true,
            'descricao' => 'Definida pelo coordenador na triagem da demanda.',
            'ordem' => false,
        ],
        'prioridades' => [
            'model' => Prioridade::class,
            'titulo' => 'Prioridades',
            'singular' => 'prioridade',
            'feminino' => true,
            'descricao' => 'Definida na triagem; a ordem organiza a fila (menor número primeiro).',
            'ordem' => true,
        ],
        'papeis' => [
            'model' => PapelEquipe::class,
            'titulo' => 'Papéis da equipe',
            'singular' => 'papel',
            'feminino' => false,
            'descricao' => 'Papel do estudante na equipe do sub-projeto, escolhido na aprovação da candidatura.',
            'ordem' => false,
        ],
    ];

    public function index(string $tipo): View
    {
        $config = self::TIPOS[$tipo];

        return view('admin.dominios.index', [
            'tipo' => $tipo,
            'config' => $config,
            'tipos' => self::TIPOS,
            'itens' => $config['model']::query()->ordenados()->get(),
        ]);
    }

    public function store(Request $request, string $tipo): RedirectResponse
    {
        $config = self::TIPOS[$tipo];
        $dados = $this->validar($request, $config);

        $config['model']::create($dados + ['ativo' => true]);

        return redirect()->route('admin.dominios.index', $tipo)->with('sucesso', ucfirst($config['singular'])." \"{$dados['nome']}\" ".($config['feminino'] ? 'incluída' : 'incluído').'.');
    }

    public function update(Request $request, string $tipo, int $id): RedirectResponse
    {
        $config = self::TIPOS[$tipo];
        $item = $config['model']::findOrFail($id);

        $dados = $this->validar($request, $config, $item);
        $dados['ativo'] = $request->boolean('ativo');
        $item->update($dados);

        return redirect()->route('admin.dominios.index', $tipo)->with('sucesso', ucfirst($config['singular'])." \"{$item->nome}\" ".($config['feminino'] ? 'atualizada' : 'atualizado').'.');
    }

    /**
     * @param  array{model: class-string<Model>, ordem: bool}  $config
     * @return array<string, mixed>
     */
    private function validar(Request $request, array $config, ?Model $item = null): array
    {
        $tabela = (new $config['model'])->getTable();

        $regras = [
            'nome' => ['required', 'string', 'max:100', Rule::unique($tabela, 'nome')->ignore($item?->getKey())],
        ];

        if ($config['ordem']) {
            $regras['ordem'] = ['required', 'integer', 'min:0', 'max:999'];
        }

        // Cada linha da lista tem o próprio formulário; o erro aparece na linha que foi salva.
        return $request->validateWithBag($item ? "item{$item->getKey()}" : 'default', $regras, [
            'nome.unique' => 'Já existe um item com este nome.',
        ]);
    }
}
