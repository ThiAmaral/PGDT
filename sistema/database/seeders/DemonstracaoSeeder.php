<?php

namespace Database\Seeders;

use App\Enums\Complexidade;
use App\Enums\DecisaoValidacao;
use App\Enums\Modalidade;
use App\Enums\Perfil;
use App\Enums\StatusCandidatura;
use App\Enums\StatusDemanda;
use App\Enums\StatusUsuario;
use App\Enums\TipoEntrega;
use App\Enums\TipoEscola;
use App\Models\Candidatura;
use App\Models\CategoriaDemanda;
use App\Models\Curso;
use App\Models\Entrega;
use App\Models\EscolaInstituicao;
use App\Models\MembroEquipe;
use App\Models\PapelEquipe;
use App\Models\Prioridade;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
use App\Models\Tipologia;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fase 8: dados de demonstração para a apresentação e os testes com usuários. Não faz parte do
 * DatabaseSeeder e não roda em produção. Uso: `php artisan db:seed --class=DemonstracaoSeeder`.
 *
 * Os dados são fictícios (pessoas, escolas e e-mails em `@demo.pgdt.example`, que não recebem
 * mensagens). Cada demanda percorre o fluxo real, na ordem e com as datas de cada passo, para a
 * linha do tempo, o dashboard e o relatório ficarem coerentes. Nenhum e-mail é enviado.
 */
class DemonstracaoSeeder extends Seeder
{
    /** Domínio dos e-mails das contas de demonstração. */
    public const DOMINIO = 'demo.pgdt.example';

    private string $senha;

    private Usuario $admin;

    /** Momento do último passo: os passos do mesmo dia ficam na ordem em que foram escritos. */
    private ?Carbon $ultimoPasso = null;

    /** @var array<string, Usuario> */
    private array $pessoas = [];

    /** @var array<string, EscolaInstituicao> */
    private array $escolas = [];

    /** @var array<string, ProjetoBase> */
    private array $projetos = [];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('Os dados de demonstração não podem ser carregados em produção.');

            return;
        }

        if (Usuario::where('email', 'like', '%@'.self::DOMINIO)->exists()) {
            $this->command?->warn('Os dados de demonstração já foram carregados neste banco.');

            return;
        }

        // Cursos, domínios, "Demandas Gerais" e o admin inicial, se ainda não existirem.
        $this->call(DatabaseSeeder::class);

        $this->admin = Usuario::where('perfil', Perfil::Admin)->orderBy('id')->firstOrFail();
        $this->senha = config('pgdt.demonstracao.senha') ?: Str::password(12, symbols: false);

        Notification::fake();

        try {
            $this->criarPessoasEEscolas();
            $this->criarProjetos();
            $this->criarDemandas();
        } finally {
            Carbon::setTestNow();
            Auth::forgetUser();
        }

        $this->mostrarContas();
    }

    /**
     * Coordenadores, demandantes e estudantes, incluindo cadastros que aguardam a aprovação do admin.
     */
    private function criarPessoasEEscolas(): void
    {
        $this->quando(200);

        $this->escolas = [
            'palmeiras' => $this->escola('EEEFM Vale das Palmeiras', TipoEscola::EscolaPublica, 'Alegre'),
            'corrego' => $this->escola('EMEF Córrego Alegre', TipoEscola::EscolaPublica, 'Guaçuí'),
            'pedra' => $this->escola('EMEIEF Pedra Azul', TipoEscola::EscolaPublica, 'Jerônimo Monteiro'),
            'vilanova' => $this->escola('Associação Comunitária Vila Nova', TipoEscola::Instituicao, 'Alegre'),
        ];

        $this->pessoas['mariana'] = $this->coordenador('Profa. Mariana Lopes', 'mariana.lopes', '1234501');
        $this->pessoas['ricardo'] = $this->coordenador('Prof. Ricardo Alves', 'ricardo.alves', '1234502');

        $this->pessoas['sonia'] = $this->demandante('Sônia Ribeiro', 'sonia.ribeiro', 'palmeiras', 'Diretora');
        $this->pessoas['joao'] = $this->demandante('João Batista Moreira', 'joao.moreira', 'corrego', 'Coordenador pedagógico');
        $this->pessoas['regina'] = $this->demandante('Regina Coutinho', 'regina.coutinho', 'pedra', 'Secretária escolar');
        $this->pessoas['lucia'] = $this->demandante('Lúcia Barros', 'lucia.barros', 'vilanova', 'Presidente da associação');

        $estudantes = [
            'ana' => ['Ana Clara Souza', 'Sistemas de Informação'],
            'bruno' => ['Bruno Henrique Lima', 'Ciência da Computação'],
            'camila' => ['Camila Ferreira', 'Sistemas de Informação'],
            'diego' => ['Diego Martins Rocha', 'Ciência da Computação'],
            'eduarda' => ['Eduarda Pires', 'Matemática'],
            'felipe' => ['Felipe Andrade', 'Ciência da Computação'],
        ];

        foreach ($estudantes as $chave => [$nome, $curso]) {
            $this->pessoas[$chave] = $this->estudante($nome, $chave, $curso);
        }

        // Fila de aprovação do admin: um estudante e um demandante com escola digitada no cadastro.
        $this->quando(2);
        $this->estudante('Gabriel Teixeira', 'gabriel', 'Sistemas de Informação', StatusUsuario::Pendente);
        $nova = $this->escola('EMEF Santa Luzia', TipoEscola::EscolaPublica, 'Cachoeiro de Itapemirim', ativa: false);
        $this->demandante('Paulo Sérgio Dias', 'paulo.dias', $nova, 'Diretor', StatusUsuario::Pendente);
    }

    private function criarProjetos(): void
    {
        $this->quando(190);

        $this->projetos = [
            'robotica' => $this->projeto('Robótica Educacional nas Escolas', 'Projeto', 'mariana',
                'Oficinas de robótica e programação com kits de baixo custo para estudantes do ensino fundamental e médio.'),
            'conecta' => $this->projeto('Conecta Escola: redes e infraestrutura', 'Prestação de Serviço', 'ricardo',
                'Diagnóstico, manutenção e melhoria das redes e dos computadores das escolas e instituições da região.'),
            'digital' => $this->projeto('Presença Digital das Escolas', 'Projeto', 'mariana',
                'Sites, sistemas simples e redes sociais para escolas e instituições comunitárias divulgarem e organizarem o seu trabalho.'),
            'formacao' => $this->projeto('Formação Tecnológica para Educadores', 'Curso', 'ricardo',
                'Cursos curtos para professores e educadores usarem tecnologia em sala de aula.'),
            'gerais' => ProjetoBase::where('titulo', ProjetoBase::DEMANDAS_GERAIS)->firstOrFail(),
        ];
    }

    /**
     * Uma demanda em cada etapa do ciclo, abertas ao longo dos últimos meses.
     */
    private function criarDemandas(): void
    {
        // Cadastradas: aguardam a coordenação.
        $this->abrir(3, 'sonia', 'conecta', 'Computadores do laboratório muito lentos',
            'Os 12 computadores do laboratório demoram mais de 10 minutos para ligar e travam ao abrir o navegador.',
            'O laboratório é usado em todas as aulas de pesquisa do ensino médio.', '420 alunos do ensino médio', anexo: true);

        $this->abrir(1, 'lucia', 'gerais', 'Planilha para controlar as doações',
            'Precisamos de uma planilha simples para registrar as doações de alimentos e roupas que recebemos e entregamos.',
            'Hoje o controle é feito em cadernos e se perde com facilidade.', '80 famílias atendidas pela associação');

        // Em análise, já classificada, com dúvidas no chat.
        $wifi = $this->abrir(10, 'joao', 'conecta', 'Wi-Fi não chega às salas do segundo andar',
            'O sinal do roteador da secretaria não alcança as quatro salas do segundo andar.',
            'Os professores não conseguem usar os tablets recebidos neste ano.', '6 turmas do ensino fundamental');
        $this->status($wifi, 9, StatusDemanda::EmAnalise);
        $this->triar($wifi, 9, 'Redes e conectividade', 'Alta', Complexidade::Media);
        $this->conversar($wifi, [
            [8, 'ricardo', 'Boa tarde! Vocês sabem qual é o modelo do roteador e se há tomadas no corredor do segundo andar?'],
            [8, 'joao', 'O roteador é o que veio da operadora. Há uma tomada no corredor, perto da sala 21.'],
        ]);

        // Priorizada, com o sub-projeto aberto na vitrine e candidaturas para avaliar.
        $robotica = $this->abrir(20, 'regina', 'robotica', 'Oficina de robótica com Arduino para o 8º ano',
            'Recebemos 5 kits de Arduino e gostaríamos de oficinas para as turmas do 8º ano, uma vez por semana.',
            'Os kits estão guardados sem uso desde o ano passado.', '2 turmas do 8º ano (54 alunos)');
        $this->status($robotica, 18, StatusDemanda::EmAnalise);
        $this->triar($robotica, 18, 'Capacitação e formação', 'Média', Complexidade::Media);
        $this->status($robotica, 15, StatusDemanda::Priorizada);
        $oficina = $this->subprojeto($robotica, 14, ['vagas' => 3, 'carga_horaria_estimada' => 30, 'modalidade' => Modalidade::Presencial,
            'data_inicio' => now()->addDays(20), 'data_fim' => now()->addDays(80)],
            'Planejar e conduzir 8 oficinas semanais de robótica com Arduino para as turmas do 8º ano.');
        $this->equipe($oficina, 12, ['diego' => 'Responsável Técnico']);
        $this->candidatar($oficina, 5, ['camila', 'felipe']);

        // Em desenvolvimento, com a primeira entrega aguardando a validação técnica.
        $site = $this->abrir(45, 'sonia', 'digital', 'Site institucional da escola',
            'Queremos um site com a apresentação da escola, o calendário, os avisos e os contatos.',
            'As famílias só recebem os avisos por bilhete e muitos se perdem.', 'Toda a comunidade escolar (cerca de 600 pessoas)');
        $this->status($site, 43, StatusDemanda::EmAnalise);
        $this->triar($site, 43, 'Site e presença digital', 'Média', Complexidade::Media);
        $this->status($site, 40, StatusDemanda::Priorizada);
        $siteSub = $this->subprojeto($site, 39, ['vagas' => 2, 'carga_horaria_estimada' => 60, 'modalidade' => Modalidade::Hibrido,
            'data_inicio' => now()->subDays(35), 'data_fim' => now()->addDays(25)],
            'Criar o site da escola com páginas de apresentação, calendário, avisos e contatos, e ensinar a secretaria a atualizar.');
        $this->equipe($siteSub, 37, ['ana' => 'Responsável Técnico', 'bruno' => 'Membro Desenvolvedor']);
        $this->status($site, 35, StatusDemanda::EmDesenvolvimento);
        $this->conversar($site, [
            [30, 'ana', 'Olá, Sônia! Vocês podem enviar o logotipo e algumas fotos da escola (sem alunos identificáveis)?'],
            [29, 'sonia', 'Claro, vou mandar pelo e-mail da coordenação ainda hoje.'],
        ]);
        $this->entregar($siteSub, 4, 'ana', TipoEntrega::Link, 'Protótipo das páginas',
            'Primeira versão com a página inicial, o calendário e os contatos.', url: 'https://prototipo.demo.pgdt.example/escola-vale-das-palmeiras');

        // Em validação: a primeira entrega foi devolvida e a segunda aguarda o aceite do demandante.
        $biblioteca = $this->abrir(70, 'joao', 'digital', 'Sistema de empréstimo da biblioteca',
            'A biblioteca controla os empréstimos em fichas de papel. Queremos registrar os livros, os alunos e as devoluções.',
            'Muitos livros não voltam porque não sabemos com quem estão.', '1.800 livros e 350 alunos');
        $this->status($biblioteca, 68, StatusDemanda::EmAnalise);
        $this->triar($biblioteca, 68, 'Desenvolvimento de sistema ou aplicativo', 'Alta', Complexidade::Alta);
        $this->status($biblioteca, 65, StatusDemanda::Priorizada);
        $bibliotecaSub = $this->subprojeto($biblioteca, 64, ['vagas' => 2, 'carga_horaria_estimada' => 80, 'modalidade' => Modalidade::Remoto,
            'data_inicio' => now()->subDays(60), 'data_fim' => now()->addDays(10)],
            'Desenvolver um sistema web simples de cadastro de livros e controle de empréstimos para a biblioteca.');
        $this->equipe($bibliotecaSub, 62, ['bruno' => 'Responsável Técnico', 'eduarda' => 'Membro Desenvolvedor']);
        $this->status($biblioteca, 60, StatusDemanda::EmDesenvolvimento);
        $primeira = $this->entregar($bibliotecaSub, 25, 'bruno', TipoEntrega::Link, 'Primeira versão do sistema',
            'Cadastro de livros e de empréstimos.', url: 'https://biblioteca.demo.pgdt.example');
        $this->validar($primeira, 23, 'mariana', DecisaoValidacao::Devolvida, 'Falta a busca por autor e o aviso de empréstimos atrasados.');
        $this->status($biblioteca, 23, StatusDemanda::AguardandoAjustes);
        $segunda = $this->entregar($bibliotecaSub, 8, 'eduarda', TipoEntrega::Link, 'Versão com busca e atrasos',
            'Inclui a busca por autor e a lista de empréstimos atrasados.', url: 'https://biblioteca.demo.pgdt.example/v2');
        $this->status($biblioteca, 7, StatusDemanda::EmValidacao);
        $this->validar($segunda, 6, 'mariana', DecisaoValidacao::Aprovada, 'Funcionando bem nos testes. Pode seguir para o aceite da escola.');

        // Aguardando ajustes: o demandante reportou uma pendência no aceite.
        $rede = $this->abrir(60, 'regina', 'conecta', 'Rede cabeada do laboratório de informática',
            'Metade dos pontos de rede do laboratório não funciona e os cabos estão soltos pelo chão.',
            'As provas on-line do estado são feitas no laboratório.', '10 computadores do laboratório');
        $this->status($rede, 58, StatusDemanda::EmAnalise);
        $this->triar($rede, 58, 'Redes e conectividade', 'Urgente', Complexidade::Media);
        $this->status($rede, 57, StatusDemanda::Priorizada);
        $redeSub = $this->subprojeto($rede, 57, ['vagas' => 2, 'carga_horaria_estimada' => 24, 'modalidade' => Modalidade::Presencial,
            'data_inicio' => now()->subDays(50), 'data_fim' => now()->addDays(5)],
            'Refazer a rede cabeada do laboratório, organizar os cabos e testar todos os pontos.');
        $this->equipe($redeSub, 55, ['diego' => 'Responsável Técnico', 'felipe' => 'Apoio de Campo']);
        $this->status($rede, 52, StatusDemanda::EmDesenvolvimento);
        $relatorioRede = $this->entregar($redeSub, 15, 'diego', TipoEntrega::Relatorio, 'Relatório da instalação',
            'Pontos refeitos, cabos organizados em canaletas e testes de cada ponto.', pdf: 'Relatório da instalação da rede cabeada');
        $this->status($rede, 14, StatusDemanda::EmValidacao);
        $this->validar($relatorioRede, 13, 'ricardo', DecisaoValidacao::Aprovada, 'Relatório completo. Aguardando a confirmação da escola.');
        $this->validar($relatorioRede, 11, 'regina', DecisaoValidacao::Pendencia, 'Os pontos 9 e 10, perto da janela, continuam sem sinal.');
        $this->status($rede, 10, StatusDemanda::AguardandoAjustes);

        // Concluídas: aparecem no dashboard e no relatório dos extensionistas.
        $manutencao = $this->abrir(150, 'sonia', 'conecta', 'Manutenção dos 15 computadores do laboratório',
            'Os computadores do laboratório precisam de limpeza, formatação e instalação dos programas usados nas aulas.',
            'Vários computadores estão parados por falta de manutenção.', '420 alunos do ensino médio');
        $this->status($manutencao, 148, StatusDemanda::EmAnalise);
        $this->triar($manutencao, 148, 'Manutenção de computadores e equipamentos', 'Alta', Complexidade::Baixa);
        $this->status($manutencao, 146, StatusDemanda::Priorizada);
        $manutencaoSub = $this->subprojeto($manutencao, 146, ['vagas' => 2, 'carga_horaria_estimada' => 20, 'modalidade' => Modalidade::Presencial,
            'data_inicio' => now()->subDays(140), 'data_fim' => now()->subDays(115)],
            'Limpar, formatar e configurar os 15 computadores do laboratório com os programas das aulas.');
        $this->equipe($manutencaoSub, 144, ['ana' => 'Responsável Técnico', 'diego' => 'Apoio de Campo']);
        $this->status($manutencao, 140, StatusDemanda::EmDesenvolvimento);
        $relatorioManutencao = $this->entregar($manutencaoSub, 118, 'ana', TipoEntrega::Relatorio, 'Relatório da manutenção',
            '15 computadores limpos e formatados, com os programas instalados.', pdf: 'Relatório da manutenção dos computadores');
        $this->status($manutencao, 117, StatusDemanda::EmValidacao);
        $this->validar($relatorioManutencao, 116, 'ricardo', DecisaoValidacao::Aprovada, null);
        $this->validar($relatorioManutencao, 114, 'sonia', DecisaoValidacao::Aceita, 'Todos os computadores funcionando. Muito obrigada!');
        $this->status($manutencao, 113, StatusDemanda::Concluida);

        $scratch = $this->abrir(120, 'lucia', 'formacao', 'Curso de introdução ao Scratch para os educadores',
            'Os educadores do reforço escolar querem aprender Scratch para criar atividades com as crianças.',
            'As crianças gostam de jogos e o Scratch ajudaria nas aulas de matemática.', '8 educadores e 60 crianças');
        $this->status($scratch, 117, StatusDemanda::EmAnalise);
        $this->triar($scratch, 117, 'Capacitação e formação', 'Baixa', Complexidade::Baixa);
        $this->status($scratch, 115, StatusDemanda::Priorizada);
        $scratchSub = $this->subprojeto($scratch, 114, ['vagas' => 2, 'carga_horaria_estimada' => 16, 'modalidade' => Modalidade::Presencial,
            'data_inicio' => now()->subDays(100), 'data_fim' => now()->subDays(70)],
            'Planejar e ministrar um curso de 4 encontros de introdução ao Scratch para os educadores da associação.');
        $this->equipe($scratchSub, 112, ['eduarda' => 'Responsável Técnico', 'camila' => 'Membro Desenvolvedor']);
        $this->status($scratch, 100, StatusDemanda::EmDesenvolvimento);
        $material = $this->entregar($scratchSub, 72, 'eduarda', TipoEntrega::Link, 'Material do curso',
            'Apostila e projetos de exemplo usados nos 4 encontros.', url: 'https://material.demo.pgdt.example/scratch');
        $this->status($scratch, 71, StatusDemanda::EmValidacao);
        $this->validar($material, 70, 'ricardo', DecisaoValidacao::Aprovada, null);
        $this->validar($material, 66, 'ricardo', DecisaoValidacao::Aceita, 'Aceite confirmado pela Lúcia por telefone.');
        $this->status($scratch, 65, StatusDemanda::Concluida);

        // Recusada e cancelada, com o motivo na linha do tempo.
        $impressora = $this->abrir(30, 'joao', 'gerais', 'Conserto da impressora da secretaria',
            'A impressora da secretaria parou de puxar o papel.', 'Precisamos imprimir os boletins.', 'Secretaria da escola');
        $this->status($impressora, 28, StatusDemanda::EmAnalise, ator: $this->admin);
        $this->status($impressora, 27, StatusDemanda::Recusada, 'O conserto de impressoras não faz parte dos projetos de extensão. A garantia ou a assistência técnica do fabricante é o melhor caminho.', ator: $this->admin);

        $cameras = $this->abrir(25, 'regina', 'conecta', 'Instalação de câmeras de segurança',
            'Gostaríamos de instalar câmeras no pátio e na entrada da escola.', 'Houve dois furtos neste semestre.', 'Toda a escola');
        $this->status($cameras, 22, StatusDemanda::Cancelada, 'A prefeitura vai fazer a instalação pelo contrato de segurança.', ator: $this->pessoas['regina']);
    }

    /**
     * Data e autor do próximo passo: a linha do tempo registra quem fez e quando.
     */
    private function quando(int $diasAtras, ?Usuario $ator = null): void
    {
        Carbon::setTestNow();
        $momento = now()->subDays($diasAtras)->setTime(rand(8, 16), rand(0, 59));

        if ($this->ultimoPasso && $momento->lessThanOrEqualTo($this->ultimoPasso)) {
            $momento = $this->ultimoPasso->copy()->addMinutes(rand(5, 40));
        }

        Carbon::setTestNow($this->ultimoPasso = $momento);

        $ator ? Auth::setUser($ator) : Auth::forgetUser();
    }

    private function email(string $usuario): string
    {
        return "{$usuario}@".self::DOMINIO;
    }

    private function escola(string $nome, TipoEscola $tipo, string $cidade, bool $ativa = true): EscolaInstituicao
    {
        return EscolaInstituicao::create(['nome' => $nome, 'tipo' => $tipo, 'cidade' => $cidade, 'uf' => 'ES', 'ativo' => $ativa]);
    }

    /**
     * @return array<string, mixed>
     */
    private function conta(string $nome, string $usuario, Perfil $perfil, StatusUsuario $status, ?string $telefone = null): array
    {
        return [
            'nome' => $nome,
            'email' => $this->email($usuario),
            'telefone' => $telefone,
            'password' => $this->senha,
            'perfil' => $perfil,
            'status' => $status,
            'email_verified_at' => now(),
            'aprovado_por' => $status === StatusUsuario::Ativo ? $this->admin->id : null,
            'aprovado_em' => $status === StatusUsuario::Ativo ? now() : null,
        ];
    }

    private function coordenador(string $nome, string $usuario, string $siape): Usuario
    {
        $conta = Usuario::create($this->conta($nome, $usuario, Perfil::Coordenador, StatusUsuario::Ativo));
        $conta->coordenador()->create(['siape' => $siape, 'departamento' => 'Departamento de Computação']);

        return $conta;
    }

    private function demandante(string $nome, string $usuario, string|EscolaInstituicao $escola, string $cargo, StatusUsuario $status = StatusUsuario::Ativo): Usuario
    {
        $conta = Usuario::create($this->conta($nome, $usuario, Perfil::Demandante, $status, '(28) 99999-'.str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT)));
        $conta->demandante()->create([
            'escola_instituicao_id' => ($escola instanceof EscolaInstituicao ? $escola : $this->escolas[$escola])->id,
            'cargo' => $cargo,
        ]);

        return $conta;
    }

    private function estudante(string $nome, string $usuario, string $curso, StatusUsuario $status = StatusUsuario::Ativo): Usuario
    {
        $conta = Usuario::create($this->conta($nome, $usuario, Perfil::Estudante, $status));
        $conta->estudante()->create([
            'matricula' => '2024'.str_pad((string) $conta->id, 6, '0', STR_PAD_LEFT),
            'curso_id' => Curso::firstOrCreate(['nome' => $curso])->id,
        ]);

        return $conta;
    }

    private function projeto(string $titulo, string $tipologia, string $coordenador, string $descricao): ProjetoBase
    {
        return ProjetoBase::create([
            'titulo' => $titulo,
            'descricao' => $descricao,
            'data_inicio' => now()->startOfYear(),
            'tipologia_id' => Tipologia::firstOrCreate(['nome' => $tipologia])->id,
            'coordenador_id' => $this->pessoas[$coordenador]->id,
            'publico' => true,
            'ativo' => true,
        ]);
    }

    /**
     * A coordenação que conduz a demanda: o coordenador do projeto ou, no "Demandas Gerais", o admin.
     */
    private function coordenacaoDe(Solicitacao $solicitacao): Usuario
    {
        return $solicitacao->projetoBase->coordenador ?? $this->admin;
    }

    private function abrir(int $diasAtras, string $demandante, string $projeto, string $titulo, string $descricao, string $justificativa, string $publico, bool $anexo = false): Solicitacao
    {
        $autor = $this->pessoas[$demandante];

        // Cada demanda tem a sua própria sequência de passos.
        $this->ultimoPasso = null;
        $this->quando($diasAtras, $autor);

        $solicitacao = Solicitacao::create([
            'projeto_base_id' => $this->projetos[$projeto]->id,
            'demandante_id' => $autor->id,
            'escola_instituicao_id' => $autor->demandante->escola_instituicao_id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'justificativa' => $justificativa,
            'publico_afetado' => $publico,
            'status' => StatusDemanda::Cadastrada,
        ]);

        if ($anexo) {
            $caminho = "solicitacoes/{$solicitacao->id}/".Str::random(40).'.pdf';
            Storage::disk('local')->put($caminho, $this->pdf("Ofício — {$titulo}", "A direção da {$autor->demandante->escolaInstituicao->nome} solicita apoio do projeto de extensão: {$descricao}"));

            $solicitacao->anexos()->create([
                'nome_original' => 'oficio-da-escola.pdf',
                'caminho' => $caminho,
                'mime' => 'application/pdf',
                'tamanho_bytes' => Storage::disk('local')->size($caminho),
                'enviado_por' => $autor->id,
            ]);
        }

        return $solicitacao;
    }

    private function status(Solicitacao $solicitacao, int $diasAtras, StatusDemanda $status, ?string $motivo = null, ?Usuario $ator = null): void
    {
        $this->quando($diasAtras, $ator ?? $this->coordenacaoDe($solicitacao));

        $solicitacao->update(['status' => $status, 'motivo_status' => $motivo]);
    }

    private function triar(Solicitacao $solicitacao, int $diasAtras, string $categoria, string $prioridade, Complexidade $complexidade): void
    {
        $ator = $this->coordenacaoDe($solicitacao);
        $this->quando($diasAtras, $ator);

        $solicitacao->update([
            'categoria_id' => CategoriaDemanda::where('nome', $categoria)->firstOrFail()->id,
            'prioridade_id' => Prioridade::where('nome', $prioridade)->firstOrFail()->id,
            'complexidade' => $complexidade,
            'triado_por' => $ator->id,
            'triado_em' => now(),
        ]);
    }

    /**
     * @param  list<array{0: int, 1: string, 2: string}>  $mensagens  dias atrás, autor e texto
     */
    private function conversar(Solicitacao $solicitacao, array $mensagens): void
    {
        foreach ($mensagens as [$diasAtras, $autor, $texto]) {
            $usuario = $this->pessoas[$autor];
            $this->quando($diasAtras, $usuario);

            $solicitacao->mensagens()->create(['usuario_id' => $usuario->id, 'perfil' => $usuario->perfil, 'texto' => $texto]);
        }
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function subprojeto(Solicitacao $solicitacao, int $diasAtras, array $dados, string $escopo): Subprojeto
    {
        $coordenador = $this->coordenacaoDe($solicitacao);
        $this->quando($diasAtras, $coordenador);

        $subprojeto = $solicitacao->subprojeto()->create([
            'titulo' => $solicitacao->titulo,
            'escopo' => $escopo,
            'coordenador_id' => $coordenador->id,
            'candidaturas_abertas' => true,
            ...$dados,
        ]);

        // A demanda passa a enxergar o sub-projeto recém-criado (equipe e coordenação).
        $solicitacao->unsetRelation('subprojeto');

        return $subprojeto;
    }

    /**
     * Cada estudante se candidata e a coordenação aprova, com o papel na equipe.
     *
     * @param  array<string, string>  $membros  estudante => papel
     */
    private function equipe(Subprojeto $subprojeto, int $diasAtras, array $membros): void
    {
        foreach ($membros as $estudante => $papel) {
            $candidatura = $this->candidatar($subprojeto, $diasAtras + 1, [$estudante])[0];

            $coordenador = $this->coordenacaoDe($subprojeto->solicitacao);
            $this->quando($diasAtras, $coordenador);

            $candidatura->update(['status' => StatusCandidatura::Aprovada, 'avaliado_por' => $coordenador->id, 'avaliado_em' => now()]);
            MembroEquipe::create([
                'subprojeto_id' => $subprojeto->id,
                'estudante_id' => $candidatura->estudante_id,
                'papel_id' => PapelEquipe::where('nome', $papel)->firstOrFail()->id,
                'candidatura_id' => $candidatura->id,
            ]);
        }

        // Equipe completa sai da vitrine, como a coordenação faria ao fechar as inscrições.
        if ($subprojeto->membrosAtivos()->count() >= $subprojeto->vagas) {
            $subprojeto->update(['candidaturas_abertas' => false]);
        }
    }

    /**
     * @param  list<string>  $estudantes
     * @return list<Candidatura>
     */
    private function candidatar(Subprojeto $subprojeto, int $diasAtras, array $estudantes): array
    {
        return array_map(function (string $estudante) use ($subprojeto, $diasAtras): Candidatura {
            $this->quando($diasAtras, $this->pessoas[$estudante]);

            return Candidatura::create([
                'subprojeto_id' => $subprojeto->id,
                'estudante_id' => $this->pessoas[$estudante]->id,
                'status' => StatusCandidatura::Pendente,
            ]);
        }, $estudantes);
    }

    private function entregar(Subprojeto $subprojeto, int $diasAtras, string $autor, TipoEntrega $tipo, string $titulo, string $descricao, ?string $url = null, ?string $pdf = null): Entrega
    {
        $this->quando($diasAtras, $this->pessoas[$autor]);

        $entrega = $subprojeto->entregas()->create([
            'numero' => $subprojeto->proximoNumeroDeEntrega(),
            'enviado_por' => $this->pessoas[$autor]->id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'url' => $url,
        ]);

        if ($pdf !== null) {
            $caminho = "entregas/{$entrega->id}/".Str::random(40).'.pdf';
            Storage::disk('local')->put($caminho, $this->pdf($pdf, $descricao));

            $entrega->arquivos()->create([
                'nome_original' => Str::slug($pdf).'.pdf',
                'caminho' => $caminho,
                'mime' => 'application/pdf',
                'tamanho_bytes' => Storage::disk('local')->size($caminho),
            ]);
        }

        return $entrega;
    }

    private function validar(Entrega $entrega, int $diasAtras, string $autor, DecisaoValidacao $decisao, ?string $feedback): void
    {
        $usuario = $this->pessoas[$autor];
        $this->quando($diasAtras, $usuario);

        $entrega->registrarValidacao($decisao, $feedback, $usuario);
    }

    /**
     * Arquivo PDF pequeno, só para a demonstração ter anexos e relatórios que abrem de verdade.
     */
    private function pdf(string $titulo, string $texto): string
    {
        return Pdf::loadHTML('<h2>'.e($titulo).'</h2><p>'.e($texto).'</p><p><em>Documento fictício gerado para a demonstração da plataforma.</em></p>')->output();
    }

    private function mostrarContas(): void
    {
        $this->command?->info('Dados de demonstração carregados. Contas (todas com a mesma senha):');
        $this->command?->table(['Perfil', 'Nome', 'E-mail', 'Status'], Usuario::query()
            ->where('email', 'like', '%@'.self::DOMINIO)
            ->orderBy('perfil')->orderBy('nome')
            ->get()
            ->map(fn (Usuario $usuario) => [$usuario->perfil->rotulo(), $usuario->nome, $usuario->email, $usuario->status->rotulo()])
            ->all());

        if (! config('pgdt.demonstracao.senha')) {
            $this->command?->warn("Senha gerada para as contas de demonstração (anote): {$this->senha}");
        }
    }
}
