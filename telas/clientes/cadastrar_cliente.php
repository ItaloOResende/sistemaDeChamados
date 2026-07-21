<?php
session_start();
// ---------------------------------------------
// 1. CONFIGURAÇÃO DE CONEXÃO COM O BANCO DE DADOS
// ---------------------------------------------
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$mensagem = "";
$cadastro_sucesso = false;
$codigo_gerado = "";

// ---------------------------------------------
// 2. LÓGICA DE PROCESSAMENTO DO FORMULÁRIO
// ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e limpeza básica dos dados
    $nome_empresa      = trim($_POST['nome_empresa']);
    $localizacao       = trim($_POST['localizacao']);
    $contato_principal = trim($_POST['contato_principal']);
    $email_contato     = trim($_POST['email_contato']); 
    $num_celular       = preg_replace("/[^0-9]/", "", $_POST['num_celular']);
    $codigo_digitado   = strtoupper(trim($_POST['codigo_empresa'] ?? ''));

    // Geração do Código se o Admin deixou em branco: Pega 3 letras do nome + 3 números aleatórios
    if (empty($codigo_digitado)) {
        $prefixo = strtoupper(substr(preg_replace("/[^a-zA-Z]/", "", $nome_empresa), 0, 3));
        if (strlen($prefixo) < 3) { $prefixo = str_pad($prefixo, 3, "X"); }
        $codigo_empresa = $prefixo . rand(100, 999);
    } else {
        $codigo_empresa = $codigo_digitado;
    }
    
    // Validação de campos obrigatórios
    if (empty($nome_empresa) || empty($email_contato) || empty($contato_principal) || empty($num_celular) || empty($localizacao)) {
        $mensagem = "<div class='msg-erro'>❌ Erro: Todos os campos obrigatórios devem ser preenchidos.</div>";
    } else {
        // Query SQL com o novo campo codigo_empresa
        $sql = "INSERT INTO clientes (nome_empresa, codigo_empresa, localizacao, contato_principal, num_celular, email_contato, status_cliente) VALUES (?, ?, ?, ?, ?, ?, 'Ativo')";
        
        try {
            $stmt = $conexao->prepare($sql);
            $stmt->bind_param("ssssss", $nome_empresa, $codigo_empresa, $localizacao, $contato_principal, $num_celular, $email_contato); 

            if ($stmt->execute()) {
                $cadastro_sucesso = true; 
                $codigo_gerado = $codigo_empresa;
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062 || $conexao->errno == 1062) {
                $mensagem = "<div class='msg-erro'>❌ Erro: Já existe uma empresa cadastrada com esse código ou e-mail.</div>";
            } else {
                $mensagem = "<div class='msg-erro'>❌ Erro ao cadastrar empresa: " . $e->getMessage() . "</div>";
            }
        }
        
        if (isset($stmt)) {
            $stmt->close(); 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Empresas</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
    <style>
        textarea { resize: vertical; min-height: 100px; } 
        .caixa-codigo { background: #e7f3fe; border-left: 6px solid #2196F3; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .caixa-codigo strong { font-size: 18px; color: #0c5460; }
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>🏭 Cadastro de Empresas</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem; ?>

        <form method="POST" action="">
            
            <label for="nome_empresa">Nome da Empresa (*):</label>
            <input type="text" id="nome_empresa" name="nome_empresa" maxlength="255" placeholder="Ex: GTM Alimentos" required>

            <label for="codigo_empresa">Código de Acesso/LGPD (Opcional):</label>
            <input type="text" id="codigo_empresa" name="codigo_empresa" maxlength="20" placeholder="Ex: EMPRESA123 (Deixe em branco para gerar automático)" style="text-transform: uppercase;">
            <small style="color: #666; font-size: 11px; display: block; margin-top: -8px; margin-bottom: 12px;">Se não digitar nada, o sistema criará um código automático de 6 dígitos.</small>

            <label for="email_contato">Email de Contato (*):</label>
            <input type="email" id="email_contato" name="email_contato" maxlength="100" required>

            <label for="contato_principal">Contato Principal (*):</label>
            <input type="text" id="contato_principal" name="contato_principal" maxlength="100" placeholder="Ex: Silas / RH" required>

            <label for="num_celular">Número (*):</label>
            <input type="text" id="num_celular" name="num_celular" placeholder="(00) 00000-0000" maxlength="15" required>

            <label for="localizacao">Localização (*):</label>
            <input type="text" id="localizacao" name="localizacao" maxlength="255" required>
            
            <button type="submit">Cadastrar Cliente</button>
        </form>
        
        <div class="voltar">
             <a href="lista_clientes.php">← Voltar para Lista de Clientes</a>
        </div>
    </main>

    <script src="../../js/mascaras.js"></script>

    <?php 
    // SE o cadastro_sucesso for TRUE, exibe o popup informando o código gerado antes de redirecionar
    if ($cadastro_sucesso === true) {
        echo "
            <script>
                alert('✅ Empresa cadastrada com sucesso!\\n\\n🔑 CÓDIGO DA EMPRESA: " . $codigo_gerado . "\\n\\nPasse este código para o gestor/funcionários da empresa se cadastrarem.');
                window.location.href = 'lista_clientes.php?status=success_add';
            </script>
        ";
    }
    $conexao->close();
    ?>
</body>
</html>