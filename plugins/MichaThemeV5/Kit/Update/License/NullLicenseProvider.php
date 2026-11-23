<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Kit\Update\License;

// Free-Plugins: keine Lizenz
final class NullLicenseProvider implements LicenseProvider
{
    public function token(): ?string
    {
        return null;
    }
}
