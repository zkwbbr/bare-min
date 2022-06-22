<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\BadRequestException;

final class Md5 extends ValidatorAbstract
{
    public function __construct(string $value, ?string $fieldName = null)
    {
        parent::__construct($value, $fieldName);

        if (\strlen($value) != 32 || !\ctype_xdigit($value))
            throw new BadRequestException($this->getFieldName() . ' format is invalid');
    }

}