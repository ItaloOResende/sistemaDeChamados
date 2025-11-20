<?php
// Configurações de Conexão
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados";

// Conexão com o Banco de Dados
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Erro ao conectar: " . $conexao->connect_error);
}

// 1. Verifica se o ID foi passado via GET
if (!isset($_GET['id'])) {
    // Redireciona com erro de ID (se necessário) e sai
    header("Location: lista_tecnicos.php?status=error_no_id");
    exit();
}

// Pega o ID e garante que é um inteiro
$id = (int) $_GET['id'];

// Query de Exclusão
$sql = "DELETE FROM tecnicos WHERE id_tecnico = $id";

// 2. Tenta executar a exclusão
if ($conexao->query($sql) === TRUE) {
    // Exclusão BEM-SUCEDIDA: Redireciona com status de sucesso
    header("Location: lista_tecnicos.php?status=success_delete");
    exit();
} else {
    // 3. Verifica se o erro é de Chave Estrangeira (FK)
    // O código de erro comum para restrição de Foreign Key no MySQL é 1451
    if ($conexao->errno == 1451) {
        // Erro de FK: O técnico possui registros associados (chamados)
        header("Location: lista_tecnicos.php?status=error_fk");
        exit();
    } else {
        // Outro Erro: Erro genérico na exclusão
        header("Location: lista_tecnicos.php?status=error_delete");
        exit();
    }
}

$conexao->close();

// Nota: Certifique-se de que o caminho "../lista_tecnicos.php" está correto
// Se o seu arquivo excluir_tecnico.php estiver no mesmo diretório, use apenas "lista_tecnicos.php"
// Ajustei o caminho no código acima para "lista_tecnicos.php" assumindo que eles estão no mesmo nível.
?>