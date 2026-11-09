<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Update\License;

// Lizenz-Adapter. Liefert das Lizenz-Token nur für den Authorization-Header. Nie loggen, nie in URLs
interface LicenseProvider
{
    public function token(): ?string;
}
