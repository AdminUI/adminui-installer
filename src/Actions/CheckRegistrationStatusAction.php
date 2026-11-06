<?php

namespace AdminUI\AdminUIInstaller\Actions;

use AdminUI\AdminUI\Models\Admin;
use AdminUI\AdminUIInstaller\Facades\Install;

/**
 * Decides whether the first admin account may be registered right now. Used by both the registration page and the
 * endpoint that creates the account, so the two can never disagree.
 */
class CheckRegistrationStatusAction
{
    public const NOT_INSTALLED = 'not-installed';

    public const ALREADY_REGISTERED = 'already-registered';

    public const OPEN = 'open';

    public function execute(): string
    {
        if (! Install::isInstalled() || ! Install::isMigrated()) {
            return self::NOT_INSTALLED;
        }

        $admin = app(Admin::class);

        return $admin->newQuery()->exists() ? self::ALREADY_REGISTERED : self::OPEN;
    }
}
