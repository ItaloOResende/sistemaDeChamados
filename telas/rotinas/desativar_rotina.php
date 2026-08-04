<?php
session_start();

// TRAVA DE SEGURANÇA: Só administrador executa essa ação
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] !== 'admin') {
    echo "SEM_PERMISSAO";
    exit();
}

include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$id_rotina = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_rotina > 0) {
    // Inverte o status atual: se era 1 vira 0 (desativa), se era 0 vira 1 (ativa)
    $sql = "UPDATE rotinas SET ativo = IF(ativo = 1, 0, 1) WHERE id_rotina = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_rotina);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "SUCESSO";
        } else {
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