# Instalação em uma máquina nova (Windows)

Passo a passo para clonar o projeto do zero, instalar as dependências e subir o servidor para testes manuais. O sistema Laravel fica na pasta `sistema/` do repositório.

## 1. Instalar as ferramentas (uma vez por máquina)

Pelo PowerShell, com o `winget`:

```powershell
winget install Git.Git
winget install PHP.PHP.8.3
winget install OpenJS.NodeJS.LTS
winget install ApacheFriends.Xampp.8.2
```

- **Composer:** baixar em https://getcomposer.org/download/. Se o `Composer-Setup.exe` falhar no modo silencioso, usar o instalador normal (com janela).
- **Extensões do PHP:** abrir o `php.ini` (o caminho aparece em `php --ini`) e tirar o `;` do começo destas linhas: `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `mysqli`, `openssl`, `pdo_mysql`, `pdo_sqlite`, `sodium`, `sqlite3`, `zip`. O `pdo_sqlite` e o `sqlite3` são usados pelos testes automatizados.
- Fechar e abrir o terminal de novo e conferir as versões (o PHP precisa ser 8.3 ou mais novo):

```powershell
php -v; composer -V; node -v; git --version
```

## 2. Ligar o banco e criar a base

1. Abrir o `C:\xampp\xampp-control.exe` e clicar em **Start** no **MySQL**. O Apache do XAMPP não é necessário.
2. Criar o banco pelo phpMyAdmin (http://localhost/phpmyadmin, que precisa do Apache ligado) ou pelo terminal:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE pgdt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 3. Clonar e instalar o projeto

```powershell
git clone https://github.com/ThiAmaral/PGDT.git
cd PGDT\sistema
composer install
npm install
npm run build
copy .env.example .env
php artisan key:generate
```

O `.env.example` já vem configurado para o projeto (MySQL `pgdt` com `root` sem senha, pt-BR e e-mails gravados no log).

No `.env`, definir a senha do administrador inicial antes de rodar o seeder (com aspas se tiver caracteres especiais):

```
ADMIN_PASSWORD="SuaSenhaDeTeste123"
```

Sem essa linha, o seeder gera uma senha aleatória e mostra no terminal uma única vez.

Criar as tabelas e os dados iniciais (16 cursos e o administrador):

```powershell
php artisan migrate --seed
```

## 4. Subir o servidor

```powershell
php artisan serve
```

Abrir http://localhost:8000 e entrar com `admin@pgdt.local` e a senha definida no `.env`.

Os avisos por e-mail (Fase 7) vão para a fila do banco. Para eles saírem, deixar rodando em outro terminal:

```powershell
php artisan queue:work
```

Sem esse terminal, os avisos ficam guardados na tabela `jobs` e saem quando ele for ligado.

Para mexer nas telas, deixar também `npm run dev` rodando em outro terminal: as mudanças de CSS e JS aparecem sem rodar o `npm run build` de novo.

## 5. Testes manuais

- **E-mails:** não são enviados de verdade. Os e-mails de confirmação, aprovação, convite, recuperação de senha e os avisos da Fase 7 (com o `queue:work` ligado) ficam no fim de `sistema\storage\logs\laravel.log`; os links são copiados de lá. Se o link vier com `&amp;`, trocar por `&` antes de colar no navegador.
- **Roteiro sugerido:**
  1. Cadastrar um estudante com e-mail `@edu.ufes.br` e confirmar o e-mail pelo link do log.
  2. Cadastrar um demandante.
  3. Entrar como admin e aprovar os dois.
  4. Em **Convites**, convidar um e-mail e aceitar o convite pelo link do log.
- **Testes automatizados:** `php artisan test`. Usam SQLite em memória e não mexem no banco `pgdt`.
- **Zerar o banco e recomeçar:** `php artisan migrate:fresh --seed`. Apaga todos os dados.

## Problemas comuns

| Sintoma | Solução |
|---|---|
| "directory must be present and writable" | Se o projeto estiver numa pasta do OneDrive, tirar o "somente leitura": `attrib -R /S /D storage` e `attrib -R /S /D bootstrap\cache` |
| "Unable to locate file in Vite manifest" | Rodar `npm run build` |
| "could not find driver" | A extensão `pdo_mysql` não foi ativada no `php.ini` |
| "Connection refused" no banco | O MySQL não foi ligado no XAMPP Control Panel |
| `php artisan db:show` dá erro | Normal no MariaDB 10.4 do XAMPP; o sistema funciona |
