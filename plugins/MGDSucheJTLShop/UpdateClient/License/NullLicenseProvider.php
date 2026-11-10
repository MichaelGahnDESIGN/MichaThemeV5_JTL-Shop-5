<?php

declare(strict_types=1);

namespace Plugin\MGDSucheJTLShop\UpdateClient\License;

// Free-Plugins: keine Lizenz
final class NullLicenseProvider implements LicenseProvider
{
    public function token(): ?string
    {
        return null;
    }
}
