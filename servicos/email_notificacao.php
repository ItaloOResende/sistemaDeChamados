<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Importação manual das classes do PHPMailer
require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';

// Importa as credenciais protegidas pelo .gitignore
require_once __DIR__ . '/config_email.php';

function enviarNotificacaoNovoChamado($dadosChamado, $listaDestinatarios) {
    if (empty($listaDestinatarios)) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Configurações do Servidor SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = 0;

        // Remetente
        $mail->setFrom(SMTP_USER, 'HelpDesk TI - Notificações');

        // Destinatários em cópia oculta
        foreach ($listaDestinatarios as $dest) {
            if (!empty($dest['email'])) {
                $mail->addBCC($dest['email'], $dest['nome']);
            }
        }

        // Template do E-mail em HTML
        $mail->isHTML(true);
        $mail->Subject = "🚨 [Novo Chamado #{$dadosChamado['id']}] - {$dadosChamado['empresa']} ({$dadosChamado['prioridade']})";

        $corPrioridade = match($dadosChamado['prioridade']) {
            'Alta', 'Urgente' => '#dc3545',
            'Média'           => '#ffc107',
            default           => '#28a745'
        };

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 25px; margin: 0;'>
            <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>
                <div style='background-color: #1a1a1a; padding: 20px; text-align: center; color: #ffffff;'>
                    <h2 style='margin: 0; font-size: 20px;'>📋 Novo Chamado Aberto</h2>
                </div>
                <div style='padding: 25px; color: #333333; line-height: 1.6;'>
                    <p style='margin-top: 0;'>Um novo chamado foi registrado no sistema:</p>
                    
                    <table style='width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px;'>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold; width: 35%;'>ID:</td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eee;'>#{$dadosChamado['id']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;'>Empresa:</td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eee;'>{$dadosChamado['empresa']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;'>Solicitante:</td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eee;'>{$dadosChamado['usuario']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;'>Prioridade:</td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eee;'>
                                <span style='background: {$corPrioridade}; color: #fff; padding: 3px 8px; border-radius: 3px; font-weight: bold; font-size: 12px;'>
                                    {$dadosChamado['prioridade']}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eee; font-weight: bold;'>Origem:</td>
                            <td style='padding: 8px 0; border-bottom: 1px solid #eee;'>{$dadosChamado['origem']}</td>
                        </tr>
                    </table>

                    <div style='background-color: #f9f9f9; border-left: 4px solid #007bff; padding: 12px; margin-bottom: 10px;'>
                        <strong style='display: block; margin-bottom: 5px;'>Descrição:</strong>
                        <p style='margin: 0; white-space: pre-line; color: #555;'>{$dadosChamado['descricao']}</p>
                    </div>
                </div>
            </div>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
        return false;
    }
}

// EXECUÇÃO EM SEGUNDO PLANO (CLI)
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    require_once __DIR__ . '/../tabelas/conexao.php';
    
    $id_chamado = (int)$argv[1];
    if ($id_chamado <= 0) {
        echo "ID de chamado inválido.\n";
        exit();
    }

    // 1. Busca os dados do chamado recém-aberto
    $sql_c = "SELECT c.id_chamado, c.prioridade, c.origem, c.descricao_solicitacao, 
                     cl.nome_empresa, u.nome AS nome_usuario
              FROM chamados c
              LEFT JOIN clientes cl ON cl.id_cliente = c.id_cliente
              LEFT JOIN usuarios u ON u.id = c.id_usuario
              WHERE c.id_chamado = ?";
    $stmt_c = $conexao->prepare($sql_c);
    $stmt_c->bind_param("i", $id_chamado);
    $stmt_c->execute();
    $chamado = $stmt_c->get_result()->fetch_assoc();
    $stmt_c->close();

    if ($chamado) {
        // 2. Busca destinatários técnicos e administradores
        $sql_dest = "SELECT nome, email FROM usuarios 
                     WHERE (LOWER(perfil) = 'admin' OR LOWER(perfil) = 'tecnico') 
                       AND (status = 'Ativo' OR status = 'ativo' OR status = '1' OR status = 1)
                       AND email IS NOT NULL AND email != ''";
        $res_dest = $conexao->query($sql_dest);
        $destinatarios = [];
        if ($res_dest) {
            while ($dest = $res_dest->fetch_assoc()) {
                $destinatarios[] = $dest;
            }
        }

        if (empty($destinatarios) && defined('SMTP_USER')) {
            $destinatarios[] = ['nome' => 'Equipe de Suporte TI', 'email' => SMTP_USER];
        }

        $dadosEnvio = [
            'id'         => $chamado['id_chamado'],
            'empresa'    => $chamado['nome_empresa'] ?? 'Empresa não identificada',
            'usuario'    => $chamado['nome_usuario'] ?? 'Solicitante não identificado',
            'prioridade' => $chamado['prioridade'],
            'origem'     => $chamado['origem'],
            'descricao'  => $chamado['descricao_solicitacao']
        ];

        echo "Iniciando comunicação com o servidor SMTP...\n";
        if (enviarNotificacaoNovoChamado($dadosEnvio, $destinatarios)) {
            echo "\n✅ [SUCESSO] E-mail enviado com sucesso!\n";
        } else {
            echo "\n❌ [FALHA] Falha no envio do e-mail.\n";
        }
    } else {
        echo "Chamado #{$id_chamado} não encontrado no banco de dados.\n";
    }
    $conexao->close();
}