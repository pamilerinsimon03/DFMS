<?php

class MailService
{
    private static $fromEmail = 'noreply@dfms.codeweb.ng'; // Default sender
    private static $fromName = 'DFMS Notification';

    /**
     * Send an email
     *
     * @param string|array $to Recipient email(s)
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @return bool True if accepted for delivery, false otherwise
     */
    public static function send($to, $subject, $message)
    {
        // Handle array of recipients
        if (is_array($to)) {
            $to = implode(',', $to);
        }

        // Headers
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
        $headers[] = 'From: ' . self::$fromName . ' <' . self::$fromEmail . '>';
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        // Wrap message in a basic HTML template
        $htmlBody = self::wrapMessage($subject, $message);

        // Send
        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
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
                    &copy; " . date('Y') . " DFMS. All rights reserved.
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
