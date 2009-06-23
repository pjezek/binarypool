#!/usr/bin/env php
<?php
ini_set("display_errors", "stderr");

/**
 * Deletes expired assets. This script is meant to run
 * daily. The user as whom this script is run must have
 * full write permissions on the Binary Pool file system so
 * it can delete files.
 */

require_once(dirname(__FILE__) . '/../inc/api/init.php');
api_init::start();

// Command line. Can contain a bucket (arg1) and a date (arg2) to process.
$limitBucket = null;
$limitDate = null;

if (count($argv) > 1) {
    $limitBucket = $argv[1];
}
if (count($argv) > 2) {
    $limitDate = $argv[2];
}

function cleanSymlinks() {
    // Do symlink cleanup
    printf("[%10s] Cleaning up symlinks.\n", 'FINAL');
    $cmd = binarypool_config::getUtilityPath('symlinks');
    system("$cmd -cdrs " . binarypool_config::getRoot() . "*/created");
    system("$cmd -cdrs " . binarypool_config::getRoot() . "*/expiry");
    system("$cmd -cdrsv " . binarypool_config::getRoot() . "*/downloaded |grep '/dev/null' |xargs -0 rm");
}

cleanSymlinks();

// Walk through each bucket
$buckets = binarypool_config::getBuckets();
if (!is_null($limitBucket)) {
    $buckets = array($limitBucket => $buckets[$limitBucket]);
}

foreach (array_keys($buckets) as $bucket) {
    $storage = new binarypool_storage($bucket);

    printf("[%10s] Fetching list of expired binaries.\n", $bucket);
    $expired = binarypool_browser::getExpired($bucket, $limitDate);
    $deleted = 0;

    printf("[%10s] %d candidates.\n", $bucket, count($expired));
    foreach ($expired as $asset) {
        try {
            $retval = binarypool_expiry::isExpired($bucket, $asset);
            if ($retval === true) {
                printf("[%10s] Deleting %s\n", $bucket, $asset);
                $storage->delete($asset);
                $deleted += 1;
            } else {
                // Update the expiry symlink
                $oldAssetObj = $storage->getAssetObject($asset);
                $storage->saveAsset($retval, $asset);
                binarypool_views::updated($bucket, $asset, $oldAssetObj);
            }
        } catch (binarypool_exception $e) {
            if ($e->getCode() == 112) {
                printf("[%10s] Asset does not exist %s\n", $bucket, $asset);
            } else {
                printf("[%10s] An error occured when working on asset %s: %s\n", $bucket, $asset, $e->getMessage());
            }
        } catch (Exception $e) {
            printf("[%10s] An error occured when working on asset %s: %s\n", $bucket, $asset, $e->getMessage());
        }
    }

    printf("[%10s] Removed %d assets.\n", $bucket, $deleted);
    printf("[%10s] Done.\n", $bucket, $expired);
}

cleanSymlinks();
?>
