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
| 0 | Decisões e preparação do ambiente | — | 🟨 |
| 1 | Fundação, autenticação e usuários | RF01, RF02, RF03, RF04 | ⬜ |
| 2 | Cadastros de apoio e Projetos Base | RF05 | ⬜ |
| 3 | Demandas: abertura e triagem | RF06, RF07 | ⬜ |
| 4 | Rastreabilidade e comunicação | RF08 | ⬜ |
| 5 | Sub-projetos, vitrine pública e equipe | RF09, RF10, RF11, RF14 | ⬜ |
| 6 | Entregas, validação e relatório | RF12, RF13, RF15 | ⬜ |
| 7 | Complementos (dashboard, notificações, auditoria) | antigos RF04/RF22/RF23 do TCC | ⬜ |
| 8 | Testes, validação com usuários e implantação | RNF01–RNF05 | ⬜ |

Cada fase termina com algo **utilizável e demonstrável**. Uma fase só começa depois que o critério de conclusão da anterior for atingido.

---

## Fase 0 — Decisões e preparação do ambiente 🟨

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
- [x] Revisar o DER completo com as entidades que faltam: [`docs/DER.md`](docs/DER.md) (diagrama Mermaid + dicionário de dados). *4 pontos aguardam confirmação do autor (seção 4 do DER).*

**Critério de conclusão:** decisões registradas neste documento; `php artisan serve` abre a página inicial conectada ao MySQL; repositório com o primeiro commit.

---

## Fase 1 — Fundação, autenticação e usuários ⬜

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
- [ ] Layout base responsivo (menu por perfil, tela de login).
- [ ] Tabelas (ver `docs/DER.md`): `usuarios` (perfil, status, telefone), `estudantes` (matrícula, curso), `demandantes` (escola, cargo), `coordenadores`, `escolas_instituicoes`, `cursos`, `convites`.
- [ ] Tela "Criar conta" com a escolha do tipo de cadastro (Estudante / Demandante).
- [ ] RF01: formulário do estudante com validação de domínio; perfil Estudante atribuído automaticamente; status `Pendente`.
- [ ] RF02: formulário do demandante (escola, cargo, telefone com máscara); status `Pendente`.
- [ ] Cadastro do Coordenador projeto de extensão por convite do admin (e-mail com link de uso único e prazo de validade); a conta já nasce `Ativo`.
- [ ] RF03: login, logout, "esqueci minha senha" com token por e-mail e redefinição.
- [ ] RF04: painel do admin com filtros (nome, e-mail, perfil, status). O admin aprova os usuários, altera o status do usuário (`Ativo`, `Inativo`, `Pendente`, `Rejeitado`) e troca o papel/perfil dos usuários.
  - Só usuários `Ativo` conseguem usar o sistema; `Inativo` e `Rejeitado` não fazem login.
  - Rejeitar e inativar pedem confirmação; a troca de perfil mostra o perfil atual e o novo antes de salvar.
- [ ] Middleware/policies de controle de acesso por perfil (RNF04).
- [ ] Seeder com um usuário administrador inicial.

**Critério de conclusão:** os 4 perfis conseguem se cadastrar ou ser criados, fazer login e ver apenas o seu menu; o admin aprova um estudante e um demandante pendentes.

---

## Fase 2 — Cadastros de apoio e Projetos Base ⬜

**RF:** RF05 Cadastro de Projetos Base

### Entregáveis
- [ ] Tabelas de domínio: `tipologias`, `categorias_demanda`, `prioridades` (com seeders).
- [ ] CRUD de Projetos Base (título, descrição, escopo preliminar, datas, tipologia, coordenador responsável).
- [ ] Listagem de Projetos Base (usada pelo Demandante na Fase 3 e pela página pública do RF14).
- [ ] Permissão: só o Coordenador projeto de extensão e o Admin cadastram e editam.

**Critério de conclusão:** um coordenador cadastra um Projeto Base e ele aparece disponível para seleção.

---

## Fase 3 — Demandas: abertura e triagem ⬜

**RFs:** RF06 Abertura de Solicitações · RF07 Triagem e Parametrização

### Entregáveis
- [ ] Tabelas `solicitacoes` e `anexos` (um para muitos).
- [ ] RF06: formulário do Demandante com seleção do Projeto Base, título (máx. 100 caracteres), descrição, justificativa, público afetado e upload drag-and-drop (PDF/JPG/PNG, até 5 MB).
- [ ] Armazenamento seguro dos arquivos (`storage`, fora do diretório público, com download autorizado).
- [ ] "Minhas demandas" para o Demandante.
- [ ] RF07: fila do coordenador (status `Cadastrada`), tela de detalhes, definição de categoria, prioridade e complexidade.
- [ ] Transições de status iniciais: Cadastrada → Em Análise → Priorizada (e Recusada, se decidido na Fase 0).

**Critério de conclusão:** um demandante abre uma demanda com anexo e um coordenador a classifica e prioriza.

---

## Fase 4 — Rastreabilidade e comunicação ⬜

**RF:** RF08

### Entregáveis
- [ ] Tabelas `historicos` e `mensagens`.
- [ ] Registro automático no histórico a cada mudança de status ou ação relevante (via Observer/Event do Laravel).
- [ ] Barra de progresso visual na tela da demanda.
- [ ] Linha do tempo cronológica do histórico.
- [ ] Chat da demanda (autor, perfil, data/hora, texto) com o botão "Enviar Comentário".
- [ ] Acesso restrito aos envolvidos (policy).

**Critério de conclusão:** demandante e coordenador trocam mensagens em uma demanda e veem cada mudança de status registrada.

---

## Fase 5 — Sub-projetos, vitrine pública e equipe ⬜

**RFs:** RF09 Criação de Sub-projetos · RF10 Vitrine de Oportunidades · RF11 Gestão de Lotação · RF14 Consulta Pública de Projetos

### Entregáveis
- [ ] Tabelas `subprojetos` (1:1 com a solicitação; o Projeto Base vem dela; data de início, data de fim e carga horária estimada), `candidaturas`, `papeis_equipe`, `membros_equipe` (com papel e `removido_em`).
- [ ] RF09: converter a demanda em sub-projeto do projeto de extensão (campos pré-preenchidos, escopo obrigatório, data de início e de fim, carga horária estimada, vagas, modalidade); o coordenador logado vira o responsável; ao salvar, a tela sugere mudar a demanda para `Em Desenvolvimento` (mudança manual).
  - A carga horária estimada é preenchida pelo Admin ou pelo Coordenador projeto de extensão e pode ser alterada em qualquer status, inclusive depois de `Concluída`.
  - Cada alteração da carga horária fica registrada no histórico (RF08): valor anterior, valor novo, quem alterou e quando.
- [ ] RF10: vitrine em cards com busca, filtro por modalidade e candidatura com um clique.
- [ ] RF11: tabela de candidaturas pendentes (Aprovar/Recusar), equipe com papéis (Membro Desenvolvedor, Responsável Técnico, funções de campo) e remoção de membros.
- [ ] RF14: página pública (`/projetos`), sem login, com os projetos de extensão cadastrados e os sub-projetos com vagas abertas (busca e filtros iguais aos da vitrine).
  - Mostra só dados públicos: título, descrição, coordenador, modalidade, período e vagas. Contatos do demandante, anexos e chat ficam fora (LGPD).
  - O botão "Quero participar" leva ao login. Depois do login, a pessoa volta para o mesmo projeto e conclui a inscrição com um clique. A tela de login tem o link "Ainda não tem conta? Criar conta".
  - Só o perfil Estudante com status `Ativo` pode se inscrever; para os outros o botão aparece desabilitado com a explicação.

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
