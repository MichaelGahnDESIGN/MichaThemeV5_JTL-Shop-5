<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Support;

defined('PFAD_ROOT') || exit;

/** Weiterleitung nach einer Aktion (Neuladen soll sie nicht nochmal auslösen). Für Tests austauschbar. */
final class Redirect
{
    /** @var (callable(string):void)|null */
    public static $handler = null;

    /** @return bool true = Weiterleitung ausgelöst (Aufrufer macht dann nichts mehr) */
    public static function to(string $url): bool
    {
        if (self::$handler !== null) {
            (self::$handler)($url);

            return true;
        }
        if (headers_sent()) {
            return false;
        }
        header('Location: ' . $url, true, 303);
        exit;
    }
}
