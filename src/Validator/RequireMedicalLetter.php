<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Custom constraint untuk memvalidasi apakah file pendukung diperlukan
 * untuk Cuti Sakit lebih dari 14 hari
 */
#[\Attribute]
class RequireMedicalLetter extends Constraint
{
    public string $message = 'File pendukung (surat dokter) wajib untuk cuti sakit lebih dari {{ days }} hari';
    public int $minimumDays = 14;

    public function __construct(array $options = null, string $message = null, int $minimumDays = null, array $groups = null, mixed $payload = null)
    {
        parent::__construct($options ?? [], $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->minimumDays = $minimumDays ?? $this->minimumDays;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}