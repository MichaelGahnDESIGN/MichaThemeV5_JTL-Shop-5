<?php

declare(strict_types=1);

// JTL Attrappen nur für Tests, läuft ohne Shop

namespace JTL\Events {
    final class Dispatcher
    {
        /** @var list<string> */
        public array $events = [];

        public function listen(string $event, callable $listener): void
        {
            $this->events[] = $event;
        }
    }
}

namespace JTL\Plugin {
    use JTL\Events\Dispatcher;

    abstract class Bootstrapper
    {
        public static ?object $plugin = null;
        public static bool $pluginThrows = false;

        public function boot(Dispatcher $dispatcher): void
        {
        }

        public function getPlugin(): object
        {
            if (self::$pluginThrows || self::$plugin === null) {
                throw new \RuntimeException('Plugin nicht verfügbar');
            }

            return self::$plugin;
        }

        public function getDB(): object
        {
            return new \stdClass();
        }
    }
}

namespace JTL {
    final class Shop
    {
        /** @var list<string> */
        public static array $log = [];
        public static bool $logThrows = false;

        public static function isFrontend(): bool
        {
            return true;
        }

        public static function getLanguageID(): int
        {
            return 1;
        }

        public static function Container(): object
        {
            if (self::$logThrows) {
                throw new \RuntimeException('kein Container');
            }

            return new class {
                public function getLogService(): object
                {
                    return new class {
                        public function warning(string $message): void
                        {
                            \JTL\Shop::$log[] = $message;
                        }
                    };
                }
            };
        }
    }
}

namespace {
    if (!defined('HOOK_SMARTY_OUTPUTFILTER')) {
        define('HOOK_SMARTY_OUTPUTFILTER', 140);
    }
}
