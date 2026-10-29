<?php

declare(strict_types=1);

namespace Plugin\MichaThemeV5\Admin;

use MichaThemeV5\Plugin\PageRenderer;
use Plugin\MichaThemeV5\License\LicenseService;
use Plugin\MichaThemeV5\Support\{FileKv, Perms};

defined('PFAD_ROOT') || exit;

/** Tab "Lizenzen": Code einfügen, Status, Link zum Kauf. Der Code wird nie angezeigt (nur die letzten 6 Zeichen). */
final class LicensePage extends Page
{
    public const SHOP_URL = 'https://theme.michael-gahn.de';

    public function tabKey(): string
    {
        return 'license';
    }

    public function render(): string
    {
        $lic = LicenseService::forShop();
        $this->handle($lic);
        $html = PageRenderer::license($lic->describe(), [
            'formOpen' => $this->formOpen('license'),
            'formClose' => '</form>',
            'hasToken' => $lic->hasToken(),
            'masked' => $lic->masked(),
            'shopUrl' => self::SHOP_URL,
        ]);

        return $this->assets() . $this->notice() . $html;
    }

    private function handle(LicenseService $lic): void
    {
        if ($this->postedAction() !== 'license') {
            return;
        }
        if (($err = $this->guard(Perms::license())) !== null) {
            $this->back($err);

            return;
        }
        $action = isset($_POST['mt_license_action']) && is_string($_POST['mt_license_action']) ? $_POST['mt_license_action'] : '';
        if ($action === 'remove') {
            $lic->remove();
            $this->back('lic_removed');

            return;
        }
        if ($action === 'check') {
            $this->back($lic->recheck(new FileKv()) ? 'lic_checked' : 'lic_wait');

            return;
        }
        $token = isset($_POST['mt_license_token']) && is_string($_POST['mt_license_token']) ? trim($_POST['mt_license_token']) : '';
        if ($action === 'save' && $token !== '') {
            $this->back($lic->save($token) ? 'lic_saved' : 'lic_format');

            return;
        }
        $this->back('lic_format');
    }
}
