<?php
// ---------------------------------------------
// 1. CONFIGURAÇÕES E CONEXÃO
// ---------------------------------------------
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$mensagem = "";
$cadastro_sucesso = false;

// ---------------------------------------------
// 2. PROCESSAMENTO DO ID
// ---------------------------------------------
$id_cliente = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_cliente > 0) {
    // ---------------------------------------------
    // 3. LÓGICA DE INATIVAÇÃO (SOFT DELETE)
    // ---------------------------------------------
    
    // Alterado de status_empresa para status_cliente conforme o novo padrão
    $sql = "UPDATE clientes SET status_cliente = 'Inativo' WHERE id_cliente = ?";
    
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
    
    if ($stmt->execute()) {
        // Se o número de linhas afetadas for maior que 0, deu certo
        if ($stmt->affected_rows > 0) {
            echo "SUCESSO";
        } else {
            // Se cair aqui, o ID existe mas o status já era 'Inativo' ou a query falhou silenciosamente
            echo "SEM_ALTERACAO"; 
        }
    } else {
        echo "ERRO_SQL: " . $conexao->error;
    }
    
    $stmt->close();
} else {
    echo "ID_INVALIDO";
}

$conexao->close();
?>