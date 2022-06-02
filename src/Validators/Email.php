<?php

declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\Validator as v;
use App\Exceptions\BadRequestException;

final class Email extends ValidatorAbstract
{
    public function __construct(string $value, ?string $fieldName = null)
    {
        parent::__construct($value, $fieldName);

        if (!v::email()->validate($value))
            throw new BadRequestException($this->getFieldName() . ' must be a valid email');
    }

}