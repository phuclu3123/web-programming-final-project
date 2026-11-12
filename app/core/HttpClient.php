<?php
declare(strict_types=1);
namespace App\Core;

/** HTTPS only; fixed provider endpoints are selected by services, never by input. */
final class HttpClient implements \App\Contracts\HttpClientInterface
{
    public function __construct(private int $maxResponseBytes=1048576) {}
    public function json(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        if (!extension_loaded('curl') || parse_url($url, PHP_URL_SCHEME) !== 'https') throw new \RuntimeException('HTTPS transport unavailable');
        $handle = curl_init($url);
        $response = '';
        $options = [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>5,
            CURLOPT_TIMEOUT=>15, CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS, CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_CUSTOMREQUEST=>$method,
            CURLOPT_HTTPHEADER=>array_merge(['Accept: application/json','User-Agent: CouponHub/1.0'], $headers)];
        // Stop while receiving, rather than allocating an unbounded provider response first.
        $limit=max(1024,min(8388608,$this->maxResponseBytes));
        $options[CURLOPT_WRITEFUNCTION] = static function ($handle, string $chunk) use (&$response,$limit): int {
            if (strlen($response) + strlen($chunk) > $limit) return 0;
            $response .= $chunk;
            return strlen($chunk);
        };
        if ($body !== null) $options[CURLOPT_POSTFIELDS] = $body;
        // Use php.ini curl.cainfo / openssl.cafile when a Windows CA bundle is needed.
        curl_setopt_array($handle, $options);
        try {
            $success = curl_exec($handle);
            $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            if ($success === false || $status < 200 || $status >= 300) throw new \RuntimeException('Provider request failed (HTTP '.$status.')');
            $data = json_decode($response, true, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            if (!is_array($data)) throw new \RuntimeException('Invalid provider response');
            return $data;
        } finally { curl_close($handle); }
    }
}
