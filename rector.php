<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    // Define the paths Rector should process
    $rectorConfig->paths([
        __DIR__ . '/admin',
        __DIR__ . '/bower_components',
        __DIR__ . '/includes',
        __DIR__ . '/tcpdf',
    ]);

    // Apply the set of rules needed to upgrade to a specific PHP version
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82, // Adjust to your desired PHP version
    ]);

    // Increase the timeout duration for child processes

};
