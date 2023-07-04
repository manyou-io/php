<?php

declare(strict_types=1);

namespace Manyou\Mango\ApiPlatform;

use ApiPlatform\Serializer\AbstractItemNormalizer;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

/** Replaces ApiPlatform\Serializer\ItemNormalizer */
#[AsDecorator('api_platform.serializer.normalizer.item')]
class ItemNormalizer extends AbstractItemNormalizer
{
}
