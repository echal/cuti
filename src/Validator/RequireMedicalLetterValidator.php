<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\PengajuanCuti;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator untuk RequireMedicalLetter constraint
 */
class RequireMedicalLetterValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof RequireMedicalLetter) {
            throw new UnexpectedTypeException($constraint, RequireMedicalLetter::class);
        }

        if (!$value instanceof PengajuanCuti) {
            throw new UnexpectedTypeException($value, PengajuanCuti::class);
        }

        // Skip validation if pengajuan is not complete
        if (!$value->getJenisCuti() || !$value->getLamaCuti()) {
            return;
        }

        // Check if this is sick leave (Cuti Sakit)
        $jenisCuti = $value->getJenisCuti();
        if (!$this->isSickLeave($jenisCuti->getKode())) {
            return;
        }

        // Check if duration exceeds minimum days
        if ($value->getLamaCuti() <= $constraint->minimumDays) {
            return;
        }

        // Check if medical letter file is provided
        if (!$this->hasMedicalLetter($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ days }}', (string) $constraint->minimumDays)
                ->atPath('filePendukung')
                ->addViolation();
        }
    }

    /**
     * Check if jenis cuti is sick leave
     */
    private function isSickLeave(string $kode): bool
    {
        // Adjust this according to your jenis cuti codes
        $sickLeaveCodes = ['CS', 'CUTI_SAKIT', 'SICK_LEAVE'];
        
        return in_array(strtoupper($kode), $sickLeaveCodes, true);
    }

    /**
     * Check if medical letter file is provided
     */
    private function hasMedicalLetter(PengajuanCuti $pengajuan): bool
    {
        // Check if file pendukung is uploaded
        $filePendukung = $pengajuan->getFilePendukung();
        
        if (!$filePendukung || empty($filePendukung)) {
            return false;
        }

        // Additional validation: check if file exists and is valid format
        // This can be expanded based on your file upload implementation
        return true;
    }
}