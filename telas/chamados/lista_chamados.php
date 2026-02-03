<?php
// 1. CONEXÃO E LÓGICA DE FILTROS
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados"; 

$conexao = new mysqli($servidor, $usuario, $senha, $banco);
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }
$conexao->set_charset("utf8mb4");

// Captura de filtros do GET
$f_id_chamado = $_GET['id_chamado'] ?? '';
$f_id_cliente = $_GET['id_cliente'] ?? '';
$f_id_tecnico = $_GET['id_tecnico'] ?? '';
$f_data_inicio = $_GET['data_inicio'] ?? '';
$f_texto      = $_GET['texto_busca'] ?? '';
$f_status     = $_GET['status'] ?? '';
$f_prioridade = $_GET['prioridade'] ?? '';
$ordenar_por = $_GET['ordem'] ?? 'c.data_abertura';
$direcao     = $_GET['dir'] ?? 'DESC';

// SQL Base com Joins
$sql = "SELECT c.*, cli.nome_empresa, t.nome_tecnico 
        FROM chamados c
        LEFT JOIN clientes cli ON c.id_cliente = cli.id_cliente
        LEFT JOIN tecnicos t ON c.id_tecnico_atribuido = t.id_tecnico
        WHERE 1=1";

$params = []; $types = "";
if (!empty($f_id_chamado)) { $sql .= " AND c.id_chamado = ?"; $params[] = $f_id_chamado; $types .= "i"; }
if (!empty($f_id_cliente)) { $sql .= " AND c.id_cliente = ?"; $params[] = $f_id_cliente; $types .= "i"; }
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

$lista_clientes = $conexao->query("SELECT id_cliente, nome_empresa FROM clientes ORDER BY nome_empresa ASC");
$lista_tecnicos = $conexao->query("SELECT id_tecnico, nome_tecnico FROM tecnicos ORDER BY nome_tecnico ASC");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Chamados - Black TI</title>
    <link rel="stylesheet" href="../../estilos/estilos.css">
    <style>
        /* Ajustes específicos para esta página */
        .barra-filtros { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 15px; 
            background: #fff; 
            padding: 20px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            margin-bottom: 25px; 
            align-items: flex-end;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
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
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>

    <main>
        <h2 style="text-align: center; margin: 20px 0; width: 100%;">📋 Painel de Chamados</h2>

        <form method="GET" class="barra-filtros">
            <div class="campo" style="max-width: 70px;"><label>ID</label>
                <input type="number" name="id_chamado" value="<?php echo $f_id_chamado; ?>">
            </div>
            
            <div class="campo"><label>Cliente</label>
                <select name="id_cliente">
                    <option value="">Todos</option>
                    <?php while($c = $lista_clientes->fetch_assoc()): ?>
                        <option value="<?php echo $c['id_cliente']; ?>" <?php echo $f_id_cliente == $c['id_cliente'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nome_empresa']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="campo"><label>Técnico</label>
                <select name="id_tecnico">
                    <option value="">Todos</option>
                    <?php while($t = $lista_tecnicos->fetch_assoc()): ?>
                        <option value="<?php echo $t['id_tecnico']; ?>" <?php echo $f_id_tecnico == $t['id_tecnico'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['nome_tecnico']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="campo" style="max-width: 150px;"><label>Data Inicial</label>
                <input type="date" name="data_inicio" value="<?php echo $f_data_inicio; ?>">
            </div>

            <div class="campo" style="max-width: 140px;"><label>Status</label>
                <select name="status">
                    <option value="">Todos</option>
                    <option value="Novo" <?php echo $f_status == 'Novo' ? 'selected' : ''; ?>>Novo</option>
                    <option value="Em Atendimento" <?php echo $f_status == 'Em Atendimento' ? 'selected' : ''; ?>>Em Atendimento</option>
                    <option value="Concluído" <?php echo $f_status == 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
                </select>
            </div>

            <div class="campo" style="flex-grow: 2;"><label>Busca (Desc/Solução)</label>
                <input type="text" name="texto_busca" value="<?php echo $f_texto; ?>" placeholder="Pesquisar...">
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
                    <th>Cliente</th>
                    <th>Técnico</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Origem</th>
                    <th>Abertura</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while($row = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><a href="detalhes_chamado.php?id=<?php echo $row['id_chamado']; ?>">#<?php echo $row['id_chamado']; ?></a></td>
                        <td><?php echo htmlspecialchars($row['nome_empresa']); ?></td>
                        <td><?php echo htmlspecialchars($row['nome_tecnico'] ?? 'Pendente'); ?></td>
                        <td class="prioridade-<?php echo $row['prioridade']; ?>"><?php echo $row['prioridade']; ?></td>
                        <td><strong><?php echo $row['status']; ?></strong></td>
                        <td><?php echo htmlspecialchars($row['origem']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['data_abertura'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">Nenhum chamado encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>