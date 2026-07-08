<nav class="navbar-superior">
    <div class="navbar-logo" style="font-weight: bold; font-size: 16px; margin-right: 30px;">
        Empresa
    </div>

    <?php if (basename($_SERVER['SCRIPT_NAME']) !== 'index.php'): ?>
        <ul class="navbar-links">
            <?php 
            // SE FOR ADMIN: Mostra todos os links de navegação do sistema
            if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'admin'): 
            ?>
                <li><a href="../chamados/lista_chamados.php">📋 Chamados</a></li>
                <li><a href="../clientes/lista_clientes.php">🏭 Empresas</a></li>
                <li><a href="../usuarios/lista_usuarios.php">👥 Usuários</a></li>
                <li><a href="../../logout.php">🚪 Sair</a></li>
            
            <?php 
            // SE FOR USUÁRIO COMUM (CLIENTE): O menu fica limpo, só com o Sair
            else: 
            ?>
                Olá, <?php echo isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome']) : 'Usuário'; ?>
                <li><a href="../../logout.php">🚪 Sair</a></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</nav>