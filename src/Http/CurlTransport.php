<?php

declare(strict_types=1);

namespace Aeitron\Http;

use Aeitron\Exceptions\NetworkException;

class CurlTransport implements TransportInterface
{
    public function send(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        float $timeoutSeconds
    ): Response {
        $ch = curl_init();
        if ($ch === false) {
            throw new NetworkException('Failed to initialize cURL handle');
        }

        $formattedHeaders = [];
        foreach ($headers as $k => $v) {
            $formattedHeaders[] = $k . ': ' . $v;
        }

        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) ceil(max(0.1, $timeoutSeconds)));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(10, (int) ceil(max(0.1, $timeoutSeconds))));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // Header parsing callback
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, string $headerLine) use (&$responseHeaders): int {
            $len = strlen($headerLine);
            $parts = explode(':', $headerLine, 2);
            if (count($parts) === 2) {
                $responseHeaders[trim($parts[0])] = trim($parts[1]);
            }
            return $len;
        });

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new NetworkException(
                'cURL request failed (' . $curlErrno . '): ' . ($curlError !== '' ? $curlError : 'Connection failed'),
                $curlErrno
            );
        }

        return new Response($statusCode, $responseHeaders, (string) $rawResponse);
    }
}
