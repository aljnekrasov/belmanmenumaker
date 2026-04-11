<?php
/**
 * Интерфейс платёжного провайдера
 */

interface PaymentProvider
{
    /**
     * Создаёт платёж и возвращает данные для редиректа клиента.
     * @return array{payment_id: string, payment_url: string}
     */
    public function createPayment(array $booking): array;

    /**
     * Проверяет подпись и валидность входящего webhook.
     */
    public function verifyWebhook(string $rawBody, array $headers): bool;

    /**
     * Парсит webhook и возвращает информацию о платеже.
     * @return array{payment_id: string, status: string, booking_token: ?string}
     */
    public function parseWebhook(string $rawBody): array;
}
