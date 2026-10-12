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

    public static function queue($to, $subject, $message)
    {
        global $conn;
        if (is_array($to)) {
            $to = implode(',', $to);
        }

        if ($conn) {
            try {
                @$conn->query("CREATE TABLE IF NOT EXISTS email_queue (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    recipient VARCHAR(255) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
                    attempts INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                $stmt = $conn->prepare("INSERT INTO email_queue (recipient, subject, message) VALUES (?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("sss", $to, $subject, $message);
                    return $stmt->execute();
                }
            } catch (Throwable $e) {
                // Fallback to direct send if DB queue insertion fails
            }
        }

        return self::send($to, $subject, $message);
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

        // Bypass mailing on local environment / CLI mode ONLY if SMTP username is unconfigured
        if (empty($config['username']) && (php_sapi_name() === 'cli' || (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'], true)))) {
            preg_match('#https?://[^\s<"\']+#i', $message, $urlMatch);
            $extractedUrl = $urlMatch[0] ?? null;

            SecurityUtils::logSecurityEvent('mail_simulated_local', [
                'to' => $to,
                'subject' => $subject,
                'activation_url' => $extractedUrl,
                'body_text' => strip_tags($message)
            ]);
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
