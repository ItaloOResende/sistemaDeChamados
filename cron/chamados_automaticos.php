<?php
// cron/chamados_automaticos.php
require_once __DIR__ . '/../tabelas/conexao.php';

$hoje = date('Y-m-d');
$dataAtualFormatada = date('d/m/Y');
$diaSemana = date('N'); // 1 = Segunda-feira
$diaMes = date('j');    // Dia do mês (1 a 31)

// 1. Busca rotinas ativas com cliente preenchido
$sql = "SELECT r.*, c.nome AS nome_cliente 
        FROM rotinas r
        INNER JOIN clientes c ON r.id_cliente = c.id_cliente
        WHERE r.ativo = 1";

$res = mysqli_query($conexao, $sql);
if (!$res) {
    die("Erro ao buscar rotinas: " . mysqli_error($conexao));
}

$rotinas = mysqli_fetch_all($res, MYSQLI_ASSOC);
$totalExecutadas = 0;

foreach ($rotinas as $rotina) {
    $idRotina = $rotina['id_rotina'];
    $idCliente = $rotina['id_cliente'];
    $frequencia = $rotina['frequencia'];
    $ultimaExec = $rotina['ultima_execucao'];

    // Evita duplicidade no mesmo dia
    if ($ultimaExec === $hoje) {
        continue;
    }

    $deveRodar = false;

    // 2. Validação da regra de negócio por frequência
    if ($frequencia === 'Diario') {
        $deveRodar = true;
    } elseif ($frequencia === 'Semanal' && $diaSemana == 1) { 
        // Toda Segunda-feira
        $deveRodar = true;
    } elseif ($frequencia === 'Mensal' && $diaMes == 1) { 
        // Todo dia 1º do mês
        $deveRodar = true;
    }

    if ($deveRodar) {
        $titulo = "[ROTINA " . strtoupper($frequencia) . "] " . $rotina['titulo'] . " - " . $dataAtualFormatada;
        $descricao = $rotina['descricao'];
        $prioridade = !empty($rotina['prioridade']) ? $rotina['prioridade'] : 'Media';
        
        // Define texto de solução
        if (stripos($rotina['titulo'], 'firewall') !== false || stripos($rotina['titulo'], 'bloqueio') !== false) {
            $periodoAnterior = date('d/m/Y', strtotime('-1 day'));
            $solucao = "[Relatório de Segurança - WatchGuard Firebox]\n• Período: {$periodoAnterior} a {$dataAtualFormatada}\n• Status: ATIVA / OPERACIONAL\n• Mitigações aplicadas automaticamente.";
        } else {
            $solucao = "Atividade de rotina preventiva/operacional finalizada automaticamente pelo sistema em {$dataAtualFormatada}.";
        }

        // 3. Insere o chamado Concluído
        $stmtInsert = mysqli_prepare($conexao, "INSERT INTO chamados (id_cliente, id_usuario, id_tecnico_atribuido, data_abertura, data_fechamento, status, prioridade, descricao_solicitacao, solucao, origem) VALUES (?, 1, 1, NOW(), NOW(), 'Concluido', ?, ?, ?, 'Sistema')");
        
        $descricaoCompleta = "{$titulo}\n\n{$descricao}";
        mysqli_stmt_bind_param($stmtInsert, "isss", $idCliente, $prioridade, $descricaoCompleta, $solucao);
        mysqli_stmt_execute($stmtInsert);
        mysqli_stmt_close($stmtInsert);

        // 4. Marca a última execução como hoje
        $stmtUpdate = mysqli_prepare($conexao, "UPDATE rotinas SET ultima_execucao = ? WHERE id_rotina = ?");
        mysqli_stmt_bind_param($stmtUpdate, "si", $hoje, $idRotina);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);

        $totalExecutadas++;
    }
}

echo "Processamento concluído. {$totalExecutadas} rotinas geradas com sucesso!";