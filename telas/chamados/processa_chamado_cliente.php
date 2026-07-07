<?php
session_start();

// 1. GARANTE QUE O USUÁRIO ESTÁ LOGADO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['usuario_id'])) {
    include_once(__DIR__ . '/../../tabelas/conexao.php'); 
    $conexao->set_charset("utf8mb4");

    // 2. COLETA A DESCRIÇÃO ENVIADA PELO CLIENTE (Bate com o name="descricao" do HTML)
    $descricao = trim($_POST['descricao']);
    
    // 3. COLETA O ID DA EMPRESA DIRETO DA SESSÃO DO LOGIN
    $id_cliente = $_SESSION['usuario_id_cliente'] ?? $_SESSION['id_cliente_vinculado'] ?? $_SESSION['id_cliente'] ?? 0;

    if ($id_cliente === 0) {
        die("❌ Erro grave: O sistema não encontrou o ID da sua empresa na sessão. Faça logout e login novamente para corrigir.");
    }
    
    // 4. REGRAS PADRÃO CONFORME OS ENUMS DA SUA TABELA
    $status_inicial = 'Novo';          // Na sua tabela é: 'Novo', 'Em Atendimento', etc.
    $prioridade_padrao = 'Média';      // Na sua tabela é: 'Baixa', 'Média', 'Alta', 'Urgente'
    $origem_padrao = 'Sistema';
    $id_tecnico_atribuido = null;      // Começa sem nenhum técnico vinculado

    if (!empty($descricao)) {
        // Query ajustada com os nomes REAIS das colunas da sua tabela 'chamados'
        $sql = "INSERT INTO chamados (id_cliente, id_tecnico_atribuido, status, prioridade, descricao_solicitacao, origem) 
                VALUES (?, ?, ?, ?, ?, ?)";
                
        $stmt = $conexao->prepare($sql);
        
        // "iissss" -> 2 inteiros (id_cliente, id_tecnico_atribuido) e 4 strings (status, prioridade, descricao, origem)
        $stmt->bind_param("iissss", $id_cliente, $id_tecnico_atribuido, $status_inicial, $prioridade_padrao, $descricao, $origem_padrao);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conexao->close();
            
            // 5. DEU CERTO: Redireciona o cliente de volta para acionar o pop-up
            header("Location: cadastrar_chamado_usuario.php?sucesso=1");
            exit();
        } else {
            echo "Erro ao registrar chamado: " . $conexao->error;
        }
    } else {
        header("Location: abrir_chamado_cliente.php");
        exit();
    }
    $conexao->close();
} else {
    header("Location: ../../index.php");
    exit();
}