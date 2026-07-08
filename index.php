<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistema de Chamados</title>
    <link rel="stylesheet" href="estilos/estilos.css?v=<?php echo time(); ?>">
</head>
<body class="page-login">

<?php include_once('telas/principal/menu.php'); ?>

<div class="wrapper-login">
    <div class="login-container">
        <h2>Login</h2>
        
        <?php if (isset($_GET['erro'])): ?>
            <div class="error-msg">
                <?php 
                    if ($_GET['erro'] == 'dados_invalidos') echo "E-mail ou senha incorretos!";
                    if ($_GET['erro'] == 'acesso_negado') echo "Faça login para acessar o sistema.";
                ?>
            </div>
        <?php endif; ?>

    <?php if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso'): ?>
    <script>
        // 1. Mostra o pop-up na tela
        alert("✅ Conta criada com sucesso! Faça o seu login.");

        // 2. Limpa o '?cadastro=sucesso' da URL sem recarregar a página
        if (window.history.replaceState) {
            const novaUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({ path: novaUrl }, '', novaUrl);
        }
    </script>
    <?php endif; ?>
        
        <form action="processa_login.php" method="POST">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required placeholder="admin@teste.com.br">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required placeholder="******">
            </div>
            
            <button type="submit">Entrar</button>
        </form>

        <div class="cadastro-link">
            Não tem uma conta? <a href="telas/usuarios/cadastrar_usuario.php?tipo=cliente">Cadastre-se aqui</a>
        </div>
        
    </div> 
</div>

</body>
</html>