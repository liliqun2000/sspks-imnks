<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use SSpkS\Package\Package;
use SSpkS\Package\PackageFilter;

$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$valid = new ReflectionMethod(Package::class, 'isValidDsmVersion');
$valid->setAccessible(true);
foreach (['7.0-40000', '7.2-69057', '7.2.1-69057', '7.2.2-72806'] as $version) {
    $check($valid->invoke(null, $version), 'Valid DSM version rejected: ' . $version);
}
foreach (['7.2.1', '7.2.1.0-69057', '7.2.-69057', '7.2.1_69057',
    '7.2.2147483648-69057', '7.2.1-2147483648', '2147483648.2.1-69057'] as $version) {
    $check(!$valid->invoke(null, $version), 'Invalid DSM version accepted: ' . $version);
}
foreach ([
    ['7.2.1-69057', '7.2-69057', 0],
    ['7.2.1-69057', '7.2-64570', 1],
    ['7.2.1-69057', '7.2.2-72806', -1],
    ['7.2.1-69057', '7.3-86009', -1],
    ['7.2.1-69057', '7.1-99999', 1],
] as [$left, $right, $expected]) {
    $check(Package::compareDsmVersions($left, $right) === $expected, 'Wrong DSM ordering');
    $check(Package::compareDsmVersions($right, $left) === -$expected, 'Wrong reverse DSM ordering');
}
$package = (new ReflectionClass(Package::class))->newInstanceWithoutConstructor();
$package->version = '1.0-1';
$package->os_min_ver = '7.2.1-69057';
$package->os_max_ver = '7.2.2-72806';
$filter = (new ReflectionClass(PackageFilter::class))->newInstanceWithoutConstructor();
$matching = new ReflectionMethod(PackageFilter::class, 'isMatchingOsVersion');
$matching->setAccessible(true);
foreach (['7.2-64570' => false, '7.2-69057' => true, '7.2.1-69057' => true,
    '7.2-72806' => true, '7.2-72807' => false, '7.3-86009' => false] as $version => $expected) {
    $filter->setOsVersionFilter($version);
    $check($matching->invoke($filter, $package) === $expected, 'Wrong filter result: ' . $version);
    $check($package->isCompatibleToFirmware($version) === $expected, 'Wrong firmware result: ' . $version);
}
$package->os_max_ver = '';
$filter->setOsVersionFilter('7.2-72806');
$older = (new ReflectionClass(Package::class))->newInstanceWithoutConstructor();
$older->version = '1.0-1';
$older->os_min_ver = '7.2-64570';
$preferred = new ReflectionMethod(PackageFilter::class, 'isPreferredPackage');
$preferred->setAccessible(true);
$check($preferred->invoke($filter, $package, $older), 'Newer three-part DSM build must win');
$check(!$preferred->invoke($filter, $older, $package), 'Selection must not depend on input order');

echo "DSM version tests passed.\n";
