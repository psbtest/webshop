<?php
declare(strict_types=1);

namespace App\Services;

class EmailService
{
    private string $smtp_host;
    private int $smtp_port;
    private string $smtp_username;
    private string $smtp_password;
    private string $smtp_encryption;
    private string $from_email;
    private string $from_name;

    public function __construct()
    {
        $this->smtp_host = $_ENV['MAIL_HOST'] ?? 'localhost';
        $this->smtp_port = (int) ($_ENV['MAIL_PORT'] ?? 587);
        $this->smtp_username = $_ENV['MAIL_USERNAME'] ?? '';
        $this->smtp_password = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->smtp_encryption = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
        $this->from_email = $_ENV['MAIL_FROM'] ?? 'noreply@webshop.com';
        $this->from_name = $_ENV['APP_NAME'] ?? 'Webshop';
    }

    /**
     * Send order confirmation email
     * 
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     */
    public function send_order_confirmation(array $order, array $items): bool
    {
        $subject = "Bevestiging van je bestelling #{$order['id']}";
        
        $html_body = $this->generate_order_confirmation_html($order, $items);
        $text_body = $this->generate_order_confirmation_text($order, $items);
        
        return $this->send_email(
            $order['customer_email'],
            $order['customer_name'],
            $subject,
            $html_body,
            $text_body
        );
    }

    /**
     * Send order status update email
     * 
     * @param array<string, mixed> $order
     */
    public function send_order_status_update(array $order): bool
    {
        $status_messages = [
            'pending' => 'In behandeling',
            'processing' => 'Wordt verwerkt',
            'shipped' => 'Verzonden',
            'delivered' => 'Bezorgd',
            'cancelled' => 'Geannuleerd',
        ];
        
        $status_text = $status_messages[$order['status']] ?? $order['status'];
        $subject = "Status update voor bestelling #{$order['id']} - {$status_text}";
        
        $html_body = $this->generate_status_update_html($order, $status_text);
        $text_body = $this->generate_status_update_text($order, $status_text);
        
        return $this->send_email(
            $order['customer_email'],
            $order['customer_name'],
            $subject,
            $html_body,
            $text_body
        );
    }

    /**
     * Send contact form email
     * 
     * @param array<string, mixed> $contact_data
     */
    public function send_contact_form(array $contact_data): bool
    {
        $subject = "Nieuw contactformulier bericht van {$contact_data['name']}";
        
        $html_body = $this->generate_contact_form_html($contact_data);
        $text_body = $this->generate_contact_form_text($contact_data);
        
        // Send to admin
        $admin_email = $_ENV['ADMIN_EMAIL'] ?? $this->from_email;
        
        return $this->send_email(
            $admin_email,
            'Admin',
            $subject,
            $html_body,
            $text_body,
            $contact_data['email'], // Reply-to
            $contact_data['name']
        );
    }

    /**
     * Send password reset email
     */
    public function send_password_reset(string $email, string $name, string $reset_token): bool
    {
        $subject = "Wachtwoord reset aanvraag";
        $reset_url = $_ENV['APP_URL'] . "/admin/reset-password?token={$reset_token}";
        
        $html_body = $this->generate_password_reset_html($name, $reset_url);
        $text_body = $this->generate_password_reset_text($name, $reset_url);
        
        return $this->send_email($email, $name, $subject, $html_body, $text_body);
    }

    /**
     * Send email using SMTP
     */
    private function send_email(
        string $to_email,
        string $to_name,
        string $subject,
        string $html_body,
        string $text_body,
        ?string $reply_to_email = null,
        ?string $reply_to_name = null
    ): bool {
        try {
            // Headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="boundary-' . uniqid() . '"',
                "From: {$this->from_name} <{$this->from_email}>",
                "To: {$to_name} <{$to_email}>",
            ];
            
            if ($reply_to_email) {
                $reply_to_name = $reply_to_name ?? $reply_to_email;
                $headers[] = "Reply-To: {$reply_to_name} <{$reply_to_email}>";
            }
            
            // Create multipart message
            $boundary = 'boundary-' . uniqid();
            $message = $this->create_multipart_message($text_body, $html_body, $boundary);
            
            // Update content-type header with correct boundary
            $headers[1] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
            
            // Send email
            if ($this->smtp_username && $this->smtp_password) {
                return $this->send_smtp_email($to_email, $subject, $message, $headers);
            } else {
                // Fallback to PHP mail() function
                return mail($to_email, $subject, $message, implode("\r\n", $headers));
            }
            
        } catch (\Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    private function send_smtp_email(string $to_email, string $subject, string $message, array $headers): bool
    {
        // Simple SMTP implementation
        // In production, consider using a library like PHPMailer or SwiftMailer
        
        $socket = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
        if (!$socket) {
            error_log("SMTP connection failed: {$errstr} ({$errno})");
            return false;
        }
        
        try {
            // SMTP conversation
            $this->smtp_command($socket, '', '220');
            $this->smtp_command($socket, "EHLO {$_SERVER['HTTP_HOST']}", '250');
            
            if ($this->smtp_encryption === 'tls') {
                $this->smtp_command($socket, 'STARTTLS', '220');
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->smtp_command($socket, "EHLO {$_SERVER['HTTP_HOST']}", '250');
            }
            
            $this->smtp_command($socket, 'AUTH LOGIN', '334');
            $this->smtp_command($socket, base64_encode($this->smtp_username), '334');
            $this->smtp_command($socket, base64_encode($this->smtp_password), '235');
            
            $this->smtp_command($socket, "MAIL FROM: <{$this->from_email}>", '250');
            $this->smtp_command($socket, "RCPT TO: <{$to_email}>", '250');
            $this->smtp_command($socket, 'DATA', '354');
            
            // Send headers and message
            fwrite($socket, "Subject: {$subject}\r\n");
            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n");
            fwrite($socket, $message . "\r\n.\r\n");
            
            $this->smtp_command($socket, '', '250');
            $this->smtp_command($socket, 'QUIT', '221');
            
            fclose($socket);
            return true;
            
        } catch (\Exception $e) {
            fclose($socket);
            error_log("SMTP error: " . $e->getMessage());
            return false;
        }
    }

    private function smtp_command($socket, string $command, string $expected_code): void
    {
        if ($command) {
            fwrite($socket, $command . "\r\n");
        }
        
        $response = fgets($socket, 512);
        $code = substr($response, 0, 3);
        
        if ($code !== $expected_code) {
            throw new \Exception("SMTP error: Expected {$expected_code}, got {$response}");
        }
    }

    private function create_multipart_message(string $text_body, string $html_body, string $boundary): string
    {
        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $text_body . "\r\n\r\n";
        
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $html_body . "\r\n\r\n";
        
        $message .= "--{$boundary}--\r\n";
        
        return $message;
    }

    /**
     * Generate order confirmation HTML
     * 
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     */
    private function generate_order_confirmation_html(array $order, array $items): string
    {
        $items_html = '';
        foreach ($items as $item) {
            $items_html .= "<tr>
                <td>{$item['product_name']}</td>
                <td>{$item['quantity']}</td>
                <td>€" . number_format($item['price'], 2) . "</td>
                <td>€" . number_format($item['price'] * $item['quantity'], 2) . "</td>
            </tr>";
        }
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>Bedankt voor je bestelling!</h2>
                
                <p>Beste {$order['customer_name']},</p>
                
                <p>We hebben je bestelling ontvangen en zullen deze zo snel mogelijk verwerken.</p>
                
                <h3>Bestelling #{$order['id']}</h3>
                <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                    <thead>
                        <tr style='background-color: #f3f4f6;'>
                            <th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Product</th>
                            <th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Aantal</th>
                            <th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Prijs</th>
                            <th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Totaal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$items_html}
                        <tr style='font-weight: bold; background-color: #f9fafb;'>
                            <td colspan='3' style='border: 1px solid #ddd; padding: 12px;'>Totaal</td>
                            <td style='border: 1px solid #ddd; padding: 12px;'>€" . number_format($order['total_amount'], 2) . "</td>
                        </tr>
                    </tbody>
                </table>
                
                <h3>Bezorgadres</h3>
                <p>" . nl2br(htmlspecialchars($order['customer_address'])) . "</p>
                
                <p>Je ontvangt een update zodra je bestelling wordt verzonden.</p>
                
                <p>Met vriendelijke groet,<br>Het {$this->from_name} team</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate order confirmation text
     * 
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     */
    private function generate_order_confirmation_text(array $order, array $items): string
    {
        $items_text = '';
        foreach ($items as $item) {
            $items_text .= "- {$item['product_name']} x{$item['quantity']} - €" . number_format($item['price'] * $item['quantity'], 2) . "\n";
        }
        
        return "Bedankt voor je bestelling!\n\n" .
               "Beste {$order['customer_name']},\n\n" .
               "We hebben je bestelling ontvangen en zullen deze zo snel mogelijk verwerken.\n\n" .
               "Bestelling #{$order['id']}\n" .
               "-------------------------\n" .
               $items_text .
               "-------------------------\n" .
               "Totaal: €" . number_format($order['total_amount'], 2) . "\n\n" .
               "Bezorgadres:\n" . $order['customer_address'] . "\n\n" .
               "Je ontvangt een update zodra je bestelling wordt verzonden.\n\n" .
               "Met vriendelijke groet,\n" .
               "Het {$this->from_name} team";
    }

    /**
     * Generate status update HTML
     * 
     * @param array<string, mixed> $order
     */
    private function generate_status_update_html(array $order, string $status_text): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>Status update voor je bestelling</h2>
                
                <p>Beste {$order['customer_name']},</p>
                
                <p>De status van je bestelling #{$order['id']} is gewijzigd naar: <strong>{$status_text}</strong></p>
                
                <p>Totaalbedrag: €" . number_format($order['total_amount'], 2) . "</p>
                
                <p>Met vriendelijke groet,<br>Het {$this->from_name} team</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate status update text
     * 
     * @param array<string, mixed> $order
     */
    private function generate_status_update_text(array $order, string $status_text): string
    {
        return "Status update voor je bestelling\n\n" .
               "Beste {$order['customer_name']},\n\n" .
               "De status van je bestelling #{$order['id']} is gewijzigd naar: {$status_text}\n\n" .
               "Totaalbedrag: €" . number_format($order['total_amount'], 2) . "\n\n" .
               "Met vriendelijke groet,\n" .
               "Het {$this->from_name} team";
    }

    /**
     * Generate contact form HTML
     * 
     * @param array<string, mixed> $contact_data
     */
    private function generate_contact_form_html(array $contact_data): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>Nieuw contactformulier bericht</h2>
                
                <p><strong>Naam:</strong> {$contact_data['name']}</p>
                <p><strong>Email:</strong> {$contact_data['email']}</p>
                <p><strong>Telefoon:</strong> " . ($contact_data['phone'] ?? 'Niet opgegeven') . "</p>
                <p><strong>Onderwerp:</strong> " . ($contact_data['subject'] ?? 'Algemene vraag') . "</p>
                
                <h3>Bericht:</h3>
                <div style='background-color: #f9fafb; padding: 15px; border-left: 4px solid #2563eb;'>
                    " . nl2br(htmlspecialchars($contact_data['message'])) . "
                </div>
                
                <p><small>Verzonden op: " . date('d-m-Y H:i:s') . "</small></p>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate contact form text
     * 
     * @param array<string, mixed> $contact_data
     */
    private function generate_contact_form_text(array $contact_data): string
    {
        return "Nieuw contactformulier bericht\n\n" .
               "Naam: {$contact_data['name']}\n" .
               "Email: {$contact_data['email']}\n" .
               "Telefoon: " . ($contact_data['phone'] ?? 'Niet opgegeven') . "\n" .
               "Onderwerp: " . ($contact_data['subject'] ?? 'Algemene vraag') . "\n\n" .
               "Bericht:\n" . $contact_data['message'] . "\n\n" .
               "Verzonden op: " . date('d-m-Y H:i:s');
    }

    /**
     * Generate password reset HTML
     */
    private function generate_password_reset_html(string $name, string $reset_url): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>Wachtwoord reset aanvraag</h2>
                
                <p>Beste {$name},</p>
                
                <p>Er is een wachtwoord reset aangevraagd voor jouw account. Klik op de onderstaande link om een nieuw wachtwoord in te stellen:</p>
                
                <p style='margin: 30px 0;'>
                    <a href='{$reset_url}' style='background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Wachtwoord resetten
                    </a>
                </p>
                
                <p>Deze link is 1 uur geldig. Als je geen wachtwoord reset hebt aangevraagd, kun je deze email negeren.</p>
                
                <p>Met vriendelijke groet,<br>Het {$this->from_name} team</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate password reset text
     */
    private function generate_password_reset_text(string $name, string $reset_url): string
    {
        return "Wachtwoord reset aanvraag\n\n" .
               "Beste {$name},\n\n" .
               "Er is een wachtwoord reset aangevraagd voor jouw account. Ga naar de onderstaande link om een nieuw wachtwoord in te stellen:\n\n" .
               $reset_url . "\n\n" .
               "Deze link is 1 uur geldig. Als je geen wachtwoord reset hebt aangevraagd, kun je deze email negeren.\n\n" .
               "Met vriendelijke groet,\n" .
               "Het {$this->from_name} team";
    }

    /**
     * Test email configuration
     */
    public function test_email_config(): bool
    {
        $test_email = $this->from_email;
        $subject = "Test email configuratie";
        $html_body = "<p>Test email om de configuratie te controleren.</p>";
        $text_body = "Test email om de configuratie te controleren.";
        
        return $this->send_email($test_email, 'Test', $subject, $html_body, $text_body);
    }
}
