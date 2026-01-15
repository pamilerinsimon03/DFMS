<?php

class MailService
{
    private static $fromEmail = 'noreply@dfms.codeweb.ng'; // Default sender
    private static $fromName = 'DFMS Notification';

    public static function send($to, $subject, $message)
    {
        if (is_array($to)) {
            $to = implode(',', $to);
        }

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=utf-8';
        $headers[] = 'From: ' . self::$fromName . ' <' . self::$fromEmail . '>';
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        $htmlBody = self::wrapMessage($subject, $message);

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
