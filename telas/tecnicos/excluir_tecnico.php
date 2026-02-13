<?php
// Configurações de Conexão
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados";

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Erro ao conectar");
}

// 1. Pega o ID via GET
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // 2. Lógica de Soft Delete: Altera o status para 'Inativo' em vez de deletar
    $sql = "UPDATE tecnicos SET ativo = 'Inativo' WHERE id_tecnico = ?";
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