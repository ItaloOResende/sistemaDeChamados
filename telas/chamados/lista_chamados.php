<?php
session_start();

// GARANTE QUE O USUÁRIO ESTÁ LOGADO
if (!isset($_SESSION['usuario_perfil'])) {
    header("Location: ../../index.php");
    exit();
}

// 1. CONEXÃO E LÓGICA DE FILTROS
include_once(__DIR__ . '/../../tabelas/conexao.php'); 

// Configura o charset usando a conexão que veio do include
$conexao->set_charset("utf8mb4");

// Puxa as informações essenciais da sessão para as travas de LGPD
$perfil_logado      = $_SESSION['usuario_perfil'];
$id_usuario_logado  = (int)$_SESSION['usuario_id'];
$id_cliente_logado  = isset($_SESSION['usuario_id_cliente']) ? (int)$_SESSION['usuario_id_cliente'] : 0;

// Captura de filtros do GET
$f_id_chamado = $_GET['id_chamado'] ?? '';
$f_id_cliente = $_GET['id_cliente'] ?? '';
$f_id_tecnico = $_GET['id_tecnico'] ?? '';
$f_data_inicio = $_GET['data_inicio'] ?? '';
$f_texto      = $_GET['texto_busca'] ?? '';
$f_status     = $_GET['status'] ?? '';
$f_prioridade = $_GET['prioridade'] ?? '';
$ordenar_por = $_GET['ordem'] ?? 'c.id_chamado'; // Ordenação padrão por ID
$direcao     = $_GET['dir'] ?? 'DESC';

// 🚀 SQL Base com Joins unificado apontando para 'usuarios'
$sql = "SELECT c.*, cli.nome_empresa, u.nome AS nome_tecnico, u.status AS status_tecnico
        FROM chamados c
        LEFT JOIN clientes cli ON c.id_cliente = cli.id_cliente
        LEFT JOIN usuarios u ON c.id_tecnico_atribuido = u.id
        WHERE 1=1";

$params = []; $types = "";

// 🔒 TRAVA DE SEGURANÇA E LGPD VIA QUERY SQL BASE:
if ($perfil_logado === 'gestor') {
    // Gestor (TI Interna): Só vê chamados pertencentes à empresa dele
    $sql .= " AND c.id_cliente = ?";
    $params[] = $id_cliente_logado;
    $types .= "i";
} elseif ($perfil_logado === 'normal') {
    // Usuário Comum: Só vê estritamente os chamados que ele próprio abriu
    $sql .= " AND c.id_usuario = ?";
    $params[] = $id_usuario_logado;
    $types .= "i";
}

// Aplicação dos filtros dinâmicos do formulário
if (!empty($f_id_chamado)) { $sql .= " AND c.id_chamado = ?"; $params[] = $f_id_chamado; $types .= "i"; }

// O filtro de cliente só é aplicado se quem está buscando for Admin ou Técnico Geral
if (!empty($f_id_cliente) && ($perfil_logado === 'admin' || $perfil_logado === 'tecnico')) { 
    $sql .= " AND c.id_cliente = ?"; $params[] = $f_id_cliente; $types .= "i"; 
}

if (!empty($f_id_tecnico)) { $sql .= " AND c.id_tecnico_atribuido = ?"; $params[] = $f_id_tecnico; $types .= "i"; }
if (!empty($f_data_inicio)) { $sql .= " AND c.data_abertura >= ?"; $params[] = $f_data_inicio . " 00:00:00"; $types .= "s"; }
if (!empty($f_status))      { $sql .= " AND c.status = ?"; $params[] = $f_status; $types .= "s"; }
if (!empty($f_prioridade)) { $sql .= " AND c.prioridade = ?"; $params[] = $f_prioridade; $types .= "s"; }
if (!empty($f_texto)) { 
    $sql .= " AND (c.descricao_solicitacao LIKE ? OR c.solucao LIKE ?)"; 
    $term = "%$f_texto%"; $params[] = $term; $params[] = $term; $types .= "ss"; 
}

$sql .= " ORDER BY $ordenar_por $direcao";
$stmt = $conexao->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$resultado = $stmt->get_result();

// Montagem condicional dos Combos de Filtro baseada na LGPD
if ($perfil_logado === 'admin' || $perfil_logado === 'tecnico') {
    // Admin/Técnico listam todas as empresas ativas do mercado
    $lista_clientes = $conexao->query("SELECT id_cliente, nome_empresa FROM clientes WHERE status_cliente = 'Ativo' ORDER BY nome_empresa ASC");
}

$lista_tecnicos = $conexao->query("SELECT id, nome AS nome_tecnico FROM usuarios WHERE (perfil = 'tecnico' OR perfil = 'admin') AND status = 'Ativo' ORDER BY nome ASC");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Chamados - Black TI</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
    <style>
        .barra-filtros { display: flex; flex-wrap: wrap; gap: 15px; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 25px; align-items: flex-end; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .barra-filtros .campo { display: flex; flex-direction: column; flex: 1; min-width: 130px; }
        .barra-filtros label { font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555; text-align: left; }
        .barra-filtros input, .barra-filtros select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100%; }
        .botoes-filtros { display: flex; gap: 10px; }
        .btn-filtrar { background: #1a1a1a; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-limpar { background: #6c757d; color: white; text-decoration: none; padding: 10px 15px; border-radius: 4px; font-size: 12px; line-height: 20px; text-align: center; }
        .btn-novo-chamado { background: #28a745; color: white; text-decoration: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; margin-left: auto; white-space: nowrap; }
        
        /* Cores de Prioridade */
        .prioridade-Alta, .prioridade-Urgente { color: #d9534f; font-weight: bold; }
        .prioridade-Média, .prioridade-Media { color: #f0ad4e; font-weight: bold; }
        .prioridade-Baixa { color: #5cb85c; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 12px; border: 1px solid #eee; text-align: left; }
        th { background-color: #f8f9fa; color: #333; }
        
        .tag-inativo { color: #d9534f; font-size: 10px; font-weight: normal; margin-left: 5px; }
        .col-descricao { max-width: 250px; font-size: 13px; color: #666; font-style: italic; }
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>

    <main style="padding: 20px;">
        <h2 style="text-align: center; margin-bottom: 20px;">📋 Painel de Chamados</h2>

        <form method="GET" class="barra-filtros">
            <div class="campo" style="max-width: 70px;"><label>ID</label>
                <input type="number" name="id_chamado" value="<?php echo htmlspecialchars($f_id_chamado); ?>">
            </div>
            
            <!-- 🔒 LGPD: O filtro de Cliente some completamente para Usuário Comum e Gestor -->
            <?php if ($perfil_logado === 'admin' || $perfil_logado === 'tecnico'): ?>
                <div class="campo"><label>Cliente</label>
                    <select name="id_cliente">
                        <option value="">Todos (Ativos)</option>
                        <?php if (isset($lista_clientes)): ?>
                            <?php while($c = $lista_clientes->fetch_assoc()): ?>
                                <option value="<?php echo $c['id_cliente']; ?>" <?php echo $f_id_cliente == $c['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nome_empresa']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="campo"><label>Técnico</label>
                <select name="id_tecnico">
                    <option value="">Todos (Ativos)</option>
                    <?php while($t = $lista_tecnicos->fetch_assoc()): ?>
                        <option value="<?php echo $t['id_tecnico'] ?? $t['id']; ?>" <?php echo ($f_id_tecnico == ($t['id_tecnico'] ?? $t['id'])) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['nome_tecnico']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="campo" style="max-width: 150px;"><label>Data Inicial</label>
                <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($f_data_inicio); ?>">
            </div>

            <div class="campo" style="max-width: 140px;"><label>Status</label>
                <select name="status">
                    <option value="">Todos</option>
                    <option value="Novo" <?php echo $f_status == 'Novo' ? 'selected' : ''; ?>>Novo</option>
                    <option value="Em Atendimento" <?php echo $f_status == 'Em Atendimento' ? 'selected' : ''; ?>>Em Atendimento</option>
                    <option value="Concluido" <?php echo $f_status == 'Concluido' ? 'selected' : ''; ?>>Concluído</option>
                    <option value="Cancelado" <?php echo $f_status == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>

            <div class="campo" style="flex-grow: 2;"><label>Busca (Desc/Solução)</label>
                <input type="text" name="texto_busca" value="<?php echo htmlspecialchars($f_texto); ?>" placeholder="Pesquisar...">
            </div>

            <div class="botoes-filtros">
                <button type="submit" class="btn-filtrar">Filtrar</button>
                <a href="lista_chamados.php" class="btn-limpar">Limpar</a>
            </div>
            <a href="cadastrar_chamado.php" class="btn-novo-chamado">+ Novo</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <!-- 🔒 O Cabeçalho de Cliente some para o Usuário Comum -->
                    <?php if ($perfil_logado !== 'normal'): ?>
                        <th>Cliente</th>
                    <?php endif; ?>
                    <th>Técnico</th>
                    <th>Abertura</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><a href="detalhes_chamado.php?id=<?php echo $row['id_chamado']; ?>">#<?php echo $row['id_chamado']; ?></a></td>
                        
                        <!-- 🔒 Os dados da empresa só aparecem para Gestores, Técnicos e Admins Gerais -->
                        <?php if ($perfil_logado !== 'normal'): ?>
                            <td><?php echo htmlspecialchars($row['nome_empresa']); ?></td>
                        <?php endif; ?>
                        
                        <td>
                            <?php 
                                echo htmlspecialchars($row['nome_tecnico'] ?? ' '); 
                                if (isset($row['status_tecnico']) && $row['status_tecnico'] == 'Inativo') {
                                    echo '<span class="tag-inativo">(Inativo)</span>';
                                }
                            ?>
                        </td>

                        <td><?php echo date('d/m/Y H:i', strtotime($row['data_abertura'])); ?></td>

                        <td class="prioridade-<?php echo $row['prioridade']; ?>"><?php echo $row['prioridade']; ?></td>
                        
                        <td><strong><?php echo $row['status']; ?></strong></td>

                        <td class="col-descricao">
                            <?php 
                                $descricao = htmlspecialchars($row['descricao_solicitacao']);
                                echo mb_strimwidth($descricao, 0, 60, "..."); 
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <!-- Ajusta o colspan dinamicamente para não quebrar a tabela -->
                        <td colspan="<?php echo $perfil_logado === 'normal' ? '6' : '7'; ?>" style="text-align:center;">
                            Nenhum chamado encontrado.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
    <?php $conexao->close(); ?>
</body>
</html>