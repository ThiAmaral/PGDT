# Plano em Fases — Plataforma de Gestão de Demandas Tecnológicas

Projeto de extensão "Rede de Apoio Tecnológico às Escolas" (UFES/CCENS — Alegre-ES).
Base: TCC1 de Paulo Henrique Bressanini Rizzi e o documento "Funcionalidades do sistema (RF)" (RF01–RF13), mais RF14 e RF15 incluídos pelo autor.

**Stack:** PHP + Laravel (MVC) · MySQL · Blade + Bootstrap + JavaScript · Apache/XAMPP (local) · Git + GitHub

**Perfis:** Administrador · Coordenador projeto de extensão · Demandante (escola/instituição) · Estudante Extensionista

**Termos:**
- **Projeto de extensão (Projeto Base):** a linha de extensão cadastrada pelo coordenador (RF05).
- **Sub-projeto:** uma demanda aceita, vinculada a um projeto de extensão e executada por uma equipe de estudantes (RF09).

**Legenda de status:** ⬜ não iniciada · 🟨 em andamento · ✅ concluída

---

## Visão geral

| Fase | Nome | RFs | Status |
|---|---|---|---|
| 0 | Decisões e preparação do ambiente | — | ✅ |
| 1 | Fundação, autenticação e usuários | RF01, RF02, RF03, RF04 | ✅ |
| 2 | Cadastros de apoio e Projetos Base | RF05 | ✅ |
| 3 | Demandas: abertura e triagem | RF06, RF07 | ✅ |
| 4 | Rastreabilidade e comunicação | RF08 | ✅ |
| 5 | Sub-projetos, vitrine pública e equipe | RF09, RF10, RF11, RF14 | ✅ |
| 6 | Entregas, validação e relatório | RF12, RF13, RF15 | ⬜ |
| 7 | Complementos (dashboard, notificações, auditoria) | antigos RF04/RF22/RF23 do TCC | ⬜ |
| 8 | Testes, validação com usuários e implantação | RNF01–RNF05 | ⬜ |

Cada fase termina com algo **utilizável e demonstrável**. Uma fase só começa depois que o critério de conclusão da anterior for atingido.

---

## Fase 0 — Decisões e preparação do ambiente ✅

**Objetivo:** fechar as lacunas dos requisitos que afetam o modelo de dados e deixar o ambiente pronto.

### Decisões aprovadas em 2026-10-18
O autor aprovou todas as propostas abaixo; o ciclo de status foi ajustado para mudanças só manuais.

- [x] **Ciclo de status da demanda:** incluir `Recusada/Não viável`, `Cancelada` e `Aguardando ajustes`? Quem muda cada status e se a mudança é automática.
  - *Decisão:* `Cadastrada` → `Em Análise` → `Priorizada` → `Em Desenvolvimento` → `Em Validação` → `Concluída`, mais `Aguardando Ajustes`, `Recusada` e `Cancelada`.
  - **Todas as mudanças de status são manuais** (decidido pelo autor em 2026-10-18). O sistema não muda o status sozinho.
  - Coordenador e admin: `Em Análise`, `Priorizada`, `Recusada` (motivo obrigatório), `Em Desenvolvimento`, `Em Validação`, `Aguardando Ajustes` e `Concluída`.
  - `Cancelada`: demandante, só antes de `Em Desenvolvimento`; admin, a qualquer momento.
  - Ações como criar o sub-projeto, enviar uma entrega ou homologar não mudam o status, mas a tela **sugere** o próximo status ao coordenador (ex.: "Entrega recebida. Mudar para Em Validação?").
  - Toda mudança fica no histórico (RF08) com quem mudou e quando.
- [x] **Usuário pendente (estudante e demandante):** pode fazer login e ver uma tela de "aguardando aprovação" ou fica bloqueado? Recebe e-mail quando é aprovado?
  - *Decisão:* faz login e vê só a tela "Cadastro em análise". Recebe e-mail na aprovação e na rejeição (com o motivo). `Rejeitado` e `Inativo` veem uma mensagem clara ao tentar entrar.
- [x] **Estudante:** aceitar só `@edu.ufes.br` ou também `@ufes.br` (domínio dos servidores)? Exigir confirmação de e-mail?
  - *Decisão:* só `@edu.ufes.br`, com confirmação de e-mail antes de ir para a fila de aprovação do admin.
- [x] **Demanda fora de qualquer Projeto Base:** existe um projeto base "Geral/Outros"?
  - *Decisão:* sim, um Projeto Base "Demandas Gerais" criado pelo seeder; o admin define o coordenador dele.
- [x] **Complexidade (RF07):** é um campo da triagem? Quais valores?
  - *Decisão:* sim, campo obrigatório da triagem: `Baixa`, `Média`, `Alta`.
- [x] **Modalidade Remoto/Presencial (RF10):** fica no Projeto Base ou no sub-projeto (RF09)?
  - *Decisão:* no sub-projeto, com os valores `Remoto`, `Presencial` e `Híbrido`, porque cada demanda tem a sua necessidade.
- [x] **Vagas (RF09):** existe um número máximo de vagas por sub-projeto?
  - *Decisão:* o coordenador define as vagas de cada sub-projeto (mínimo 1). Quando a equipe completa, a candidatura fecha. Sem limite global.
- [x] **Acesso ao RF08:** estudantes da equipe e admin também veem o histórico e o chat?
  - *Decisão:* sim. O admin vê tudo; o estudante vê o histórico e o chat só dos sub-projetos em que é membro aprovado.
- [x] **Limites de upload (RF12):** tipos e tamanho máximo das entregas.
  - *Decisão:* PDF, DOCX, JPG, PNG e ZIP, até 20 MB por arquivo e 5 arquivos por entrega; links validados como URL.
- [x] **Kit de autenticação:** o Laravel Breeze usa Tailwind, e o plano usa Bootstrap.
  - *Decisão:* usar `laravel/ui` com o preset Bootstrap (`php artisan ui bootstrap --auth`) como base e adaptar os cadastros por perfil.

### Decisões respondidas anteriormente
- [x] **Coordenador x Professor Orientador:** são o mesmo perfil, chamado **Coordenador projeto de extensão**.
- [x] **Aprovação de estudantes (RF04):** o estudante também fica `Pendente` até o admin aprovar.
- [x] **Carga horária (RF15):** uma estimativa por **sub-projeto**, preenchida pelo Admin ou pelo Coordenador projeto de extensão e editável em qualquer status, mesmo depois de `Concluída`.
- [x] **Projeto Base x sub-projeto:** são entidades distintas. A demanda vinculada a um projeto de extensão vira um sub-projeto.

### Ambiente (atualizado em 2026-10-18)

| Ferramenta | Situação |
|---|---|
| PHP | ✅ 8.3.33 (winget), no PATH. Extensões ativadas: `openssl`, `pdo_mysql`, `mysqli`, `curl`, `zip`, `intl`, `gd`, `sodium`. Backup do `php.ini` original: `php.ini.bak-2026-10-18` |
| Composer | ✅ 2.10.3 em `%LOCALAPPDATA%\Composer\bin` (no PATH do usuário). O `composer.phar` foi conferido pelo SHA-256 oficial. O `Composer-Setup.exe` falhou no modo silencioso |
| XAMPP | ✅ 8.2.12 em `C:\xampp` (winget): Apache, MariaDB 10.4.32 e phpMyAdmin. O PHP 8.2 do XAMPP não está no PATH; o projeto usa o PHP 8.3 |
| Banco | ✅ banco `pgdt` criado (utf8mb4); conexão PDO do PHP 8.3 testada. Usuário `root` sem senha (padrão do XAMPP, só para uso local) |
| Node.js | ✅ v24.16.0 |
| Git | ✅ 2.49.0, já configurado com nome e e-mail |
| GitHub CLI (`gh`) | ➖ não instalado (opcional; o repositório pode ser criado pelo site) |

Instalação em uma máquina nova: [`docs/INSTALACAO.md`](docs/INSTALACAO.md).

Para ligar o banco: abrir o **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`) e clicar em **Start** no MySQL.

### Tarefas
- [x] Instalar PHP 8.2+, Composer, Node.js, XAMPP (Apache + MySQL) e Git.
- [x] Criar o repositório no GitHub e definir o fluxo de branches.
  - Repositório: https://github.com/ThiAmaral/PGDT (remoto `origin`), com a raiz na pasta `PGDT/`.
  - Fluxo: `main` sempre funcionando; cada entrega em uma branch `feature/<fase>-<assunto>` (ex.: `feature/fase1-cadastros`), juntada na `main` quando o critério da etapa for atingido.
- [x] Criar o projeto Laravel e configurar `.env` e o banco local.
  - Laravel 13.37 em `PGDT/sistema/`; o plano fica na raiz do repositório.
  - `.env`: `APP_NAME=PGDT`, `APP_LOCALE=pt_BR`, MySQL `pgdt` (root, sem senha); fuso `America/Sao_Paulo` em `config/app.php`.
  - `laravel/ui` 4.6 com Bootstrap 5 e telas de login, cadastro e recuperação de senha; Tailwind removido; `axios` adicionado; página inicial própria em Bootstrap.
  - Migrations padrão rodadas no MySQL; `php artisan serve` responde em `/`, `/login` e `/register`.
  - ⚠️ O OneDrive marca as pastas como "somente leitura" e o PHP recusa gravar nelas. O atributo foi removido de `bootstrap/cache` e `storage/`; se o erro "directory must be present and writable" voltar, repetir esse passo.
  - ℹ️ `php artisan db:show` falha no MariaDB 10.4 do XAMPP (falta `performance_schema`). É só o comando de diagnóstico; o sistema funciona.
- [x] Escolher o kit de autenticação e o Bootstrap: `laravel/ui` com o preset Bootstrap.
- [x] Revisar o DER completo com as entidades que faltam: [`docs/DER.md`](docs/DER.md) (diagrama Mermaid + dicionário de dados). Os 4 pontos da seção 4 foram aprovados pelo autor.

**Critério de conclusão:** decisões registradas neste documento; `php artisan serve` abre a página inicial conectada ao MySQL; repositório com o primeiro commit.

---

## Fase 1 — Fundação, autenticação e usuários ✅

**RFs:** RF01 Cadastro Acadêmico · RF02 Cadastro Institucional · RF03 Acesso e Recuperação · RF04 Administração

### Separação dos cadastros (decidido em 2026-10-18)

| Perfil | Como entra no sistema | Rota |
|---|---|---|
| Estudante | Autocadastro em formulário próprio, fica `Pendente` até o admin aprovar | `/cadastro/estudante` |
| Demandante | Autocadastro em formulário próprio, fica `Pendente` até o admin aprovar | `/cadastro/demandante` |
| Coordenador projeto de extensão | **Sem autocadastro.** O admin envia um convite por e-mail (link para definir a senha) ou troca o perfil de um usuário existente (RF04) | `/convite/{token}` |
| Administrador | Seeder inicial; outros admins só pelo painel | — |

Regras para evitar erro do usuário:
- **Login único** para todos: o perfil vem da conta, o usuário nunca escolhe o perfil ao entrar.
- **"Criar conta" abre uma tela de escolha** com dois cartões grandes e textos claros: "Sou estudante da UFES" e "Represento uma escola ou instituição". Não existe um campo "perfil" dentro do formulário.
- **Cada formulário mostra só os campos do seu perfil** e tem um título fixo ("Cadastro de Estudante") com o link "Não é o seu caso? Trocar tipo de cadastro".
- **O e-mail confirma a escolha:** e-mail institucional da UFES no formulário de demandante gera o aviso "Parece que você é da UFES. Quer se cadastrar como estudante?"; e-mail de fora da UFES no formulário de estudante é bloqueado com um link para o cadastro de demandante.
- **Links diretos para divulgação:** as escolas recebem o link `/cadastro/demandante`; os estudantes, o link `/cadastro/estudante`.
- **Mesmo modelo de dados:** uma única tabela `usuarios` com `perfil`; os campos específicos ficam nas tabelas de cada perfil.

### Entregáveis
- [x] Layout base responsivo (menu por perfil, tela de login).
- [x] Tabelas (ver `docs/DER.md`): `usuarios` (perfil, status, telefone), `estudantes` (matrícula, curso), `demandantes` (escola, cargo), `coordenadores`, `escolas_instituicoes`, `cursos`, `convites`.
- [x] Tela "Criar conta" com a escolha do tipo de cadastro (Estudante / Demandante).
- [x] RF01: formulário do estudante com validação de domínio; perfil Estudante atribuído automaticamente; status `Pendente`.
- [x] RF02: formulário do demandante (escola, cargo, telefone com máscara); status `Pendente`.
- [x] Cadastro do Coordenador projeto de extensão por convite do admin (e-mail com link de uso único e prazo de validade); a conta já nasce `Ativo`.
- [x] RF03: login, logout, "esqueci minha senha" com token por e-mail e redefinição.
- [x] RF04: painel do admin com filtros (nome, e-mail, perfil, status). O admin aprova os usuários, altera o status do usuário (`Ativo`, `Inativo`, `Pendente`, `Rejeitado`) e troca o papel/perfil dos usuários.
  - Só usuários `Ativo` conseguem usar o sistema; `Inativo` e `Rejeitado` não fazem login.
  - Rejeitar e inativar pedem confirmação; a troca de perfil mostra o perfil atual e o novo antes de salvar.
- [x] Middleware/policies de controle de acesso por perfil (RNF04).
- [x] Seeder com um usuário administrador inicial.

### Como foi implementado (2026-10-18)
- **Código:** branch `feature/fase1-usuarios`. Model `Usuario` (tabela `usuarios`) no lugar do `User` padrão; enums `Perfil`, `StatusUsuario` e `TipoEscola` em `app/Enums`.
- **Acesso:** middleware `perfil:admin,...` (`VerificaPerfil`) e `ativo` (`ContaAtiva`): `Pendente` vai para "Cadastro em análise"; `Inativo`/`Rejeitado` são desconectados com a mensagem e o motivo. O login também bloqueia essas contas.
- **Rotas:** `/cadastro` (escolha), `/cadastro/estudante`, `/cadastro/demandante`, `/convite/{token}`, `/painel`, `/admin/usuarios`, `/admin/convites`. `/register` e `/home` redirecionam para as rotas novas.
- **Convite:** token de 64 caracteres; no banco fica só o SHA-256. Validade de 7 dias, uso único; um convite novo para o mesmo e-mail cancela o anterior; o admin pode cancelar.
- **E-mails:** confirmação de e-mail (estudante), aprovação, rejeição com motivo, convite e recuperação de senha, em pt-BR. Em ambiente local o `MAIL_MAILER=log` grava os e-mails em `storage/logs/laravel.log`.
- **Seeders:** `CursoSeeder` (16 cursos do campus de Alegre) e `AdminSeeder` (usa `ADMIN_NOME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` do `.env`; sem senha, gera uma aleatória e mostra no terminal).
- **Testes:** 34 testes de feature (`CadastroTest`, `LoginTest`, `AdminUsuariosTest`, `ConviteTest`) passando no SQLite em memória.
- **Teste manual no navegador:** cadastro de demandante com escola nova, aviso de e-mail da UFES, máscara de telefone, tela "Cadastro em análise", login do admin, aprovação com e-mail registrado no log e layout no celular.

**Critério de conclusão:** os 4 perfis conseguem se cadastrar ou ser criados, fazer login e ver apenas o seu menu; o admin aprova um estudante e um demandante pendentes.

---

## Fase 2 — Cadastros de apoio e Projetos Base ✅

**RF:** RF05 Cadastro de Projetos Base

### Entregáveis
- [x] Tabelas de domínio: `tipologias`, `categorias_demanda`, `prioridades` (com seeders).
- [x] CRUD de Projetos Base (título, descrição, escopo preliminar, datas, tipologia, coordenador responsável).
- [x] Listagem de Projetos Base (usada pelo Demandante na Fase 3 e pela página pública do RF14).
- [x] Permissão: só o Coordenador projeto de extensão e o Admin cadastram e editam.

### Como foi implementado (2026-10-21)
- **Código:** branch `feature/fase2-projetos-base`. Migration `2026_10_21_000001_create_projetos_base_tables`; models `Tipologia`, `CategoriaDemanda`, `Prioridade` (trait `ItemDeDominio`) e `ProjetoBase`; `ProjetoBasePolicy`.
- **Rotas:** `/projetos-base` (lista, novo, detalhes, editar) e `/admin/cadastros/{tipologias|categorias|prioridades}`.
- **Cadastros de apoio (admin):** incluir, renomear e ativar/inativar; prioridades têm `ordem` (menor primeiro). Nenhum item é apagado, para não quebrar os registros que já o usam.
- **Seeders:** `DominioSeeder` (tipologias: Programa, Projeto, Curso, Oficina, Evento, Prestação de Serviço; 7 categorias; prioridades Urgente, Alta, Média, Baixa) e `ProjetoBaseSeeder` ("Demandas Gerais").
- **Regras:**
  - O coordenador que cadastra vira o responsável; só o admin escolhe ou troca o coordenador (precisa ser um coordenador `Ativo`).
  - O coordenador edita só os projetos que coordena; o admin edita todos.
  - Projeto Base não é apagado: desmarcar "Ativo" tira o projeto da seleção de demandas (`ProjetoBase::disponiveis()`). "Público" controla a página do RF14.
  - Demandante e estudante veem a lista "Projetos de extensão" só com os ativos, sem botões de edição.
  - "Demandas Gerais" nasce sem coordenador; o admin vê um aviso na lista até definir quem coordena.
  - A data de término é opcional e não pode ser anterior à de início. Uma tipologia inativada continua valendo nos projetos que já a usam.
- **Testes:** 19 testes novos (`ProjetoBaseTest`, `DominioTest`); 53 no total, todos passando.
- **Teste manual no navegador (admin):** lista com o aviso de "Demandas Gerais" sem coordenador, formulário com validação em pt-BR, detalhes, edição pré-preenchida, cadastros de apoio e layout no celular e no computador. Corrigidos: as chaves "Público"/"Ativo" voltavam marcadas depois de um erro de validação, e a ordem das prioridades aparecia sem rótulo.

**Critério de conclusão:** um coordenador cadastra um Projeto Base e ele aparece disponível para seleção.

---

## Fase 3 — Demandas: abertura e triagem ✅

**RFs:** RF06 Abertura de Solicitações · RF07 Triagem e Parametrização

### Entregáveis
- [x] Tabelas `solicitacoes` e `anexos` (um para muitos).
- [x] RF06: formulário do Demandante com seleção do Projeto Base, título (máx. 100 caracteres), descrição, justificativa, público afetado e upload drag-and-drop (PDF/JPG/PNG, até 5 MB).
- [x] Armazenamento seguro dos arquivos (`storage`, fora do diretório público, com download autorizado).
- [x] "Minhas demandas" para o Demandante.
- [x] RF07: fila do coordenador (status `Cadastrada`), tela de detalhes, definição de categoria, prioridade e complexidade.
- [x] Transições de status iniciais: Cadastrada → Em Análise → Priorizada (e Recusada, se decidido na Fase 0).

### Como foi implementado (2026-10-21)
- **Código:** branch `feature/fase3-demandas`. Migration `2026_10_21_000002_create_solicitacoes_tables`; enums `StatusDemanda` e `Complexidade`; models `Solicitacao` e `Anexo`; `SolicitacaoPolicy`; `SolicitacaoController`.
- **Rotas:** `/demandas` (lista: "Minhas demandas" para o demandante, fila para coordenador e admin), `/demandas/create`, `/demandas/{id}` (detalhes), `PATCH /demandas/{id}/triagem`, `PATCH /demandas/{id}/status` e `/demandas/{id}/anexos/{anexo}` (download).
- **Abertura (RF06):** o demandante escolhe um Projeto Base ativo (a descrição do projeto aparece ao escolher), com contador no título. A escola é copiada do cadastro do demandante. Anexos são opcionais: até 5 arquivos PDF/JPG/PNG de até 5 MB, conferidos no navegador (tipo, tamanho, quantidade, repetidos) e de novo no servidor; os erros citam o nome do arquivo.
- **Arquivos:** gravados no disco `local` (`storage/app/private/solicitacoes/{id}`), fora da pasta pública, com nome aleatório. O download passa pela policy (só quem vê a demanda) e devolve o nome original. Se a gravação da demanda falhar, os arquivos já salvos são apagados.
- **Fila (RF07):** abre nas demandas `Cadastrada`; filtros por título, status, prioridade e projeto. Ordem: prioridade (pela `ordem` do cadastro de apoio; sem prioridade por último) e depois a mais antiga.
- **Triagem:** categoria, prioridade e complexidade (`Baixa`, `Média`, `Alta`), todas obrigatórias; grava quem classificou e quando. Pode ser revista enquanto a demanda está `Cadastrada`, `Em Análise` ou `Priorizada`. Salvar a triagem não muda o status.
- **Status (sempre manual):** coordenador e admin: `Cadastrada` → `Em Análise` ou `Recusada`; `Em Análise` → `Priorizada` (só depois da triagem) ou `Recusada`; `Priorizada` → `Em Análise` ou `Recusada`; `Recusada` → `Em Análise` (reabrir). `Recusada` e `Cancelada` exigem motivo, que o demandante vê. O demandante cancela até `Priorizada`; o admin cancela a qualquer momento. A tela sugere o próximo passo ao coordenador.
- **Painel:** admin e coordenador veem o número de demandas novas; o demandante vê as 5 últimas demandas e o botão "Nova demanda". Menu: "Demandas" (admin e coordenador) e "Minhas demandas" (demandante).
- **Testes:** 24 testes novos (`SolicitacaoTest`); 77 no total, todos passando.
- **Teste manual no navegador:** coordenador cadastrou um Projeto Base de teste; demandante de teste criado pelo cadastro e aprovado pelo admin; demanda aberta com dois anexos pelo arrastar-e-soltar (arquivo `.xlsx` e PDF de 6 MB barrados no navegador); download conferido; admin passou a demanda por Em Análise → classificação → Priorizada; demandante viu o status e a classificação no celular. Corrigidos: tamanho do arquivo no formato brasileiro ("1,4 MB") e o cancelamento do demandante passou a ficar recolhido. A triagem no navegador foi feita como admin (a senha do coordenador de teste não está registrada); o fluxo do coordenador está coberto pelos testes.
- **Conta de teste:** o e-mail e a senha do demandante de teste estão no `sistema/.env` local (`TESTE_DEMANDANTE_*`), que não vai para o GitHub.

**Critério de conclusão:** um demandante abre uma demanda com anexo e um coordenador a classifica e prioriza.

---

## Fase 4 — Rastreabilidade e comunicação ✅

**RF:** RF08

### Entregáveis
- [x] Tabelas `historicos` e `mensagens`.
- [x] Registro automático no histórico a cada mudança de status ou ação relevante (via Observer/Event do Laravel).
- [x] Barra de progresso visual na tela da demanda.
- [x] Linha do tempo cronológica do histórico.
- [x] Chat da demanda (autor, perfil, data/hora, texto) com o botão "Enviar Comentário".
- [x] Acesso restrito aos envolvidos (policy).

### Como foi implementado (2026-10-21)
- **Código:** branch `feature/fase4-rastreabilidade`. Migration `2026_10_21_000003_create_historicos_mensagens_tables`; enum `TipoHistorico`; models `Historico` e `Mensagem`; `SolicitacaoObserver`; `MensagemController`; parciais `_progresso`, `_linha-do-tempo` e `_chat` na tela da demanda.
- **Histórico automático:** o `SolicitacaoObserver` grava a abertura ("Demanda aberta"), cada mudança de status (de/para e o motivo, quando há) e cada classificação ("Demanda classificada" e, nas revisões, "Classificação revista" só com o que mudou). Guarda os nomes da categoria e da prioridade, para a linha do tempo não mudar se o admin renomear um item. Status e histórico são gravados na mesma transação (`updateOrFail`). O histórico não pode ser editado nem apagado (o model recusa).
- **Demandas antigas:** a migration criou o registro "Demanda aberta" para as demandas que já existiam; as mudanças de status feitas antes desta fase não têm registro.
- **Barra de progresso:** Cadastrada → Em Análise → Priorizada → Em Desenvolvimento → Em Validação → Concluída. "Aguardando Ajustes" aparece na etapa de validação. Recusada ou cancelada, a barra marca com ✕ a etapa em que a demanda parou (tirada do histórico). No celular, só o nome da etapa atual aparece.
- **Linha do tempo:** na coluna da direita, da mais antiga para a mais recente, com quem fez e quando.
- **Chat:** rota `POST /demandas/{id}/mensagens` (limite de 20 envios por minuto). Mostra autor, perfil, data/hora e texto; as mensagens do próprio usuário ficam à direita. Até 2.000 caracteres. O perfil do autor é guardado na mensagem.
- **Acesso:** quem vê a demanda (demandante dela, coordenador do projeto e admin) vê o histórico e o chat; o estudante entra na Fase 5, quando houver equipe.
- **Testes:** 16 testes novos (`RastreabilidadeTest`); 93 no total, todos passando.
- **Teste no navegador:** como demandante, enviei um comentário na demanda de teste; como admin, respondi, passei a demanda para Em Análise e de volta para Priorizada, e as duas mudanças apareceram na linha do tempo. Conferido também no celular. Corrigido: espaços em branco a mais dentro do balão da mensagem.

### Decisões de implementação (aprovadas pelo autor em 2026-10-21)
- (a) Mensagens do chat não podem ser editadas nem apagadas, para a conversa valer como registro.
- (b) O chat fica só para leitura quando a demanda está `Concluída` ou `Cancelada`; em `Recusada` continua aberto, porque a demanda pode ser reaberta.
- (c) Todos os envolvidos veem na linha do tempo o nome de quem fez cada ação (inclusive o demandante, que antes não via quem fez a triagem).
- (d) `mensagens.perfil` guarda o perfil do autor no envio; `historicos.usuario_id` aceita nulo para mudanças feitas fora de uma sessão de usuário (DER atualizado).
- (e) Sem aviso de nova mensagem por e-mail ou no painel nesta fase; as notificações ficam para a Fase 7.

**Critério de conclusão:** demandante e coordenador trocam mensagens em uma demanda e veem cada mudança de status registrada.

---

## Fase 5 — Sub-projetos, vitrine pública e equipe ✅

**RFs:** RF09 Criação de Sub-projetos · RF10 Vitrine de Oportunidades · RF11 Gestão de Lotação · RF14 Consulta Pública de Projetos

### Entregáveis
- [x] Tabelas `subprojetos` (1:1 com a solicitação; o Projeto Base vem dela; data de início, data de fim e carga horária estimada), `candidaturas`, `papeis_equipe`, `membros_equipe` (com papel e `removido_em`).
- [x] RF09: converter a demanda em sub-projeto do projeto de extensão (campos pré-preenchidos, escopo obrigatório, data de início e de fim, carga horária estimada, vagas, modalidade); o coordenador logado vira o responsável; ao salvar, a tela sugere mudar a demanda para `Em Desenvolvimento` (mudança manual).
  - A carga horária estimada é preenchida pelo Admin ou pelo Coordenador projeto de extensão e pode ser alterada em qualquer status, inclusive depois de `Concluída`.
  - Cada alteração da carga horária fica registrada no histórico (RF08): valor anterior, valor novo, quem alterou e quando.
- [x] RF10: vitrine em cards com busca, filtro por modalidade e candidatura com um clique.
- [x] RF11: tabela de candidaturas pendentes (Aprovar/Recusar), equipe com papéis (Membro Desenvolvedor, Responsável Técnico, funções de campo) e remoção de membros.
- [x] RF14: página pública (`/projetos`), sem login, com os projetos de extensão cadastrados e os sub-projetos com vagas abertas (busca e filtros iguais aos da vitrine).
  - Mostra só dados públicos: título, descrição, coordenador, modalidade, período e vagas. Contatos do demandante, anexos e chat ficam fora (LGPD).
  - O botão "Quero participar" leva ao login. Depois do login, a pessoa volta para o mesmo projeto e conclui a inscrição com um clique. A tela de login tem o link "Ainda não tem conta? Criar conta".
  - Só o perfil Estudante com status `Ativo` pode se inscrever; para os outros o botão aparece desabilitado com a explicação.

### Como foi implementado (2026-10-21)
- **Código:** branch `feature/fase5-subprojetos`. Migration `2026_10_21_000004_create_subprojetos_tables` (cria também os 3 papéis iniciais); enums `Modalidade` e `StatusCandidatura`; models `Subprojeto`, `Candidatura`, `PapelEquipe` e `MembroEquipe`; observers `SubprojetoObserver` e `MembroEquipeObserver`; `SubprojetoPolicy`; controllers `SubprojetoController`, `CandidaturaController`, `MembroEquipeController`, `OportunidadeController` e `ProjetoPublicoController`.
- **RF09:** na demanda "Priorizada", o card "Sub-projeto" leva ao formulário (`/demandas/{id}/subprojeto/criar`) com título e escopo vindos da demanda. Depois de salvar, a tela da demanda sugere "Em Desenvolvimento" (já vem escolhido na caixa de status). A tela do sub-projeto (`/subprojetos/{id}`) mostra candidaturas pendentes, equipe e dados; "Editar" altera os dados e a opção "Receber candidaturas".
- **Carga horária:** cada alteração grava "Carga horária estimada alterada de X h para Y h" na linha do tempo, com `{"de": X, "para": Y}`, quem e quando. Com a demanda encerrada, a tela de edição mostra só esse campo.
- **RF10:** menu "Oportunidades" do estudante (`/oportunidades`): cards com busca (título ou escopo), filtro por modalidade e por projeto. O card leva à página do sub-projeto, onde "Confirmar candidatura" envia com um clique. "Meus sub-projetos" mostra as equipes e as candidaturas com a situação.
- **RF11:** aprovar (escolhendo o papel), recusar, trocar papel e remover membro, com confirmação. A aprovação trava o sub-projeto no banco para duas aprovações simultâneas não passarem das vagas. O admin cadastra novos papéis em "Cadastros de apoio" → "Papéis da equipe".
- **RF14:** `/projetos` e `/projetos/{id}` sem login, com o link "Projetos e vagas" no menu dos visitantes e na página inicial. "Quero participar" leva ao login (com o aviso "Entre com a sua conta de estudante para concluir a inscrição") e, depois do login, de volta ao sub-projeto. Quem não pode se inscrever vê o botão desabilitado com o motivo.
- **Painel:** admin e coordenador veem o total de candidaturas pendentes; o estudante vê as vagas abertas e as suas equipes. Menu "Sub-projetos" para admin e coordenador.
- **Testes:** 34 testes novos (`SubprojetoTest`, `EquipeTest`, `ConsultaPublicaTest`), incluindo o critério de conclusão de ponta a ponta; 127 no total, todos passando.
- **Teste no navegador:** como admin, criei o sub-projeto da demanda de teste (presencial, 2 vagas, 40 h). Como visitante, cliquei em "Quero participar", criei a estudante de teste pelo cadastro, confirmei o e-mail (link no `laravel.log`) e, ainda pendente, vi o botão desabilitado com o motivo. Depois da aprovação pelo admin, entrei, voltei ao sub-projeto e me candidatei com um clique. Como admin, aprovei como Responsável Técnico, corrigi a carga horária para 48 h e conferi as duas ações na linha do tempo. Conferido também no celular. A conta de teste da estudante está anotada no `.env` local.
- **Ajuste durante o teste:** quando o admin cria o sub-projeto, o responsável passa a ser o coordenador do projeto (a página pública mostraria "Administrador" como coordenação).

### Decisões de implementação (aprovadas pelo autor em 2026-10-21)
- (a) O sub-projeto só é criado com a demanda "Priorizada", uma vez por demanda. Criar não muda o status; "Em Desenvolvimento" só aparece na lista depois que o sub-projeto existe.
- (b) Responsável: o coordenador que cria. Se quem cria é o admin, fica o coordenador do Projeto Base (ou o admin, se o projeto não tiver coordenador). O responsável também gerencia a demanda e a equipe, mesmo que o admin troque depois o coordenador do Projeto Base.
- (c) Com a demanda "Concluída", "Cancelada" ou "Recusada", o sub-projeto sai da vitrine e os dados e a equipe não mudam mais; só a carga horária pode ser corrigida.
- (d) O sub-projeto aparece na vitrine enquanto "Receber candidaturas" estiver marcado, houver vagas livres, a demanda estiver em andamento e o Projeto Base estiver ativo. Com a equipe completa, sai da vitrine sozinho e volta se alguém sair. As vagas não podem ficar abaixo do número de membros.
- (e) O estudante pode cancelar a candidatura enquanto ela não foi avaliada e se candidatar de novo depois. Quem foi recusado ou removido não se candidata de novo ao mesmo sub-projeto.
- (f) Na aprovação, o papel sugerido é "Membro Desenvolvedor". Entrada, troca de papel e remoção de membros vão para a linha do tempo da demanda; as candidaturas não (ficam na tela do sub-projeto).
- (g) O estudante da equipe vê a demanda (descrição, anexos, linha do tempo e chat) e conversa no chat, mas não vê o e-mail e o telefone do demandante. Removido da equipe, perde o acesso.
- (h) A página pública mostra título, escopo, projeto, coordenador, modalidade, período e vagas. A carga horária aparece só na vitrine, para quem está logado. Projetos ocultos (`publico` desmarcado) e os seus sub-projetos ficam fora.
- (i) O escopo vem preenchido com a descrição da demanda e o formulário avisa que ele vai para a página pública, para não incluir dados pessoais.

**Critério de conclusão:** um visitante sem login vê os projetos, clica em "Quero participar", faz login, se candidata, é aprovado e aparece na equipe com um papel.

---

## Fase 6 — Entregas, validação e relatório ⬜

**RFs:** RF12 Submissão de Entregas · RF13 Validação e Homologação · RF15 Relatório de Demandas por Extensionista

### Entregáveis
- [ ] Tabelas `entregas`, `entrega_arquivos` (até 5 por entrega) e `validacoes` (etapa, decisão, feedback, autor, data).
- [ ] RF12: formulário com tipo de entrega dinâmico (link/repositório, relatório técnico, arquivos/fotos), validação de formato e tamanho; só membros aprovados enviam.
- [ ] RF13 etapa 1: coordenador escolhe "Aprovar Tecnicamente" ou "Devolver para Ajustes" (feedback obrigatório).
- [ ] RF13 etapa 2: demandante escolhe "Homologar Solução" ou "Reportar Pendência".
- [ ] Depois da homologação, o coordenador ou o admin muda a demanda para `Concluída`; depois de uma devolução ou pendência, muda para `Aguardando Ajustes` e a demanda volta para a equipe. As mudanças são manuais, com sugestão na tela.
- [ ] Tela de mudança de status (coordenador e admin) com confirmação e registro no histórico.
- [ ] RF15: relatório de demandas executadas por extensionista. Para cada sub-projeto de que o estudante participou: título, projeto de extensão, escola/instituição, papel do estudante, **data de início**, **data de fim** e **carga horária estimada** do sub-projeto, com o total de horas no final.
  - Os valores vêm do sub-projeto (RF09); se o Admin ou o coordenador corrigir a carga horária, o relatório já sai com o valor novo.
  - Filtros: estudante, período, Projeto Base e status (por padrão, só demandas `Concluída`).
  - Exportação em PDF (ex.: `barryvdh/laravel-dompdf`).
  - Acesso: o admin vê todos; o coordenador vê os estudantes dos seus projetos; o estudante vê só o próprio relatório.

**Critério de conclusão:** o ciclo completo funciona de ponta a ponta: demanda → sub-projeto → entrega → aprovação técnica → homologação → concluída; o relatório do estudante mostra esse sub-projeto com datas e carga horária.

---

## Fase 7 — Complementos ⬜

Itens do TCC que não estão no documento de RF, mas que agregam valor.

- [ ] Dashboard gerencial (demandas por status, categoria, escola e tempo médio de atendimento).
- [ ] Notificações por e-mail (cadastro aprovado, mudança de status, nova mensagem, candidatura aprovada, entrega devolvida).
- [ ] Log de auditoria geral (logins, alterações administrativas).
- [ ] Parâmetros gerais configuráveis pelo admin.

**Critério de conclusão:** os itens escolhidos estão funcionando; os demais ficam registrados como trabalhos futuros.

---

## Fase 8 — Testes, validação com usuários e implantação ⬜

- [ ] Testes automatizados (Feature tests do Laravel) para os fluxos principais e as permissões.
- [ ] Revisão de segurança: CSRF, hash de senhas, validação de uploads, autorização em todas as rotas, LGPD (dados mínimos necessários).
- [ ] Testes de responsividade (celular, tablet, desktop) — RNF02.
- [ ] Dados de demonstração (seeders) para a apresentação.
- [ ] Testes com usuários reais (coordenador, demandante de escola ou instituição, estudantes) e registro dos resultados para o TCC2.
- [ ] Correções a partir do feedback.
- [ ] Definir o ambiente de produção e fazer o deploy.

**Critério de conclusão:** sistema implantado e validado com usuários; resultados documentados.

---

## Modelo de dados por fase

Detalhes de cada tabela, colunas e regras: [`docs/DER.md`](docs/DER.md).

| Fase | Tabelas |
|---|---|
| 1 | usuarios, estudantes, demandantes, coordenadores, escolas_instituicoes, cursos, convites, password_reset_tokens |
| 2 | tipologias, categorias_demanda, prioridades, projetos_base |
| 3 | solicitacoes, anexos |
| 4 | historicos, mensagens |
| 5 | subprojetos, candidaturas, papeis_equipe, membros_equipe |
| 6 | entregas, entrega_arquivos, validacoes |
| 7 | logs_auditoria, parametros |

---

## Registro de decisões

| Data | Decisão | Motivo |
|---|---|---|
| 2026-10-18 | O texto do TCC não será alterado por enquanto; o foco é o plano de construção do sistema. | Pedido do autor. |
| 2026-10-18 | O perfil "Gestor" passa a se chamar "Demandante". | Pedido do autor. |
| 2026-10-18 | Cadastros separados: formulário próprio para Estudante e para Demandante, a partir de uma tela de escolha; Professor Orientador só por convite do admin ou promoção; login único. | Reduzir confusão e erro na escolha do perfil e impedir que alguém se dê permissões de professor. |
| 2026-10-18 | RF04 reescrito: o admin aprova os usuários, define o status (`Ativo`, `Inativo`, `Pendente`, `Rejeitado`) e troca o perfil. | Pedido do autor. |
| 2026-10-18 | RF05: quem cadastra Projetos Base é o Coordenador do Projeto de Extensão (e o Admin). | Pedido do autor. |
| 2026-10-18 | Novo RF14: consulta pública dos projetos de extensão sem login; participar/inscrever-se exige login. Entra na Fase 5. | Pedido do autor. |
| 2026-10-18 | Novo RF15: relatório de demandas executadas por extensionista, com data de início/fim e carga horária estimada. Entra na Fase 6 e substitui o "comprovante de participação" opcional da Fase 7. | Pedido do autor. |
| 2026-10-18 | O perfil "Professor Orientador" passa a se chamar "Coordenador projeto de extensão" e concentra RF05, RF07, RF09, RF11 e RF13. | Pedido do autor: é o mesmo perfil. |
| 2026-10-18 | O estudante também fica `Pendente` até o admin aprovar. | Pedido do autor. |
| 2026-10-18 | A demanda vinculada a um projeto de extensão vira um **sub-projeto** (tabela `subprojetos`). A carga horária estimada é uma por sub-projeto, preenchida pelo Admin ou pelo coordenador e editável em qualquer status, com registro no histórico. | Pedido do autor; o registro no histórico garante rastreabilidade das correções. |
| 2026-10-18 | Ciclo de status da demanda sem mudanças automáticas: todos os status são alterados manualmente (coordenador/admin; `Cancelada` também pelo demandante antes de `Em Desenvolvimento`). A tela só sugere o próximo status. | Pedido do autor. |
| 2026-10-18 | Campos específicos de cada perfil ficam em tabelas 1:1 (`estudantes`, `demandantes`, `coordenadores`), e não em `usuarios`. | O plano tinha duas versões; tabelas por perfil evitam colunas vazias e separam as regras de cada cadastro. |
| 2026-10-18 | DER aprovado: o demandante escolhe a escola da lista ou digita uma nova (conferida pelo admin na aprovação); membro removido não aparece no RF15; papéis iniciais Membro Desenvolvedor, Responsável Técnico e Apoio de Campo; todo Projeto Base ativo é público por padrão, e o coordenador pode ocultar. | Pedido do autor. |
| 2026-10-18 | Decisões de implementação da Fase 1: (a) a confirmação de e-mail vale só para o estudante, como decidido na Fase 0; (b) senha com no mínimo 8 caracteres, letras e números; (c) escola digitada pelo demandante nasce inativa e é ativada na aprovação; (d) o admin não altera o próprio status ou perfil; (e) na troca de perfil, os dados do perfil anterior ficam guardados; (f) estudante sem e-mail confirmado não pode ser aprovado. | Segurança e coerência com as decisões da Fase 0; aprovadas pelo autor junto com a Fase 1. |
| 2026-10-18 | Laravel Boost instalado como dependência de desenvolvimento. | Pedido pelo `CLAUDE.md` do projeto Laravel. |
| 2026-10-21 | Decisões de implementação da Fase 2 (aprovadas pelo autor junto com a fase): (a) o coordenador edita só os projetos que coordena; (b) só o admin troca o coordenador responsável; (c) Projeto Base e itens de domínio não são apagados, só inativados; (d) `projetos_base.coordenador_id` aceita nulo só para o "Demandas Gerais" até o admin definir o coordenador; (e) demandante e estudante consultam a lista de projetos ativos. | Segurança, rastreabilidade e a decisão da Fase 0 sobre "Demandas Gerais". |
| 2026-10-21 | Decisões de implementação da Fase 3 (aprovadas pelo autor junto com a fase): (a) o coordenador vê e classifica só as demandas dos projetos que coordena; as do "Demandas Gerais" sem coordenador ficam com o admin; (b) até 5 anexos por demanda, opcionais; (c) o demandante não edita a demanda depois de enviada (ajustes vão pelo chat da Fase 4); (d) "Priorizada" só depois de categoria, prioridade e complexidade definidas; (e) a triagem pode ser revista até "Priorizada"; (f) uma demanda recusada pode ser reaberta para "Em Análise"; (g) o histórico das mudanças de status (tabela `historicos`) entra na Fase 4; até lá fica gravado só o status atual, o motivo e quem fez a triagem. | Segurança e coerência com as decisões da Fase 0. |
| 2026-10-21 | Decisões de implementação da Fase 4 (aprovadas pelo autor junto com a fase): (a) mensagens do chat não são editadas nem apagadas; (b) o chat fica só para leitura em "Concluída" e "Cancelada" e continua aberto em "Recusada"; (c) todos os envolvidos veem na linha do tempo quem fez cada ação; (d) `mensagens.perfil` guarda o perfil do autor no envio e `historicos.usuario_id` aceita nulo para mudanças fora de uma sessão de usuário; (e) sem aviso de nova mensagem nesta fase (fica para a Fase 7). | Rastreabilidade e coerência com as decisões da Fase 0. |
| 2026-10-21 | Decisões de implementação da Fase 5 (aprovadas pelo autor junto com a fase): (a) o sub-projeto só é criado com a demanda "Priorizada", uma vez por demanda, e "Em Desenvolvimento" só aparece depois dele; (b) o responsável é o coordenador que cria (se for o admin, o coordenador do Projeto Base); (c) com a demanda concluída, cancelada ou recusada, só a carga horária pode ser corrigida; (d) a equipe completa tira o sub-projeto da vitrine; (e) quem cancela a candidatura pode se candidatar de novo, quem foi recusado ou removido não; (f) mudanças na equipe vão para a linha do tempo, candidaturas não; (g) o estudante da equipe não vê os contatos do demandante; (h) a página pública não mostra a carga horária e omite projetos ocultos; (i) o formulário avisa que o escopo é público. | Segurança, LGPD e coerência com as decisões da Fase 0. |

## Registro de progresso

| Data | Fase | O que foi feito |
|---|---|---|
| 2026-10-18 | — | Pasta `PGDT/` e este plano criados. |
| 2026-10-18 | 0 | Fase 0 iniciada. Ambiente verificado: PHP 8.3, Node 24 e Git ok; faltam Composer, MySQL/XAMPP e extensões do PHP. Propostas registradas para as 10 decisões pendentes. |
| 2026-10-18 | 0 | Ambiente pronto: extensões do PHP 8.3 ativadas, Composer 2.10.3 e XAMPP 8.2.12 (MariaDB 10.4.32) instalados, banco `pgdt` criado e conexão testada. |
| 2026-10-18 | 0 | Ciclo de status ajustado: todas as mudanças passam a ser manuais (RF09 e RF13 atualizados). |
| 2026-10-18 | 0 | As 9 propostas restantes foram aprovadas. Projeto Laravel 13 criado em `sistema/` com MySQL, pt_BR e `laravel/ui` + Bootstrap; página inicial funcionando em `php artisan serve`; repositório Git local iniciado com o primeiro commit. |
| 2026-10-18 | 0 | Repositório no GitHub configurado (`origin` = github.com/ThiAmaral/PGDT) e branch `main` enviada; fluxo `main` + `feature/*` definido. |
| 2026-10-18 | 0 | DER completo revisado em `docs/DER.md` (23 tabelas no diagrama + `parametros`); plano ajustado: tabelas por perfil, `papeis_equipe`, `entrega_arquivos`. Diagrama Mermaid validado. Fase 0 aguarda a confirmação de 4 pontos do DER. |
| 2026-10-18 | 0 | Autor aprovou os 4 pontos do DER. **Fase 0 concluída**: decisões registradas, ambiente pronto, `php artisan serve` conectado ao MySQL e repositório no GitHub atualizado. |
| 2026-10-18 | 1 | Fase 1 iniciada na branch `feature/fase1-usuarios`. Traduções pt-BR do Laravel instaladas (`lucascudo/laravel-pt-br-localization`). |
| 2026-10-18 | 1 | Entregáveis da Fase 1 implementados: tabelas por perfil, cadastros separados, convite de coordenador, login com bloqueio por status, recuperação de senha, painel do admin (filtros, aprovação, status, troca de perfil) e menu por perfil. 34 testes passando e fluxo conferido no navegador. Aguarda validação do autor para juntar na `main`. |
| 2026-10-18 | 1 | Autor aprovou a Fase 1. **Fase 1 concluída**: branch `feature/fase1-usuarios` enviada ao GitHub e juntada na `main`. |
| 2026-10-18 | — | Passo a passo de instalação em máquina nova salvo em `docs/INSTALACAO.md`. |
| 2026-10-21 | 2 | Fase 2 iniciada na branch `feature/fase2-projetos-base`. Entregáveis implementados: tabelas de domínio com seeders, cadastros de apoio do admin, CRUD e listagem de Projetos Base com permissões por perfil. 53 testes passando; migrations e seeders rodados no MySQL local. Falta a conferência no navegador com login e a validação do autor. |
| 2026-10-21 | 2 | Telas conferidas no navegador como admin; duas correções de formulário feitas. Aguarda validação do autor (incluindo o fluxo do coordenador, que precisa de um coordenador convidado) para juntar na `main`. |
| 2026-10-21 | 2 | Autor cadastrou um coordenador pelo convite (link lido no `laravel.log`, pois o ambiente local usa `MAIL_MAILER=log`) e aprovou a Fase 2. **Fase 2 concluída**: branch `feature/fase2-projetos-base` juntada na `main`. |
| 2026-10-21 | 3 | Fase 3 iniciada na branch `feature/fase3-demandas`. |
| 2026-10-21 | 3 | Entregáveis da Fase 3 implementados: demandas com anexos (upload arrasta-e-solta, arquivos fora da pasta pública), "Minhas demandas", fila de triagem, classificação e status iniciais. 77 testes passando; migration rodada no MySQL local; fluxo conferido no navegador (computador e celular). Aguarda validação do autor para juntar na `main`. |
| 2026-10-21 | 3 | Autor aprovou a Fase 3 e as decisões de implementação (a)–(g). **Fase 3 concluída**: branch `feature/fase3-demandas` juntada na `main` e enviada ao GitHub. |
| 2026-10-21 | 4 | Fase 4 iniciada na branch `feature/fase4-rastreabilidade`. Entregáveis implementados: tabelas `historicos` e `mensagens`, registro automático pelo Observer, barra de progresso, linha do tempo e chat com acesso restrito. 93 testes passando; migration rodada no MySQL local; fluxo conferido no navegador (computador e celular). Aguarda validação do autor e das decisões (a)–(e) para juntar na `main`. |
| 2026-10-21 | 4 | Autor aprovou a Fase 4 e as decisões de implementação (a)–(e). **Fase 4 concluída**: branch `feature/fase4-rastreabilidade` juntada na `main` e enviada ao GitHub. |
| 2026-10-21 | 5 | Fase 5 iniciada na branch `feature/fase5-subprojetos`. Entregáveis implementados: sub-projetos com carga horária no histórico, vitrine de oportunidades, candidaturas, equipe com papéis e página pública `/projetos`. 127 testes passando; migration rodada no MySQL local; critério de conclusão conferido no navegador (computador e celular). Aguarda validação do autor e das decisões (a)–(i) para juntar na `main`. |
| 2026-10-21 | 5 | Autor aprovou a Fase 5 e as decisões de implementação (a)–(i). **Fase 5 concluída**: branch `feature/fase5-subprojetos` juntada na `main` e enviada ao GitHub. |
