<?php

declare(strict_types=1);

namespace Manyou\Mango\Doctrine\Type;

use BackedEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use LogicException;

use function array_map;
use function is_a;
use function sprintf;

trait BackedTinyIntEnum
{
    use ArrayTinyIntEnum;

    private string $enumClass;

    abstract private function getEnumClass(): string;

    public function __construct()
    {
        if (! is_a($this->enumClass = $this->getEnumClass(), BackedEnum::class, true)) {
            throw new LogicException(sprintf('Enum class "%s" is not a BackedEnum.', self::class));
        }
    }

    private function getEnums(): array
    {
        return array_map(static fn (BackedEnum $enum) => $enum->value, $this->enumClass::cases());
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?BackedEnum
    {
        return $value === null ? null : $this->enumClass::from($value);
    }

    public function valueToId($value): ?int
    {
        if (! is_a($value, $this->enumClass)) {
            return null;
        }

        return $this->getIdMap()[$value->value] ?? null;
    }

    public function idToValue(int $id): ?BackedEnum
    {
        $value = $this->getValueMap()[$id] ?? null;

        return $value === null ? null : $this->enumClass::from($value);
    }
}
