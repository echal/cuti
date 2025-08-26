<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\JenisCuti;
use App\Model\ValidationResult;
use App\Repository\PengajuanCutiRepository;

/**
 * Service untuk validasi kebijakan cuti sesuai peraturan
 */
class CutiPolicy
{
    public function __construct(
        private readonly CutiCalculator $cutiCalculator,
        private readonly PengajuanCutiRepository $pengajuanCutiRepository
    ) {
    }

    /**
     * Validasi apakah user dapat mengajukan cuti tertentu
     */
    public function dapatAjukan(
        User $user,
        JenisCuti $jenis,
        \DateTimeInterface $mulai,
        \DateTimeInterface $selesai
    ): ValidationResult {
        $errors = [];

        // 1. Validasi status kepegawaian vs jenis cuti
        if (!$this->cekKompatibilitasStatusKepegawaian($user, $jenis)) {
            $errors[] = sprintf(
                'Jenis cuti %s tidak tersedia untuk %s',
                $jenis->getNama(),
                $user->getStatusKepegawaian()
            );
        }

        // 2. Validasi durasi
        $lamaCuti = $this->cutiCalculator->hitungLamaCuti($mulai, $selesai);
        $validasiDurasi = $this->validasiDurasi($jenis, $lamaCuti);
        if (!$validasiDurasi->isValid()) {
            $errors = array_merge($errors, $validasiDurasi->getErrors());
        }

        // 3. Validasi khusus per jenis cuti
        $validasiKhusus = $this->validasiKhususJenisCuti($user, $jenis, $mulai, $selesai, $lamaCuti);
        if (!$validasiKhusus->isValid()) {
            $errors = array_merge($errors, $validasiKhusus->getErrors());
        }

        // 4. Validasi overlap dengan cuti yang sudah ada
        if ($this->cekOverlapCuti($user, $mulai, $selesai)) {
            $errors[] = 'Terdapat overlap dengan pengajuan cuti yang sudah ada';
        }

        return empty($errors) ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * Validasi durasi cuti terhadap maksimal yang diizinkan
     */
    public function validasiDurasi(JenisCuti $jenis, int $lama): ValidationResult
    {
        if ($jenis->getDurasiMax() === null) {
            return ValidationResult::ok(); // Tidak ada batasan durasi
        }

        if ($lama > $jenis->getDurasiMax()) {
            return ValidationResult::failSingle(
                sprintf(
                    'Durasi cuti %d hari melebihi maksimal %d hari untuk %s',
                    $lama,
                    $jenis->getDurasiMax(),
                    $jenis->getNama()
                )
            );
        }

        return ValidationResult::ok();
    }

    /**
     * Cek kompatibilitas status kepegawaian dengan jenis cuti
     */
    private function cekKompatibilitasStatusKepegawaian(User $user, JenisCuti $jenis): bool
    {
        $tersediaUntuk = $jenis->getTersediUntuk();
        $statusUser = $user->getStatusKepegawaian();

        return $tersediaUntuk === 'ALL' || $tersediaUntuk === $statusUser;
    }

    /**
     * Validasi khusus per jenis cuti berdasarkan aturan bisnis
     */
    private function validasiKhususJenisCuti(
        User $user,
        JenisCuti $jenis,
        \DateTimeInterface $mulai,
        \DateTimeInterface $selesai,
        int $lamaCuti
    ): ValidationResult {
        $kode = $jenis->getKode();
        $errors = [];

        switch ($kode) {
            case 'CT': // Cuti Tahunan
                $validasi = $this->validasiCutiTahunan($user, (int) $mulai->format('Y'), $lamaCuti);
                if (!$validasi->isValid()) {
                    $errors = array_merge($errors, $validasi->getErrors());
                }
                break;

            case 'CB': // Cuti Besar
                $validasi = $this->validasiCutiBesar($user, (int) $mulai->format('Y'));
                if (!$validasi->isValid()) {
                    $errors = array_merge($errors, $validasi->getErrors());
                }
                break;

            case 'CS': // Cuti Sakit
                $validasi = $this->validasiCutiSakit($user, $lamaCuti);
                if (!$validasi->isValid()) {
                    $errors = array_merge($errors, $validasi->getErrors());
                }
                break;

            case 'CM': // Cuti Melahirkan
                $validasi = $this->validasiCutiMelahirkan($user);
                if (!$validasi->isValid()) {
                    $errors = array_merge($errors, $validasi->getErrors());
                }
                break;

            case 'CAP': // Cuti Alasan Penting
                $validasi = $this->validasiCutiAlasanPenting($lamaCuti);
                if (!$validasi->isValid()) {
                    $errors = array_merge($errors, $validasi->getErrors());
                }
                break;

            case 'CLTN': // Cuti di Luar Tanggungan Negara
                $validasi = $this->validasiCLTN($user, (int) $mulai->format('Y'));
                if (!$validasi->isValid()) {
                    $errors = array_merge($errors, $validasi->getErrors());
                }
                break;
        }

        return empty($errors) ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * Validasi Cuti Tahunan
     */
    private function validasiCutiTahunan(User $user, int $tahun, int $lamaCuti): ValidationResult
    {
        $errors = [];

        // Cek masa kerja minimal 1 tahun
        $masaKerja = $this->cutiCalculator->hitungMasaKerja($user, $tahun);
        if ($masaKerja < 1) {
            $errors[] = 'Cuti tahunan memerlukan masa kerja minimal 1 tahun';
        }

        // Cek total akumulasi tidak melebihi 24 hari
        $totalHak = $this->cutiCalculator->hitungTotalHakTahunBerjalan($user, $tahun);
        if ($totalHak < $lamaCuti) {
            $errors[] = sprintf(
                'Sisa hak cuti tahunan tidak mencukupi. Tersedia: %d hari, diajukan: %d hari',
                $totalHak,
                $lamaCuti
            );
        }

        return empty($errors) ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * Validasi Cuti Besar
     */
    private function validasiCutiBesar(User $user, int $tahun): ValidationResult
    {
        $errors = [];

        // Hanya untuk PNS
        if ($user->getStatusKepegawaian() !== 'PNS') {
            $errors[] = 'Cuti Besar hanya untuk PNS';
        }

        // Masa kerja minimal 6 tahun berturut-turut
        $masaKerja = $this->cutiCalculator->hitungMasaKerja($user, $tahun);
        if ($masaKerja < 6) {
            $errors[] = 'Cuti Besar memerlukan masa kerja minimal 6 tahun berturut-turut';
        }

        // Cek apakah sudah pernah ambil CB dalam 6 tahun terakhir
        // TODO: Implementasi pengecekan riwayat CB

        return empty($errors) ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * Validasi Cuti Sakit
     */
    private function validasiCutiSakit(User $user, int $lamaCuti): ValidationResult
    {
        $errors = [];

        // Untuk cuti sakit > 3 hari, perlu surat dokter
        if ($lamaCuti > 3) {
            // Note: Implementasi pengecekan file surat dokter dilakukan di controller/form
            $errors[] = 'Cuti sakit lebih dari 3 hari memerlukan surat keterangan dokter';
        }

        return empty($errors) ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * Validasi Cuti Melahirkan
     */
    private function validasiCutiMelahirkan(User $user): ValidationResult
    {
        $errors = [];

        // Hanya untuk perempuan
        if ($user->getJenisKelamin() !== 'P') {
            $errors[] = 'Cuti melahirkan hanya untuk pegawai perempuan';
        }

        // Aturan: anak ke-1..3 → CM, ke-4 dst → bisa gunakan CB jika eligible
        $jumlahAnak = $user->getJumlahAnak();
        if ($jumlahAnak >= 4) {
            $errors[] = 'Untuk anak ke-4 dan seterusnya, gunakan Cuti Besar jika memenuhi syarat';
        }

        return empty($errors) ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * Validasi Cuti Alasan Penting
     */
    private function validasiCutiAlasanPenting(int $lamaCuti): ValidationResult
    {
        // Durasi maksimal 1 bulan (30 hari)
        if ($lamaCuti > 30) {
            return ValidationResult::failSingle('Cuti Alasan Penting maksimal 30 hari');
        }

        return ValidationResult::ok();
    }

    /**
     * Validasi Cuti di Luar Tanggungan Negara
     */
    private function validasiCLTN(User $user, int $tahun): ValidationResult
    {
        $errors = [];

        // Hanya untuk PNS
        if ($user->getStatusKepegawaian() !== 'PNS') {
            $errors[] = 'CLTN hanya untuk PNS';
        }

        // Masa kerja minimal 5 tahun
        $masaKerja = $this->cutiCalculator->hitungMasaKerja($user, $tahun);
        if ($masaKerja < 5) {
            $errors[] = 'CLTN memerlukan masa kerja minimal 5 tahun';
        }

        return empty($errors) ? ValidationResult::ok() : ValidationResult::fail($errors);
    }

    /**
     * Cek overlap dengan pengajuan cuti yang sudah ada
     */
    private function cekOverlapCuti(User $user, \DateTimeInterface $mulai, \DateTimeInterface $selesai): bool
    {
        $overlappingCuti = $this->pengajuanCutiRepository->findOverlappingCuti($user, $mulai, $selesai);
        return !empty($overlappingCuti);
    }
}