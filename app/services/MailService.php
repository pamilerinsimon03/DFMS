<?php
require_once __DIR__ . '/../utils/SecurityUtils.php';

class MailService
{
    private static function getSmtpConfig()
    {
        $config = [
            'host'       => getenv('SMTP_HOST') ?: 'localhost',
            'port'       => (int)(getenv('SMTP_PORT') ?: 587),
            'username'   => getenv('SMTP_USER') ?: '',
            'password'   => getenv('SMTP_PASS') ?: '',
            'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
            'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'noreply@dfms.ui.edu.ng',
            'from_name'  => getenv('SMTP_FROM_NAME') ?: 'UI DFMS Portal'
        ];

        // Fallback to settings.json if .env missing
        $settingsFile = __DIR__ . '/../config/settings.json';
        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
            if (!empty($settings['smtp_host'])) $config['host'] = $settings['smtp_host'];
            if (!empty($settings['smtp_port'])) $config['port'] = (int)$settings['smtp_port'];
            if (!empty($settings['smtp_user'])) $config['username'] = $settings['smtp_user'];
            if (!empty($settings['smtp_pass'])) $config['password'] = $settings['smtp_pass'];
            if (!empty($settings['smtp_from_email'])) $config['from_email'] = $settings['smtp_from_email'];
            if (!empty($settings['smtp_from_name'])) $config['from_name'] = $settings['smtp_from_name'];
        }

        return $config;
    }

    public static function send($to, $subject, $message)
    {
        if (is_array($to)) {
            $to = implode(',', $to);
        }

        $config = self::getSmtpConfig();
        $htmlBody = self::wrapMessage($subject, $message);

        // PHPMailer Integration if loaded
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $config['host'];
                $mail->SMTPAuth   = !empty($config['username']);
                $mail->Username   = $config['username'];
                $mail->Password   = $config['password'];
                $mail->Port       = $config['port'];
                
                if ($config['encryption'] === 'ssl' || $config['port'] === 465) {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }

                $mail->setFrom($config['from_email'], $config['from_name']);
                
                $recipients = explode(',', $to);
                foreach ($recipients as $recipient) {
                    $mail->addAddress(trim($recipient));
                }

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;

                return $mail->send();
            } catch (Exception $e) {
                SecurityUtils::logSecurityEvent('mail_send_error', ['error' => $e->getMessage(), 'recipient' => $to]);
                return false;
            }
        }

        // Bypass mailing on local environment / CLI mode if unconfigured
        if (php_sapi_name() === 'cli' || (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost')) {
            SecurityUtils::logSecurityEvent('mail_simulated_local', ['to' => $to, 'subject' => $subject]);
            return true;
        }

        // Standard Mail Fallback
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
        $headers[] = 'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>';
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }

    private static function wrapMessage($title, $content)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #01017A; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 5px 5px; }
                .footer { font-size: 12px; color: #777; text-align: center; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>{$title}</h2>
                </div>
                <div class='content'>
                    {$content}
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " DFMS Portal. All rights reserved.
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
