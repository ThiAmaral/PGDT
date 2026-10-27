@extends('layouts.app')

@section('titulo', 'Aviso de privacidade')

@php
    $rede = \App\Models\Parametro::valor('nome_rede');
    $contato = \App\Models\Parametro::valor('email_contato');
@endphp

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            {{-- Fase 8: rascunho em revisão pelo autor. Os trechos marcados dependem da confirmação da UFES. --}}
            <div class="alert alert-warning small">
                <strong>Rascunho em revisão.</strong> Os trechos <mark>destacados</mark> ainda precisam ser confirmados com a UFES.
            </div>

            <h1 class="h3">Aviso de privacidade</h1>
            <p class="text-muted">Última atualização: 27/10/2026</p>

            <p>
                Este aviso explica quais dados pessoais a Plataforma de Gestão de Demandas Tecnológicas coleta, para que eles são usados,
                quem tem acesso a eles e como você pode exercer os seus direitos, conforme a Lei Geral de Proteção de Dados Pessoais
                (LGPD, Lei nº 13.709/2018).
            </p>

            <h2 class="h5 mt-4">1. Quem é o responsável pelos dados</h2>
            <p>
                A plataforma é mantida pelo projeto de extensão {{ $rede }}. A Universidade Federal do Espírito Santo (UFES)
                é a controladora dos dados.
            </p>
            <p>
                Contato do projeto: @if ($contato)<a href="mailto:{{ $contato }}">{{ $contato }}</a>@else<mark>e-mail de contato a definir nos parâmetros gerais</mark>@endif.<br>
                Encarregado de dados (DPO) da UFES: <mark>nome e e-mail do encarregado da UFES</mark>.
            </p>

            <h2 class="h5 mt-4">2. Quais dados coletamos</h2>
            <div class="table-responsive">
                <table class="table table-sm align-top">
                    <thead>
                        <tr><th scope="col">Quem</th><th scope="col">Dados</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Todos os usuários</td><td>Nome, e-mail, telefone (obrigatório só para o demandante) e senha. A senha é guardada de forma embaralhada (hash) e ninguém, nem o administrador, consegue lê-la.</td></tr>
                        <tr><td>Estudante</td><td>Matrícula e curso na UFES.</td></tr>
                        <tr><td>Demandante</td><td>Escola ou instituição e cargo.</td></tr>
                        <tr><td>Coordenador</td><td>SIAPE e departamento (opcionais).</td></tr>
                        <tr><td>Uso da plataforma</td><td>Demandas e anexos, mensagens do chat, candidaturas, participação nas equipes, entregas e arquivos, decisões de validação e a linha do tempo de cada demanda (quem fez cada ação e quando).</td></tr>
                        <tr><td>Dados técnicos</td><td>Endereço IP e navegador usados na sessão e registros de erro do sistema, para segurança e correção de problemas.</td></tr>
                    </tbody>
                </table>
            </div>

            <h2 class="h5 mt-4">3. Para que usamos os dados</h2>
            <ul>
                <li>Criar e aprovar a sua conta e confirmar que você é estudante, servidor ou representante de uma escola ou instituição.</li>
                <li>Receber, analisar e acompanhar as demandas das escolas e instituições.</li>
                <li>Formar as equipes de estudantes, acompanhar as entregas e registrar a validação das soluções.</li>
                <li>Enviar avisos por e-mail sobre as demandas em que você participa.</li>
                <li>Gerar o relatório de participação dos extensionistas, com a carga horária, para os registros do projeto de extensão.</li>
                <li>Manter a segurança da plataforma.</li>
            </ul>
            <p>
                O tratamento dos dados tem como base a execução das atividades de extensão da UFES, uma universidade pública
                (<mark>art. 7º, inciso III, e art. 23 da LGPD — a confirmar com a UFES</mark>). Os dados não são usados para publicidade
                nem vendidos a ninguém.
            </p>

            <h2 class="h5 mt-4">4. Quem vê os seus dados</h2>
            <ul>
                <li><strong>Qualquer pessoa, sem login:</strong> os projetos de extensão, o nome e o departamento do coordenador e o escopo dos sub-projetos com vagas abertas. E-mail, telefone e matrícula nunca aparecem na página pública.</li>
                <li><strong>Administrador da plataforma:</strong> todos os dados, para aprovar cadastros e dar suporte.</li>
                <li><strong>Coordenação do projeto:</strong> as demandas dos projetos que coordena, com os contatos do demandante, e o nome, o curso e a matrícula dos estudantes das equipes e dos candidatos.</li>
                <li><strong>Estudantes da equipe:</strong> a demanda em que trabalham, o chat e as entregas. Eles não veem o e-mail nem o telefone do demandante.</li>
                <li><strong>Demandante:</strong> as próprias demandas, o nome dos estudantes da equipe e as entregas já aprovadas pela coordenação.</li>
            </ul>
            <p>
                Os dados ficam guardados em <mark>servidor de hospedagem a definir</mark> e os e-mails são enviados por
                <mark>serviço de e-mail a definir</mark>. Fora isso, os dados só são compartilhados quando a lei exigir.
            </p>

            <h2 class="h5 mt-4">5. Cuidado com os anexos</h2>
            <p>
                Envie nos anexos, no chat e nas entregas só o necessário para resolver a demanda. Evite fotos de alunos e documentos com
                dados pessoais de terceiros, principalmente de crianças e adolescentes. Se uma foto for necessária, prefira imagens em que
                as pessoas não possam ser identificadas.
            </p>

            <h2 class="h5 mt-4">6. Cookies</h2>
            <p>
                A plataforma usa só os cookies necessários para funcionar: o que mantém você conectado e o que protege os formulários
                contra envios falsos. Não usamos cookies de propaganda nem de rastreamento.
            </p>

            <h2 class="h5 mt-4">7. Segurança</h2>
            <p>
                O acesso é feito com login e senha, e cada perfil vê só o que precisa. Os arquivos enviados ficam fora da área pública
                do servidor e só podem ser baixados por quem tem permissão. A conexão é protegida por HTTPS.
            </p>

            <h2 class="h5 mt-4">8. Por quanto tempo guardamos os dados</h2>
            <p>
                Os dados da conta ficam guardados enquanto ela existir. As demandas, a linha do tempo e a participação nas equipes são
                mantidas como registro das atividades de extensão e para a emissão dos relatórios de carga horária, por
                <mark>prazo a definir com a UFES</mark>. Contas inativadas deixam de acessar a plataforma, mas os registros das
                atividades de que participaram continuam guardados.
            </p>

            <h2 class="h5 mt-4">9. Os seus direitos</h2>
            <p>Pela LGPD, você pode pedir:</p>
            <ul>
                <li>a confirmação de que tratamos os seus dados e uma cópia deles;</li>
                <li>a correção de dados incompletos ou errados;</li>
                <li>a exclusão ou a anonimização dos dados desnecessários;</li>
                <li>informações sobre com quem os dados foram compartilhados.</li>
            </ul>
            <p>
                Para fazer um pedido, escreva para
                @if ($contato)<a href="mailto:{{ $contato }}">{{ $contato }}</a>.@else{{ 'o contato do projeto.' }}@endif
                Alguns dados podem precisar ser mantidos mesmo depois do pedido, como os registros das atividades de extensão já realizadas.
            </p>

            <h2 class="h5 mt-4">10. Mudanças neste aviso</h2>
            <p>
                Este aviso pode ser atualizado. A data da última atualização fica no topo da página.
            </p>
        </div>
    </div>
</div>
@endsection
