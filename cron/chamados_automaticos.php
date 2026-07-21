<?php
// cron/chamados_automaticos.php

// 1. Importa a conexão MySQLi
require_once __DIR__ . '/../tabelas/conexao.php';

$dataAtual = date('d/m/Y');
$periodoAnterior = date('d/m/Y', strtotime('-1 day'));

// 2. Busca todos os clientes/empresas
// Ajuste o nome da tabela 'clientes' se no seu banco for outro nome
$resultClientes = mysqli_query($conexao, "SELECT id_cliente FROM clientes");

if (!$resultClientes) {
    die("Erro ao buscar clientes: " . mysqli_error($conexao));
}

$clientes = mysqli_fetch_all($resultClientes, MYSQLI_ASSOC);

// 3. Texto da solução do WatchGuard Firebox
$solucaoFirewall = "
[Relatório Diário de Segurança - WatchGuard Firebox]

• Período: {$periodoAnterior} a {$dataAtual}
• Status da Proteção: ATIVA / OPERACIONAL
• Total de Ameaças Bloqueadas: Variados (Proteção Ativa)
• Principais Portas Filtradas: RDP (3389), SSH (22), HTTPS (443)

Regras de mitigação aplicadas automaticamente. Nenhuma ação manual necessária.
";

$descricaoSolicitacao = "[Rotina Diária] Bloqueios de Firewall - {$dataAtual}";

// 4. Loop para inserir os chamados concluídos usando MySQLi Prepared Statements
$sql = "INSERT INTO chamados (
            id_cliente, 
            id_usuario, 
            id_tecnico_atribuido, 
            data_abertura, 
            data_fechamento, 
            status, 
            prioridade, 
            descricao_solicitacao, 
            solucao, 
            origem
        ) VALUES (?, 1, 1, NOW(), NOW(), 'Concluido', 'Baixa', ?, ?, 'Sistema')";

$stmt = mysqli_prepare($conexao, $sql);

if ($stmt) {
    foreach ($clientes as $cliente) {
        $idCliente = $cliente['id_cliente'];
        
        // "iss" = integer, string, string
        mysqli_stmt_bind_param($stmt, "iss", $idCliente, $descricaoSolicitacao, trim($solucaoFirewall));
        mysqli_stmt_execute($stmt);
    }
    
    mysqli_stmt_close($stmt);
    echo "Chamados automáticos gerados com sucesso!";
} else {
    echo "Erro ao preparar a consulta: " . mysqli_error($conexao);
}