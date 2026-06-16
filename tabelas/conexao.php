<?php
// 1. Tenta ler as variáveis da Render. Se não existirem no PC, usa o localhost por padrão.
$host     = getenv('DB_HOST') ?: 'localhost';
$port     = getenv('DB_PORT') ?: '3306';
$user     = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname   = getenv('DB_NAME') ?: 'sistemadechamados'; 

// 2. Cria a conexão usando o mysqli_init para poder ativar o SSL
$conexao = mysqli_init();

// 3. ATENÇÃO: Força o SSL apenas se estiver rodando na nuvem (Render/Aiven)
if (getenv('DB_HOST')) {
    mysqli_ssl_set($conexao, NULL, NULL, NULL, NULL, NULL);
}

// 4. Conecta de fato passando todas as variáveis, incluindo a porta
if (!@mysqli_real_connect($conexao, $host, $user, $password, $dbname, $port)) {
    die("Erro ao conectar no banco de dados: " . mysqli_connect_error());
}