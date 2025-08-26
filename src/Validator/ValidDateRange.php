<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute]
class ValidDateRange extends Constraint
{
    public string $message = 'Tanggal selesai harus sama atau setelah tanggal mulai';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}