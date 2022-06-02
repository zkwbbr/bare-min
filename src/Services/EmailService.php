<?php

declare(strict_types=1);

namespace App\Services;

use App\Factories\EmailBuilderFactory;
use MetaRush\EmailFallback\Builder;

class EmailService
{
    private EmailBuilderFactory $emailBuilderFactory;

    public function __construct(EmailBuilderFactory $emailBuilderFactory)
    {
        $this->emailBuilderFactory = $emailBuilderFactory;
    }

    public function getBuilder(): Builder
    {
        return $this->emailBuilderFactory->getInstance();
    }

    public function send(Builder $builder): void
    {
        $builder
            ->build()
            ->sendEmailFallback();
    }

}