<?php

namespace AdminUI\AdminUIInstaller\Actions;

/**
 * AdminUI caches objects (e.g. the licence), but Laravel 13 defaults `cache.serializable_classes` to `false`,
 * which turns cached objects into incomplete classes. This sets it to `true` in the application's config/cache.php.
 *
 * Applications without the option (Laravel 12 and earlier) already allow all classes, so nothing is changed there.
 */
class EnableCacheSerializationAction
{
    /**
     * @return bool Whether config/cache.php was changed
     */
    public function execute(): bool
    {
        $path = base_path('config/cache.php');

        if (! file_exists($path)) {
            return false;
        }

        $contents = file_get_contents($path);
        $updated = preg_replace(
            "/(['\"]serializable_classes['\"]\s*=>\s*)false\b/",
            '${1}true',
            $contents,
            1,
            $count
        );

        if ($count === 0) {
            return false;
        }

        file_put_contents($path, $updated);

        return true;
    }
}
