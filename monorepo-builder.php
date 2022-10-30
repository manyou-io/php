<?php

declare(strict_types=1);

use Symplify\MonorepoBuilder\Config\MBConfig;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJsonSection;

return static function (MBConfig $mbConfig): void {
    $mbConfig->packageDirectories([
        __DIR__ . '/src',
    ]);
    $mbConfig->defaultBranch('main');
    $mbConfig->dataToAppend([
        ComposerJsonSection::REQUIRE_DEV => [
            'phpunit/phpunit' => '^9.5',
        ],
    ]);
};
