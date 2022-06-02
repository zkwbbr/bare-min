<?php

declare(strict_types=1);

namespace App\Validators;

abstract class ValidatorAbstract
{
    private string $value;
    private ?string $fieldName;

    public function __construct(string $value, ?string $fieldName = null)
    {
        $this->value = $value;
        $this->fieldName = $fieldName;
    }

    public function getFieldName(): ?string
    {
        $className = \get_class($this);
        $className = (string) \strrchr($className, '\\');
        $className = \trim($className, '\\');
        // credit: https://stackoverflow.com/questions/1089613/
        $defaultFieldName = \preg_replace('/(?<!\ )[A-Z]/', ' $0', $className);

        $fieldName = $this->fieldName ?? $defaultFieldName;

        return $fieldName;
    }

    public function getAsString(): string
    {
        return $this->value;
    }

    public function getAsInt(): int
    {
        return (int) $this->value;
    }

}