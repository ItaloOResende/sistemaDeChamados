<?php
// ---------------------------------------------
// 1. CONFIGURAÇÕES
// ---------------------------------------------
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados";

// ---------------------------------------------
// 2. CONEXÃO COM O BANCO DE DADOS
// ---------------------------------------------
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    // Em caso de falha crítica na conexão, encerra a execução
    header("Location: lista_clientes.php?status=error_conexao");
    exit();
}

// ---------------------------------------------
// 3. VERIFICAÇÃO E PROCESSAMENTO DO ID
// ---------------------------------------------

// Verifica se o ID foi passado via GET (como esperado pela sua função JS)
if (!isset($_GET['id'])) {
    header("Location: lista_clientes.php?status=error_no_id");
    exit();
}

// Pega o ID e garante que é um inteiro (boa prática de segurança)
$id_cliente = (int) $_GET['id'];

// Query de Exclusão focada na tabela clientes
$sql = "DELETE FROM clientes WHERE id_cliente = $id_cliente";

// ---------------------------------------------
// 4. TENTA EXECUTAR A EXCLUSÃO
// ---------------------------------------------
if ($conexao->query($sql) === TRUE) {
    // Exclusão BEM-SUCEDIDA
    header("Location: lista_clientes.php?status=success_delete");
    exit();
} else {
    // 5. TRATAMENTO DE ERROS

    // O código de erro comum para restrição de Foreign Key no MySQL é 1451
    if ($conexao->errno == 1451) {
        // Erro de FK: O cliente possui registros associados (chamados, interações)
        header("Location: lista_clientes.php?status=error_fk");
        exit();
    } else {
        // Outro Erro: Erro genérico na exclusão
        header("Location: lista_clientes.php?status=error_delete");
        exit();
    }
}

$conexao->close();
?>