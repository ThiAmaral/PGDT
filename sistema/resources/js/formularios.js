/**
 * Comportamentos dos formulários de cadastro e do painel do admin.
 */

function mascararTelefone(valor) {
    const digitos = valor.replace(/\D/g, '').slice(0, 11);

    if (digitos.length <= 2) {
        return digitos.length ? `(${digitos}` : '';
    }

    const ddd = digitos.slice(0, 2);
    const numero = digitos.slice(2);
    const corte = numero.length > 8 ? 5 : 4;

    return numero.length > corte
        ? `(${ddd}) ${numero.slice(0, corte)}-${numero.slice(corte)}`
        : `(${ddd}) ${numero}`;
}

function iniciarMascaras() {
    document.querySelectorAll('[data-mascara="telefone"]').forEach((campo) => {
        campo.addEventListener('input', () => {
            campo.value = mascararTelefone(campo.value);
        });
    });
}

/** Aviso no cadastro de demandante quando o e-mail é da UFES. */
function iniciarAvisoUfes() {
    document.querySelectorAll('[data-aviso-ufes]').forEach((campo) => {
        const aviso = document.querySelector(campo.dataset.avisoUfes);
        const verificar = () => {
            const ehUfes = /@([a-z0-9-]+\.)*ufes\.br$/i.test(campo.value.trim());
            aviso.classList.toggle('d-none', !ehUfes);
        };

        campo.addEventListener('blur', verificar);
        campo.addEventListener('input', () => {
            if (!aviso.classList.contains('d-none')) {
                verificar();
            }
        });
        verificar();
    });
}

/** Alterna entre escolher a escola na lista e digitar uma nova. */
function iniciarEscolhaEscola() {
    const modo = document.getElementById('escola_modo');

    if (!modo) {
        return;
    }

    document.querySelectorAll('[data-escola-modo]').forEach((botao) => {
        botao.addEventListener('click', () => {
            modo.value = botao.dataset.escolaModo;
            document.getElementById('escola-lista').classList.toggle('d-none', modo.value !== 'lista');
            document.getElementById('escola-nova').classList.toggle('d-none', modo.value !== 'nova');
        });
    });
}

function iniciarConfirmacoes() {
    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });
}

/** Alterar status: mostra o campo de motivo quando exigido e confirma a mudança. */
function iniciarAlteracaoStatus() {
    document.querySelectorAll('form[data-confirmar-status]').forEach((formulario) => {
        const select = formulario.querySelector('select[name="status"]');
        const blocoMotivo = formulario.querySelector(select.dataset.campoMotivo);
        const motivo = blocoMotivo.querySelector('textarea');
        const opcaoAtual = () => select.options[select.selectedIndex];

        const atualizar = () => {
            const exige = opcaoAtual().dataset.exigeMotivo === '1';
            blocoMotivo.classList.toggle('d-none', !exige);
            motivo.required = exige;
        };

        select.addEventListener('change', atualizar);
        atualizar();

        formulario.addEventListener('submit', (evento) => {
            const novo = opcaoAtual().text;
            const atual = formulario.dataset.statusAtual;

            if (novo === atual) {
                evento.preventDefault();
                window.alert('Escolha um status diferente do atual.');
                return;
            }

            if (motivo.required && motivo.value.trim() === '') {
                evento.preventDefault();
                motivo.classList.add('is-invalid');
                motivo.focus();
                return;
            }

            if (!window.confirm(`Mudar o status de "${atual}" para "${novo}"?`)) {
                evento.preventDefault();
            }
        });
    });
}

/** Trocar perfil: mostra os campos do perfil novo e confirma mostrando o atual e o novo. */
function iniciarTrocaPerfil() {
    document.querySelectorAll('form[data-confirmar-perfil]').forEach((formulario) => {
        const select = formulario.querySelector('[data-campos-perfil]');

        const atualizar = () => {
            formulario.querySelectorAll('[data-perfil-campos]').forEach((bloco) => {
                bloco.classList.toggle('d-none', bloco.dataset.perfilCampos !== select.value);
            });
        };

        select.addEventListener('change', atualizar);
        atualizar();

        formulario.addEventListener('submit', (evento) => {
            const novo = select.options[select.selectedIndex].text;
            const atual = formulario.dataset.perfilAtual;

            if (novo === atual) {
                evento.preventDefault();
                window.alert('Escolha um perfil diferente do atual.');
                return;
            }

            if (!window.confirm(`Trocar o perfil de "${atual}" para "${novo}"?\n\nO usuário passa a ver só o menu do perfil novo.`)) {
                evento.preventDefault();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarMascaras();
    iniciarAvisoUfes();
    iniciarEscolhaEscola();
    iniciarConfirmacoes();
    iniciarAlteracaoStatus();
    iniciarTrocaPerfil();
});
