<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de Senha - CiViTas</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); border: 1px solid #e2e8f0;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); padding: 32px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -0.5px;">CiViTas</h1>
                            <p style="color: #e0e7ff; margin: 6px 0 0; font-size: 14px;">Redefinição Segura de Acesso</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 36px 32px 28px;">
                            <h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 600; color: #0f172a;">Olá, {{ $nome }}</h2>
                            <p style="margin: 0 0 18px; font-size: 15px; line-height: 1.6; color: #475569;">
                                Recebemos uma solicitação para redefinir a senha da sua conta no <strong>CiViTas</strong>.
                            </p>
                            <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #475569;">
                                Para cadastrar uma nova senha, clique no botão abaixo. Este link é seguro e expira em <strong>{{ $expiresInMinutes }} minutos</strong>.
                            </p>

                            <!-- Button CTA -->
                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{{ $resetUrl }}" style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; padding: 12px 32px; border-radius: 8px; box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);">Redefinir Minha Senha</a>
                            </div>

                            <p style="margin: 20px 0 8px; font-size: 13px; color: #64748b;">
                                Caso o botão acima não funcione, copie e cole o link abaixo no seu navegador:
                            </p>
                            <p style="margin: 0 0 24px; font-size: 12px; color: #4f46e5; word-break: break-all; background-color: #f1f5f9; padding: 10px 14px; border-radius: 6px;">
                                {{ $resetUrl }}
                            </p>

                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 28px 0;" />

                            <div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 4px;">
                                <p style="margin: 0; font-size: 13px; color: #92400e; line-height: 1.5;">
                                    <strong>Atenção:</strong> Se você não solicitou a redefinição de senha, nenhuma ação é necessária. Sua senha atual permanecerá segura.
                                </p>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                © {{ date('Y') }} CiViTas. Mensagem automática enviada pelo sistema.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
