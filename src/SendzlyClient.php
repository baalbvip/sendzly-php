<?php

declare(strict_types=1);

namespace Sendzly;

/**
 * Cliente oficial de Sendzly para PHP 8+ (cero dependencias externas).
 * Compatible con PHP nativo, Laravel, Symfony, CodeIgniter y Yii.
 */
class SendzlyClient
{
    public const DEFAULT_BASE_URL = 'https://sendzly.com/api/v1';

    private string $mailserverKey;
    private string $campaignKey;
    private string $baseUrl;
    private int $timeoutSeconds;

    public function __construct(
        string $mailserverKey,
        string $campaignKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        int $timeoutSeconds = 15
    ) {
        $this->mailserverKey = trim($mailserverKey);
        $this->campaignKey = trim($campaignKey);
        $this->baseUrl = $this->normalizeBaseUrl($baseUrl);
        $this->timeoutSeconds = max(3, $timeoutSeconds);

        if ($this->mailserverKey === '' || $this->campaignKey === '') {
            throw new SendzlyException(
                'Debes proporcionar tanto mailserverKey como campaignKey para inicializar SendzlyClient.',
                400
            );
        }
    }

    /**
     * Envía un correo transaccional 1-a-1 (MT).
     *
     * @param array{
     *   to: string,
     *   subject: string,
     *   html?: string|null,
     *   text?: string|null,
     *   fromName?: string|null,
     *   replyTo?: string|null,
     *   variables?: array<string, mixed>
     * } $payload
     */
    public function send(array $payload): array
    {
        $to = trim((string) ($payload['to'] ?? $payload['email'] ?? ''));
        $subject = trim((string) ($payload['subject'] ?? $payload['affair'] ?? ''));

        if ($to === '' || $subject === '') {
            throw new SendzlyException('Los campos "to" y "subject" son obligatorios.', 400);
        }

        return $this->request('POST', '/mt/send', $payload);
    }

    /**
     * Envía un código OTP / verificación en segundos.
     */
    public function sendOtp(string $to, string $code, string $name = '', string $subject = 'Tu código de verificación'): array
    {
        $greeting = $name !== '' ? "Hola {$name}," : 'Hola,';
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        $html = "<div style=\"font-family:system-ui,-apple-system,sans-serif;max-width:520px;margin:0 auto;padding:28px;border:1px solid #e2e8f0;border-radius:16px;\">"
            . "<p style=\"color:#334155;font-size:15px;\">{$greeting}</p>"
            . "<p style=\"color:#475569;font-size:14px;\">Usa el siguiente código de verificación para continuar:</p>"
            . "<div style=\"margin:20px 0;padding:16px;text-align:center;background:#f8fafc;border-radius:12px;font-size:28px;font-weight:800;letter-spacing:6px;color:#4f46e5;\">{$safeCode}</div>"
            . "<p style=\"color:#94a3b8;font-size:12px;\">Si no solicitaste este código, puedes ignorar este correo.</p>"
            . "</div>";

        return $this->send([
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'variables' => ['name' => $name, 'code' => $code],
        ]);
    }

    /**
     * Lista los últimos envíos transaccionales de tu organización.
     */
    public function listEmails(int $limit = 25): array
    {
        return $this->request('GET', '/mt/emails?limit=' . max(1, min(100, $limit)));
    }

    /**
     * Consulta el estado de un correo específico por su UUID.
     */
    public function getEmail(string $emailId): array
    {
        return $this->request('GET', '/mt/emails/' . rawurlencode($emailId));
    }

    private function normalizeBaseUrl(string $rawUrl): string
    {
        $clean = rtrim(trim($rawUrl), '/');
        if (str_ends_with($clean, '/api/v1') || str_ends_with($clean, '/v1')) {
            return $clean;
        }
        if (str_ends_with($clean, '/api')) {
            return $clean . '/v1';
        }
        return $clean . '/api/v1';
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);

        if ($ch === false) {
            throw new SendzlyException('No se pudo inicializar cURL para conectar con Sendzly.', 500);
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: sendzly-php/1.0.0',
            'x-mailserver-key: ' . $this->mailserverKey,
            'x-campaign-key: ' . $this->campaignKey,
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        curl_setopt_array($ch, $options);
        $rawResponse = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new SendzlyException("Error de conexión con Sendzly: {$curlErr}", 0);
        }

        $decoded = json_decode($rawResponse, true);
        if (!is_array($decoded)) {
            $decoded = ['raw' => $rawResponse];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $message = (string) ($decoded['message'] ?? "Error HTTP {$httpCode} al despachar correo en Sendzly.");
            $code = isset($decoded['code']) ? (string) $decoded['code'] : null;
            throw new SendzlyException($message, $httpCode, $code, $decoded);
        }

        return $decoded;
    }
}
