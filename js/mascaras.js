// mascaras.js - CONTEÚDO FINAL COMPLETO E CORRIGIDO (SUBSTITUA TUDO!)

// === LÓGICA DA MÁSCARA ===
const inputCelular = document.getElementById('num_celular');

function mascaraCelular(valor) {
    valor = valor.replace(/\D/g, "");
    valor = valor.replace(/^(\d{2})(\d)/g, "($1) $2");
    valor = valor.replace(/(\d)(\d{4})$/, "$1-$2");
    return valor;
}

if (inputCelular) {
    inputCelular.value = mascaraCelular(inputCelular.value);

    inputCelular.addEventListener('input', function(e) {
        e.target.value = mascaraCelular(e.target.value);
    });
}
// ==========================

// === FUNÇÃO DE FEEDBACK E REDIRECIONAMENTO ===
function mostrarSucessoERedirecionar(mensagem, destino) {
    alert(mensagem);
    window.location.href = destino;
}
// ==========================

// === FUNÇÃO DE EXCLUSÃO AJAX (excluirTecnico) ===
function excluirTecnico(id, botao) {
    if (confirm('Tem certeza que deseja excluir este técnico?')) {
        
        const xhr = new XMLHttpRequest();
        // Caminho ajustado para AJAX (assumindo lista_tecnicos.php na raiz)
        xhr.open('GET', '../telas/tecnicos/excluir_tecnico.php?id=' + id, true); 
        
        xhr.onload = function () {
            if (xhr.status === 200) {
                const response = xhr.responseText.trim(); 
                
                if (response === 'SUCESSO') {
                    const linha = botao.closest('tr');
                    linha.remove();
                    alert('Técnico excluído com sucesso!');
                    
                } else if (response.includes('FK_ERROR')) {
                    alert('❌ Erro: Não é possível excluir este técnico. Ele possui chamados no sistema.');
                } else {
                    alert('❌ Erro desconhecido na exclusão. Tente novamente.');
                }
            } else {
                alert('Erro de conexão com o servidor: ' + xhr.status);
            }
        };
        
        xhr.send();
    }
}
// ==========================