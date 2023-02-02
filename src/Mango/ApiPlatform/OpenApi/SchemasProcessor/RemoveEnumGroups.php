<?php

declare(strict_types=1);

namespace Manyou\Mango\ApiPlatform\OpenApi\SchemasProcessor;

use ArrayObject;
use Manyou\Mango\ApiPlatform\OpenApi\SchemasProcessor;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

use function explode;
use function strlen;
use function strpos;
use function substr;

#[AsTaggedItem(priority: -100)]
class RemoveEnumGroups implements SchemasProcessor
{
    public function __invoke(ArrayObject $schemas): ArrayObject
    {
        foreach ($schemas as $name => $schema) {
            if ($properties = $schema['properties'] ?? false) {
                foreach ($properties as $field => $property) {
                    if (
                        ($ref = $property['$ref'] ?? false)
                        && strpos($ref, '-')
                    ) {
                        $enum = explode('-', $ref, 2)[0];
                        $enum = substr($enum, strlen('#/components/schemas/'));
                        if (isset($schemas[$enum]['enum'])) {
                            $schemas[$name]['properties'][$field]['$ref'] = '#/components/schemas/' . $enum;
                        }
                    }
                }
            }
        }

        return $schemas;
    }
}
