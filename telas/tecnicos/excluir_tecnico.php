<?php
// Configurações de Conexão
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$mensagem = "";
$cadastro_sucesso = false;

// 1. Pega o ID via GET
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // 2. Lógica de Soft Delete: Altera o status_tecnico para 'Inativo' em vez de deletar
    $sql = "UPDATE tecnicos SET status_tecnico = 'Inativo' WHERE id_tecnico = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Responde apenas SUCESSO para o AJAX no mascaras.js
        echo "SUCESSO";
    } else {
        echo "ERRO_AO_ATUALIZAR";
    }
    $stmt->close();
} else {
    echo "ID_INVALIDO";
}

$conexao->close();
?>