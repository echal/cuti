<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\PengajuanCuti;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidDateRangeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidDateRange) {
            throw new UnexpectedTypeException($constraint, ValidDateRange::class);
        }

        if (!$value instanceof PengajuanCuti) {
            throw new UnexpectedValueException($value, PengajuanCuti::class);
        }

        $tanggalMulai = $value->getTanggalMulai();
        $tanggalSelesai = $value->getTanggalSelesai();

        // Skip validation if either date is null
        if (!$tanggalMulai || !$tanggalSelesai) {
            return;
        }

        // Convert to DateTime for comparison if needed
        if (!$tanggalMulai instanceof \DateTime) {
            $tanggalMulai = new \DateTime($tanggalMulai->format('Y-m-d'));
        }

        if (!$tanggalSelesai instanceof \DateTime) {
            $tanggalSelesai = new \DateTime($tanggalSelesai->format('Y-m-d'));
        }

        // Validate that end date >= start date
        if ($tanggalSelesai < $tanggalMulai) {
            $this->context->buildViolation($constraint->message)
                ->atPath('tanggalSelesai')
                ->addViolation();
        }
    }
}