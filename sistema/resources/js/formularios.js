/**
 * Comportamentos dos formulários de cadastro, do painel do admin e das demandas.
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

/** Contador de caracteres: `data-contador="#id-do-contador"` no campo com maxlength. */
function iniciarContadores() {
    document.querySelectorAll('[data-contador]').forEach((campo) => {
        const contador = document.querySelector(campo.dataset.contador);
        const atualizar = () => {
            contador.textContent = `${campo.value.length}/${campo.maxLength}`;
        };

        campo.addEventListener('input', atualizar);
        atualizar();
    });
}

/** Nova demanda: mostra a descrição do projeto de extensão escolhido. */
function iniciarDescricaoProjeto() {
    document.querySelectorAll('[data-descricoes-projeto]').forEach((select) => {
        const destino = document.querySelector(select.dataset.descricoesProjeto);
        const atualizar = () => {
            destino.textContent = select.options[select.selectedIndex]?.dataset.descricao ?? '';
        };

        select.addEventListener('change', atualizar);
        atualizar();
    });
}

/** Evita o envio duplo de formulários com upload, que podem demorar. */
function iniciarEnvioUnico() {
    document.querySelectorAll('form[data-enviando]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (evento.defaultPrevented) {
                return;
            }

            formulario.querySelectorAll('button[type="submit"]').forEach((botao) => {
                botao.disabled = true;
                botao.textContent = formulario.dataset.enviando;
            });
        });
    });
}

function formatarTamanho(bytes) {
    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1).replace('.0', '').replace('.', ',')} MB`;
}

/**
 * Upload com arrastar e soltar (RF06). Os arquivos ficam no próprio <input type="file">,
 * então o formulário é enviado normalmente. Confere tipo, tamanho e quantidade antes do envio;
 * o servidor confere de novo.
 */
function iniciarUploads() {
    document.querySelectorAll('[data-upload]').forEach((zona) => {
        const input = zona.querySelector('input[type="file"]');
        const lista = zona.parentElement.querySelector('[data-upload-lista]');
        const erro = zona.parentElement.querySelector('[data-upload-erro]');
        const extensoes = zona.dataset.extensoes.split(',');
        const tamanhoMaximo = Number(zona.dataset.tamanhoMaximo);
        const quantidadeMaxima = Number(zona.dataset.quantidadeMaxima);
        let arquivos = [];

        const sincronizar = () => {
            const transferencia = new DataTransfer();
            arquivos.forEach((arquivo) => transferencia.items.add(arquivo));
            input.files = transferencia.files;

            lista.replaceChildren(...arquivos.map((arquivo, indice) => {
                const item = document.createElement('li');
                item.className = 'list-group-item d-flex justify-content-between align-items-center gap-2';

                const nome = document.createElement('span');
                nome.className = 'text-break small';
                nome.textContent = `${arquivo.name} (${formatarTamanho(arquivo.size)})`;

                const remover = document.createElement('button');
                remover.type = 'button';
                remover.className = 'btn btn-sm btn-outline-danger';
                remover.textContent = 'Remover';
                remover.setAttribute('aria-label', `Remover ${arquivo.name}`);
                remover.addEventListener('click', () => {
                    arquivos.splice(indice, 1);
                    erro.textContent = '';
                    sincronizar();
                });

                item.append(nome, remover);
                return item;
            }));
        };

        const adicionar = (novos) => {
            const problemas = [];

            Array.from(novos).forEach((arquivo) => {
                const extensao = arquivo.name.split('.').pop().toLowerCase();

                if (!extensoes.includes(extensao)) {
                    problemas.push(`"${arquivo.name}" não é PDF, JPG ou PNG.`);
                } else if (arquivo.size > tamanhoMaximo) {
                    problemas.push(`"${arquivo.name}" tem mais de ${formatarTamanho(tamanhoMaximo)}.`);
                } else if (arquivos.some((existente) => existente.name === arquivo.name && existente.size === arquivo.size)) {
                    problemas.push(`"${arquivo.name}" já está na lista.`);
                } else if (arquivos.length >= quantidadeMaxima) {
                    problemas.push(`"${arquivo.name}" não foi incluído: o limite é de ${quantidadeMaxima} arquivos.`);
                } else {
                    arquivos.push(arquivo);
                }
            });

            erro.textContent = problemas.join(' ');
            sincronizar();
        };

        input.addEventListener('change', () => {
            // Ao escolher pelo botão, o navegador troca a seleção: junta com a lista atual.
            const escolhidos = Array.from(input.files);
            input.value = '';
            adicionar(escolhidos);
        });

        ['dragenter', 'dragover'].forEach((tipo) => zona.addEventListener(tipo, (evento) => {
            evento.preventDefault();
            zona.classList.add('arrastando');
        }));

        ['dragleave', 'drop'].forEach((tipo) => zona.addEventListener(tipo, (evento) => {
            evento.preventDefault();
            zona.classList.remove('arrastando');
        }));

        zona.addEventListener('drop', (evento) => adicionar(evento.dataTransfer.files));
    });
}

document.addEventListener('DOMContentLoaded', () => {
    iniciarMascaras();
    iniciarAvisoUfes();
    iniciarEscolhaEscola();
    iniciarConfirmacoes();
    iniciarAlteracaoStatus();
    iniciarTrocaPerfil();
    iniciarContadores();
    iniciarDescricaoProjeto();
    iniciarEnvioUnico();
    iniciarUploads();
});
