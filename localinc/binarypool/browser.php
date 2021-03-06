<?php
require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/asset.php');

/**
 * Read-only access to the Binary Pool file system.
 */
class binarypool_browser {
    /**
     * Returns all items which expired in the last seven days
     * including today.
     *
     * All the returned file names are relative paths.
     */
    public static function getExpired($bucket, $limitDate = null) {
        $storage = new binarypool_storage($bucket);

        if ($limitDate !== null) {
            // Get all asset files which expired in those days
            return $storage->listDir('expiry/' . $limitDate);
        } else {
            $files = array();
            for ($day = 0; $day < 100; $day++) {
                // Date directory for given day
                $dateDir = date('Y/m/d', time() - ($day * 24 * 60 * 60));

                // Get all asset files which expired in those days
                $retval = $storage->listDir('expiry/' . $dateDir);
                $files = array_merge($files, $retval);
            }
            return $files;
        }
    }
}
