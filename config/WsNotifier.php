<?php
class WsNotifier {

    private const NOTIFY_URL    = 'http://127.0.0.1:3002/notify';
    private const NOTIFY_SECRET = '27DST0050x.'; // Igual que en server.js

    public static function send(string $event, array $payload = []): void {
        $body = json_encode([
            'secret'  => self::NOTIFY_SECRET,
            'event'   => $event,
            'payload' => $payload,
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n",
                'content' => $body,
                'timeout' => 0.5,
            ],
        ]);

        // @ suprime errores si Node.js no está disponible — no bloquea la respuesta
        @file_get_contents(self::NOTIFY_URL, false, $ctx);
    }
}