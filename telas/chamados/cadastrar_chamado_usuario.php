<?php
session_start();

// TRAVA DE SEGURANÇA: Garante que o cara está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

$mensagem = "";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Abrir Chamado</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
    <style>
        textarea { resize: vertical; min-height: 150px; width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
        .form-container { max-width: 500px; margin: 30px auto; padding: 20px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; }
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header style="text-align: center; margin-top: 20px;">
        <h1>📝 Abrir Novo Chamado</h1>
    </header>
    <hr>

    <main>
        <div class="form-container">
            <?php echo $mensagem; ?>

            <form method="POST" action="processa_chamado_cliente.php">
                <h2>Detalhes da Solicitação</h2>
                <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
                    Descreva o problema com o máximo de detalhes possível para que possamos te ajudar o quanto antes.
                </p>
                
                <label Reqd for="descricao" style="font-weight: bold; display: block; margin-bottom: 8px;">Descrição Detalhada do Problema (*):</label>
                <textarea id="descricao" name="descricao" placeholder="Ex: Minha impressora não está puxando papel / O sistema X está dando erro de conexão..." required></textarea>
                
                <button type="submit" style="background-color: #4CAF50; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 15px;">
                    Enviar Solicitação
                </button>
            </form>
        </div>
    </main>
<script src="../../js/mascaras.js"></script>

    <script>
        // SE o PHP identificar que o chamado foi gravado (?sucesso=1 na URL)
        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
            
            // Abre o pop-up nativo do navegador
            alert('Chamado aberto com sucesso!');
            
            // Assim que o cara clica em OK, redireciona limpando o "?sucesso=1" da URL e mantém ele na tela
            window.location.href = 'cadastrar_chamado_usuario.php';

        <?php endif; ?>
    </script>
</body>
</html>
</body>
</html>