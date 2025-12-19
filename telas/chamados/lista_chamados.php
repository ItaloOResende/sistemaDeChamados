<?php
// ... (Toda a lógica PHP de conexão e filtros permanece EXATAMENTE a mesma do código anterior) ...
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados"; 

$conexao = new mysqli($servidor, $usuario, $senha, $banco);
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }
$conexao->set_charset("utf8mb4");

$f_id_chamado = $_GET['id_chamado'] ?? '';
$f_id_cliente = $_GET['id_cliente'] ?? '';
$f_id_tecnico = $_GET['id_tecnico'] ?? '';
$f_data_inicio = $_GET['data_inicio'] ?? '';
$f_texto      = $_GET['texto_busca'] ?? '';
$f_status     = $_GET['status'] ?? '';
$f_prioridade = $_GET['prioridade'] ?? '';
$ordenar_por = $_GET['ordem'] ?? 'c.data_abertura';
$direcao     = $_GET['dir'] ?? 'DESC';

$colunas_permitidas = ['c.status', 'c.prioridade', 'c.origem', 'c.data_abertura', 'c.id_chamado'];
if (!in_array($ordenar_por, $colunas_permitidas)) { $ordenar_por = 'c.data_abertura'; }

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
if (!empty($f_status))     { $sql .= " AND c.status = ?"; $params[] = $f_status; $types .= "s"; }
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
    <title>Lista de Chamados</title>
    <link rel="stylesheet" href="../../estilos/estilos.css">
    <style>
        /* FORMULÁRIO NA HORIZONTAL TOTAL */
        .barra-filtros { 
            display: flex; 
            flex-wrap: nowrap; /* Força ficar em uma linha */
            gap: 10px; 
            background: #f4f4f4; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            align-items: flex-end; /* Alinha labels e botões embaixo */
            font-size: 11px;
        }
        .barra-filtros .campo { display: flex; flex-direction: column; flex: 1; }
        .barra-filtros input, .barra-filtros select { padding: 6px; border: 1px solid #ccc; border-radius: 4px; width: 100%; }
        
        /* BOTÕES */
        .btn-filtrar { background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-limpar { background: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-size: 11px; }
        .btn-novo-chamado { background: #28a745; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-weight: bold; white-space: nowrap; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #eee; }
        .prioridade-Alta, .prioridade-Urgente { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <h2 style="text-align:center;">📋 Painel de Chamados</h2>

    <form method="GET" class="barra-filtros">
        <div class="campo" style="max-width: 60px;"><label>ID:</label><input type="number" name="id_chamado" value="<?php echo $f_id_chamado; ?>"></div>
        
        <div class="campo"><label>Cliente:</label>
            <select name="id_cliente">
                <option value="">Todos</option>
                <?php while($c = $lista_clientes->fetch_assoc()): ?>
                    <option value="<?php echo $c['id_cliente']; ?>" <?php echo $f_id_cliente == $c['id_cliente'] ? 'selected' : ''; ?>><?php echo $c['nome_empresa']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="campo"><label>Técnico:</label>
            <select name="id_tecnico">
                <option value="">Todos</option>
                <?php while($t = $lista_tecnicos->fetch_assoc()): ?>
                    <option value="<?php echo $t['id_tecnico']; ?>" <?php echo $f_id_tecnico == $t['id_tecnico'] ? 'selected' : ''; ?>><?php echo $t['nome_tecnico']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="campo" style="max-width: 120px;"><label>Data Inicial:</label><input type="date" name="data_inicio" value="<?php echo $f_data_inicio; ?>"></div>
        
        <div class="campo"><label>Status:</label>
            <select name="status">
                <option value="">Todos</option>
                <option value="Novo" <?php echo $f_status == 'Novo' ? 'selected' : ''; ?>>Novo</option>
                <option value="Em Atendimento" <?php echo $f_status == 'Em Atendimento' ? 'selected' : ''; ?>>Em Atendimento</option>
                <option value="Concluído" <?php echo $f_status == 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
            </select>
        </div>

        <div class="campo"><label>Busca:</label><input type="text" name="texto_busca" value="<?php echo $f_texto; ?>" placeholder="Desc/Solução"></div>

        <button type="submit" class="btn-filtrar">Filtrar</button>
        <a href="lista_chamados.php" class="btn-limpar">Limpar</a>
        
        <a href="cadastrar_chamado.php" class="btn-novo-chamado">+ Novo Chamado</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Técnico</th>
                <th><a href="?<?php echo http_build_query(array_merge($_GET, ['ordem' => 'c.prioridade', 'dir' => ($direcao=='ASC'?'DESC':'ASC')])); ?>">Prioridade ↕</a></th>
                <th><a href="?<?php echo http_build_query(array_merge($_GET, ['ordem' => 'c.status', 'dir' => ($direcao=='ASC'?'DESC':'ASC')])); ?>">Status ↕</a></th>
                <th><a href="?<?php echo http_build_query(array_merge($_GET, ['ordem' => 'c.origem', 'dir' => ($direcao=='ASC'?'DESC':'ASC')])); ?>">Origem ↕</a></th>
                <th>Abertura</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $resultado->fetch_assoc()): ?>
            <tr>
                <td><a href="detalhes_chamado.php?id=<?php echo $row['id_chamado']; ?>">#<?php echo $row['id_chamado']; ?></a></td>
                <td><?php echo htmlspecialchars($row['nome_empresa']); ?></td>
                <td><?php echo htmlspecialchars($row['nome_tecnico'] ?? 'Pendente'); ?></td>
                <td class="prioridade-<?php echo $row['prioridade']; ?>"><?php echo $row['prioridade']; ?></td>
                <td><strong><?php echo $row['status']; ?></strong></td>
                <td><?php echo $row['origem']; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($row['data_abertura'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>