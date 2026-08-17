<?php
// cron/chamados_automaticos.php
require_once __DIR__ . '/../tabelas/conexao.php';

$hoje = date('Y-m-d');
$dataAtualFormatada = date('d/m/Y');
$diaSemana = (int) date('N'); // 1 = Segunda-feira
$diaMes = (int) date('j');    // Dia do mês (1 a 31)

// 1. Busca todas as rotinas ativas
$sqlRotinas = "SELECT * FROM rotinas WHERE ativo = 1";
$resRotinas = mysqli_query($conexao, $sqlRotinas);

if (!$resRotinas) {
    die("Erro ao buscar rotinas: " . mysqli_error($conexao));
}

// Busca todos os clientes para aplicar rotinas globais (id_cliente = NULL)
$sqlClientes = "SELECT id_cliente FROM clientes";
$resClientes = mysqli_query($conexao, $sqlClientes);
$todosClientes = mysqli_fetch_all($resClientes, MYSQLI_ASSOC);

$totalExecutadas = 0;

while ($rotina = mysqli_fetch_assoc($resRotinas)) {
    $idRotina = $rotina['id_rotina'];
    $frequencia = trim($rotina['frequencia']);
    $ultimaExec = $rotina['ultima_execucao'];

    // Evita duplicidade no mesmo dia
    if ($ultimaExec === $hoje) {
        continue;
    }

    $deveRodar = false;

    // 2. Validação da regra por frequência (com e sem acento)
    if (in_array($frequencia, ['Diario', 'Diário', 'diario', 'diário'])) {
        $deveRodar = true;
    } elseif (in_array($frequencia, ['Semanal', 'semanal']) && $diaSemana === 1) { 
        $deveRodar = true; // Toda segunda-feira
    } elseif (in_array($frequencia, ['Mensal', 'mensal']) && $diaMes === 1) { 
        $deveRodar = true; // Todo dia 1º
    }

    if ($deveRodar) {
        $titulo = "[ROTINA " . strtoupper($frequencia) . "] " . $rotina['titulo'] . " - " . $dataAtualFormatada;
        $descricao = $rotina['descricao'];
        $prioridade = !empty($rotina['prioridade']) ? $rotina['prioridade'] : 'Media';
        $descricaoCompleta = "{$titulo}\n\n{$descricao}";
        
        // Define texto padrão de solução
        if (stripos($rotina['titulo'], 'firewall') !== false || stripos($rotina['titulo'], 'bloqueio') !== false || stripos($rotina['titulo'], 'ip') !== false) {
            $periodoAnterior = date('d/m/Y', strtotime('-1 day'));
            $solucao = "[Relatório de Segurança - WatchGuard Firebox]\n• Período: {$periodoAnterior} a {$dataAtualFormatada}\n• Status: ATIVA / OPERACIONAL\n• Mitigações aplicadas automaticamente.";
        } else {
            $solucao = "Atividade de rotina preventiva/operacional finalizada automaticamente pelo sistema em {$dataAtualFormatada}.";
        }

        // Define quais clientes receberão o chamado
        $alvoClientes = [];
        if (!empty($rotina['id_cliente'])) {
            $alvoClientes[] = ['id_cliente' => $rotina['id_cliente']];
        } else {
            $alvoClientes = $todosClientes;
        }

        // 3. Insere os chamados como Concluído
        $stmtInsert = mysqli_prepare(
            $conexao, 
            "INSERT INTO chamados (id_cliente, id_usuario, id_tecnico_atribuido, data_abertura, data_fechamento, status, prioridade, descricao_solicitacao, solucao, origem) 
             VALUES (?, 1, 1, NOW(), NOW(), 'Concluido', ?, ?, ?, 'Sistema')"
        );

        foreach ($alvoClientes as $cli) {
            $idCli = (int) $cli['id_cliente'];
            mysqli_stmt_bind_param($stmtInsert, "isss", $idCli, $prioridade, $descricaoCompleta, $solucao);
            mysqli_stmt_execute($stmtInsert);
            $totalExecutadas++;
        }
        mysqli_stmt_close($stmtInsert);

        // 4. Marca a última execução como hoje na rotina
        $stmtUpdate = mysqli_prepare($conexao, "UPDATE rotinas SET ultima_execucao = ? WHERE id_rotina = ?");
        mysqli_stmt_bind_param($stmtUpdate, "si", $hoje, $idRotina);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
    }
}

echo "Processamento concluído. {$totalExecutadas} chamados gerados com sucesso!\n";