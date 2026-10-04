<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use SSpkS\Config;
use SSpkS\Package\Package;

$root = dirname(__DIR__);
$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sspks-tar-test-' . bin2hex(random_bytes(8));
if (!mkdir($temporaryRoot, 0700)) {
    throw new RuntimeException('Cannot create ordinary SPK test directory');
}
$source = $temporaryRoot . DIRECTORY_SEPARATOR . 'source';
mkdir($source, 0700);
$info = <<<'INFO'
package="OrdinarySmokeTest"
version="1.0.0-1"
displayname="Ordinary Smoke Test"
arch="x86_64"
os_min_ver="7.2.1-69057"
os_max_ver="7.2.2-72806"
description="Ordinary TAR SPK test"
maintainer="SSpkS"
INFO;
file_put_contents($source . DIRECTORY_SEPARATOR . 'INFO', $info . "\n");
copy($root . '/themes/material/images/default_package_icon_72.png', $source . DIRECTORY_SEPARATOR . 'PACKAGE_ICON.PNG');
copy($root . '/themes/material/images/default_package_icon_120.png', $source . DIRECTORY_SEPARATOR . 'PACKAGE_ICON_256.PNG');

$tar = $temporaryRoot . DIRECTORY_SEPARATOR . 'ordinary.tar';
$spk = $temporaryRoot . DIRECTORY_SEPARATOR . 'ordinary.spk';
$archive = new PharData($tar);
$archive->addFile($source . DIRECTORY_SEPARATOR . 'INFO', 'INFO');
$archive->addFile($source . DIRECTORY_SEPARATOR . 'PACKAGE_ICON.PNG', 'PACKAGE_ICON.PNG');
$archive->addFile($source . DIRECTORY_SEPARATOR . 'PACKAGE_ICON_256.PNG', 'PACKAGE_ICON_256.PNG');
unset($archive);
rename($tar, $spk);

$config = Config::getInstance($root, 'conf/sspks.yaml');
$cachePrefix = rtrim($config->paths['cache'], '/\\') . DIRECTORY_SEPARATOR . 'ordinary';
try {
    $package = new Package($config, $spk);
    $metadata = $package->getMetadata();
    if ($metadata['package'] !== 'OrdinarySmokeTest' || count($metadata['thumbnail']) !== 2) {
        throw new RuntimeException('Ordinary SPK regression test failed');
    }
    echo "Ordinary SPK tests passed.\n";
} finally {
    foreach (glob($cachePrefix . '*') ?: [] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    foreach (['INFO', 'PACKAGE_ICON.PNG', 'PACKAGE_ICON_256.PNG'] as $file) {
        @unlink($source . DIRECTORY_SEPARATOR . $file);
    }
    @unlink($spk);
    @rmdir($source);
    @rmdir($temporaryRoot);
}
