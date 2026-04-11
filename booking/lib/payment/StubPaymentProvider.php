<?php
/**
 * Заглушка платёжного провайдера для разработки
 */

require_once __DIR__ . '/PaymentProvider.php';

class StubPaymentProvider implements PaymentProvider
{
    private string $baseUrl;

    public function __construct(string $bookingBaseUrl)
    {
        $this->baseUrl = $bookingBaseUrl;
    }

    public function createPayment(array $booking): array
    {
        $paymentId = 'stub_' . uniqid();

        return [
            'payment_id' => $paymentId,
            'payment_url' => $this->baseUrl . '/api/confirm-payment.php?token=' . $booking['booking_token'],
        ];
    }

    public function verifyWebhook(string $rawBody, array $headers): bool
    {
        return true;
    }

    public function parseWebhook(string $rawBody): array
    {
        $data = json_decode($rawBody, true) ?? [];

        return [
            'payment_id' => $data['payment_id'] ?? '',
            'status' => $data['status'] ?? 'succeeded',
            'booking_token' => $data['booking_token'] ?? null,
        ];
    }
}
