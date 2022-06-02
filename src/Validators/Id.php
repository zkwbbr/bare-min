<?php

declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\Validator as v;
use App\Exceptions\BadRequestException;

final class Id extends ValidatorAbstract
{
    public function __construct(string $value, ?string $fieldName = null)
    {
        parent::__construct($value, $fieldName);

        if (!v::intVal()->validate($value))
            throw new BadRequestException($this->getFieldName() . ' is required / must be a number');
    }

}