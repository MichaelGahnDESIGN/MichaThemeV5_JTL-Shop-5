<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Update\Support;

// Fehler im Update-Ablauf. Meldungen enthalten nie Token, URLs mit Query oder Schlüssel
class UpdateException extends \RuntimeException
{
}
