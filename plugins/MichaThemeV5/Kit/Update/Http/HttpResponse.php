<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Kit\Update\Http;

final class HttpResponse
{
    public function __construct(public readonly int $status, public readonly string $body = '')
    {
    }
}
