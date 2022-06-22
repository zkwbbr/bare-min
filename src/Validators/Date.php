<?php

declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\Validator as v;
use App\Exceptions\BadRequestException;

final class Date extends ValidatorAbstract
{
    public function __construct(string $value, ?string $fieldName = null)
    {
        parent::__construct($value, $fieldName);

        if (!v::dateTime('Y-m-d')->validate($value))
            throw new BadRequestException($this->getFieldName() . ' format is invalid');
    }

}