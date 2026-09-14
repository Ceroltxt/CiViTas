<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao CiViTas</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" max-width="580" style="max-width: 580px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); border: 1px solid #e2e8f0;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); padding: 32px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -0.5px;">CiViTas</h1>
                            <p style="color: #e0e7ff; margin: 6px 0 0; font-size: 14px;">Gestão Integrada e Colaborativa</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 36px 32px 28px;">
                            <h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 600; color: #0f172a;">Olá, {{ $nome }}! 👋</h2>
                            <p style="margin: 0 0 18px; font-size: 15px; line-height: 1.6; color: #475569;">
                                Sua conta no <strong>CiViTas</strong> foi criada com sucesso! É um prazer ter você a bordo da nossa plataforma.
                            </p>

                            <!-- Profile Details Card -->
                            <div style="background-color: #f1f5f9; border-radius: 8px; padding: 18px 20px; margin: 24px 0;">
                                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 10px;">Seus dados de acesso</div>
                                <table style="width: 100%; font-size: 14px; color: #334155;">
                                    <tr>
                                        <td style="padding: 4px 0; color: #64748b; width: 120px;">E-mail:</td>
                                        <td style="padding: 4px 0; font-weight: 600;">{{ $funcionario->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 4px 0; color: #64748b;">Departamento:</td>
                                        <td style="padding: 4px 0; font-weight: 500;">{{ $departamento }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 4px 0; color: #64748b;">Cargo:</td>
                                        <td style="padding: 4px 0; font-weight: 500;">{{ $cargo }}</td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #475569;">
                                Já criamos sua primeira tarefa de boas-vindas no painel. Acesse a plataforma para explorar suas atribuições, prazos e colaborar com sua equipe.
                            </p>

                            <!-- Button CTA -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ $loginUrl }}" style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; padding: 12px 28px; border-radius: 8px; box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);">Acessar o CiViTas</a>
                            </div>

                            <p style="margin: 24px 0 0; font-size: 13px; color: #94a3b8; line-height: 1.5;">
                                Se você não realizou este cadastro, entre em contato imediatamente com o administrador da sua organização.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                © {{ date('Y') }} CiViTas. Todos os direitos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
