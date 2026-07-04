<?php
session_start();

// TRAVA DE SEGURANÇA: Só o administrador pode excluir usuários
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] !== 'admin') {
    header("Location: ../chamados/lista_chamados.php");
    exit();
}

// ---------------------------------------------
// 1. CONFIGURAÇÕES E CONEXÃO
// ---------------------------------------------
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

// ---------------------------------------------
// 2. PROCESSAMENTO DO ID VIA GET (Vindo do JavaScript)
// ---------------------------------------------
$id_usuario = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_usuario > 0) {
    // ---------------------------------------------
    // 3. LÓGICA DE EXCLUSÃO (HARD DELETE)
    // ---------------------------------------------
    $sql = "DELETE FROM usuarios WHERE id = ?";
    
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Deletou com sucesso -> Volta para a lista com a mensagem de sucesso
                $conexao->close();
                header("Location: lista_usuarios.php?status=success_delete");
                exit();
            } else {
                // O ID não foi encontrado no banco
                $conexao->close();
                header("Location: lista_usuarios.php?status=error_no_id");
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Se der erro de chave estrangeira (ex: se o usuário tiver chamados vinculados a ele)
        $conexao->close();
        header("Location: lista_usuarios.php?status=error_delete");
        exit();
    }
    
    $stmt->close();
} else {
    $conexao->close();
    header("Location: lista_usuarios.php?status=error_no_id");
    exit();
}

$conexao->close();
?>