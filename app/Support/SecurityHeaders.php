<?php

namespace App\Support;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

final class SecurityHeaders
{
    /**
     * Returns security headers to safely serve untrusted or user-provided content.
     *
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    public static function forUntrustedContent(array $headers = []): array
    {
        return array_merge([
            'Content-Security-Policy' => 'sandbox',
            'X-Content-Type-Options' => 'nosniff',
        ], $headers);
    }

    /**
     * Create a response with untrusted content security headers.
     *
     * @param  array<string, string>  $headers
     */
    public static function untrusted(mixed $content = '', int $status = 200, array $headers = []): Response
    {
        /** @var View|string|array<mixed>|null $content */
        return response($content, $status)->withHeaders(self::forUntrustedContent($headers));
    }
}
