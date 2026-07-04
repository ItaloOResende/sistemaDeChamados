<?php
// 1. Inicia a sessão para o PHP saber de quem estamos falando
session_start();

// 2. Limpa todas as variáveis salvas na sessão (id, nome, perfil, etc.)
$_SESSION = array();

// 3. Destrói a sessão ativa no servidor de vez
session_destroy();

// 4. Redireciona o cara de volta para a tela de login (index.php)
header("Location: index.php");
exit();
?>