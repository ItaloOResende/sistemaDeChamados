// === LÓGICA DA MÁSCARA DE CELULAR (CORRIGIDA) ===
function mascaraCelular(valor) {
    if (!valor) return "";
    valor = valor.replace(/\D/g, "");
    valor = valor.replace(/^(\d{2})(\d)/g, "($1) $2");
    valor = valor.replace(/(\d)(\d{4})$/, "$1-$2");
    return valor;
}

// Verifica se o elemento existe antes de aplicar a lógica
const inputCelular = document.getElementById('num_celular');
if (inputCelular) {
    // Aplica a máscara ao carregar (se já houver valor)
    inputCelular.value = mascaraCelular(inputCelular.value);
    
    inputCelular.addEventListener('input', function(e) {
        e.target.value = mascaraCelular(e.target.value);
    });
}

// === FUNÇÃO DO CADASTRO (FEEDBACK) ===
function mostrarSucessoERedirecionar(mensagem, destino) {
    alert(mensagem);
    window.location.href = destino;
}

// === FUNÇÃO DE EXCLUSÃO AJAX (Técnicos) ===
function excluirTecnico(id, nome, botao) {
    if (confirm('Tem certeza que deseja excluir ' + nome + '?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'excluir_tecnico.php?id=' + id, true); 
        xhr.onload = function () {
            if (xhr.status === 200) {
                const response = xhr.responseText.trim(); 
                if (response === 'SUCESSO') {
                    alert('✅ Técnico ' + nome + ' excluído com sucesso!');
                    location.reload(); 
                } else {
                    alert('Erro no servidor: ' + response);
                }
            } else {
                alert('Erro de conexão: ' + xhr.status);
            }
        };
        xhr.send();
    }
}

// === FUNÇÃO DE EXCLUSÃO AJAX (Clientes) ===
function excluirCliente(id, nome_empresa, botao) {
    if (confirm('Tem certeza que deseja excluir o cliente ' + nome_empresa + '?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'excluir_cliente.php?id=' + id, true); 
        xhr.onload = function () {
            if (xhr.status === 200) {
                if (xhr.responseText.trim() === 'SUCESSO') {
                    alert('✅ Cliente ' + nome_empresa + ' excluído com sucesso!');
                    location.reload(); 
                }
            }
        };
        xhr.send();
    }
}

// === VALIDAÇÃO DE FECHAMENTO DE CHAMADO ===
document.addEventListener('DOMContentLoaded', function() {
    const campoStatus = document.querySelector('select[name="status"]');
    const campoSolucao = document.querySelector('textarea[name="solucao"]');

    if (campoStatus && campoSolucao) {
        const validarNativo = () => {
            const status = campoStatus.value;
            if (status === 'Concluido') {
                campoSolucao.required = true;
                campoSolucao.placeholder = "Obrigatório para encerrar o chamado...";
            } else {
                campoSolucao.required = false;
                campoSolucao.placeholder = "Descreva aqui o que foi feito...";
            }
        };

        validarNativo();
        campoStatus.addEventListener('change', validarNativo);
    }
});