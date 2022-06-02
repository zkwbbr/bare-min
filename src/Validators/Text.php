<?php

declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\Validator as v;
use App\Exceptions\BadRequestException;

final class Text extends ValidatorAbstract
{
    public function __construct(string $value, ?string $fieldName = null)
    {
        parent::__construct($value, $fieldName);

        if (!v::length(1, 65535, true)->validate($value))
            throw new BadRequestException($this->getFieldName() . ' must be between 1-65535 characters');
    }

}