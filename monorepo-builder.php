<?php

declare(strict_types=1);

use Symplify\MonorepoBuilder\Config\MBConfig;

return static function (MBConfig $mbConfig): void {
    $mbConfig->packageDirectories([
        __DIR__ . '/Aria2',
        __DIR__ . '/BingHomepage',
        __DIR__ . '/LeanStorage',
        __DIR__ . '/Mango',
        __DIR__ . '/PromiseHttpClient',
        __DIR__ . '/RemoveDataCollectorBundle',
        __DIR__ . '/WorkermanSymfonyRuntime',
    ]);
    $mbConfig->defaultBranch('main');
};
