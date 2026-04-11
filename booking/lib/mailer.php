<?php
/**
 * Обёртка над PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function create(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mailCfg = $this->config['mail'];

        $mail->isSMTP();
        $mail->Host = $mailCfg['smtp_host'];
        $mail->Port = $mailCfg['smtp_port'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailCfg['smtp_user'];
        $mail->Password = $mailCfg['smtp_pass'];
        $mail->SMTPSecure = $mailCfg['smtp_secure'];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($mailCfg['from_email'], $mailCfg['from_name']);

        return $mail;
    }

    private function renderTemplate(string $template, array $data): string
    {
        extract($data);
        ob_start();
        require __DIR__ . '/../templates/emails/' . $template . '.php';
        return ob_get_clean();
    }

    private function logMailError(string $message): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logDir . '/mail.log', "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    }

    public function sendBookingConfirmation(array $booking, array $event): bool
    {
        try {
            $template = ($event['type'] === 'dinner')
                ? 'booking-confirmation-dinner'
                : 'booking-confirmation-tasting';

            $mail = $this->create();
            $mail->addAddress($booking['email'], $booking['name']);
            $mail->isHTML(true);
            $mail->Subject = 'Бронирование подтверждено — Радиус';
            $mail->Body = $this->renderTemplate($template, [
                'booking' => $booking,
                'event' => $event,
                'config' => $this->config,
            ]);

            $mail->send();
            return true;
        } catch (Exception $e) {
            $this->logMailError("Ошибка отправки подтверждения клиенту [{$booking['email']}]: " . $e->getMessage());
            return false;
        }
    }

    public function sendAdminNotification(array $booking, array $event): bool
    {
        try {
            $mail = $this->create();
            $mail->addAddress($this->config['mail']['admin_email']);
            $mail->isHTML(true);
            $mail->Subject = 'Новая бронь — ' . $event['title'] . ' ' . $event['event_date'];
            $mail->Body = $this->renderTemplate('admin-notification', [
                'booking' => $booking,
                'event' => $event,
                'config' => $this->config,
            ]);

            $mail->send();
            return true;
        } catch (Exception $e) {
            $this->logMailError("Ошибка отправки уведомления админу: " . $e->getMessage());
            return false;
        }
    }
}
