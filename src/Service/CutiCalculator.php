<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\HakCutiRepository;
use App\Repository\HariLiburRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service untuk kalkulasi hak cuti dan perhitungan terkait
 */
class CutiCalculator
{
    public function __construct(
        private readonly HakCutiRepository $hakCutiRepository,
        private readonly HariLiburRepository $hariLiburRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Hitung hak cuti tahunan untuk user di tahun tertentu
     * Default: 12 hari per tahun
     */
    public function hitungHakTahunan(User $user, int $tahun): int
    {
        // Default hak cuti tahunan adalah 12 hari
        // Bisa di-customize berdasarkan masa kerja, golongan, dll
        $hakTahunan = 12;

        // Untuk PNS dengan masa kerja > 20 tahun, bisa dapat tambahan
        if ($user->getStatusKepegawaian() === 'PNS' && $user->getTmtCpns()) {
            $masaKerja = $this->hitungMasaKerja($user, $tahun);
            if ($masaKerja >= 20) {
                $hakTahunan = 15; // Tambahan 3 hari
            } elseif ($masaKerja >= 10) {
                $hakTahunan = 14; // Tambahan 2 hari
            }
        }

        return $hakTahunan;
    }

    /**
     * Get cuti yang dibawa dari tahun-tahun sebelumnya
     * Return: ['n1' => sisa_n1, 'n2' => sisa_n2] dengan max 6 hari per tahun
     */
    public function getCutiDibawa(User $user, int $tahun): array
    {
        $cutiDibawa = ['n1' => 0, 'n2' => 0];

        // Cuti dari tahun sebelumnya (N-1)
        $hakCutiN1 = $this->hakCutiRepository->findByUserAndTahun($user, $tahun - 1);
        if ($hakCutiN1 && $hakCutiN1->isCarryOver() && $hakCutiN1->getSisa() > 0) {
            $cutiDibawa['n1'] = min($hakCutiN1->getSisa(), 6);
        }

        // Cuti dari 2 tahun sebelumnya (N-2)
        $hakCutiN2 = $this->hakCutiRepository->findByUserAndTahun($user, $tahun - 2);
        if ($hakCutiN2 && $hakCutiN2->isCarryOver() && $hakCutiN2->getSisa() > 0) {
            $cutiDibawa['n2'] = min($hakCutiN2->getSisa(), 6);
        }

        return $cutiDibawa;
    }

    /**
     * Hitung total hak cuti tahun berjalan termasuk cuti dari tahun sebelumnya
     * Formula: N + min(N-1, 6) + min(N-2, 6) dengan maksimal 24 hari
     */
    public function hitungTotalHakTahunBerjalan(User $user, int $tahun): int
    {
        $hakTahunIni = $this->hitungHakTahunan($user, $tahun);
        $cutiDibawa = $this->getCutiDibawa($user, $tahun);
        
        $totalHak = $hakTahunIni + $cutiDibawa['n1'] + $cutiDibawa['n2'];
        
        // Cap maksimal 24 hari
        return min($totalHak, 24);
    }

    /**
     * Hitung lama cuti dari tanggal mulai ke tanggal selesai
     * Mode: 'calendar' (hari kalender) atau 'workday' (hari kerja)
     */
    public function hitungLamaCuti(
        \DateTimeInterface $mulai, 
        \DateTimeInterface $selesai,
        string $mode = 'calendar'
    ): int {
        if ($mulai > $selesai) {
            return 0;
        }

        if ($mode === 'calendar') {
            return $this->hitungHariKalender($mulai, $selesai);
        } else {
            return $this->hitungHariKerja($mulai, $selesai);
        }
    }

    /**
     * Kurangi saldo cuti tahunan user
     */
    public function kurangiSaldoTahunan(User $user, int $tahun, int $jumlah): void
    {
        // Urutan pengurangan: N-2 -> N-1 -> N (FIFO)
        
        // 1. Kurangi dari carry over N-2 dulu
        $hakCutiN2 = $this->hakCutiRepository->findByUserAndTahun($user, $tahun - 2);
        if ($hakCutiN2 && $hakCutiN2->getSisa() > 0 && $jumlah > 0) {
            $potongN2 = min($hakCutiN2->getSisa(), $jumlah);
            $hakCutiN2->setTerpakai($hakCutiN2->getTerpakai() + $potongN2);
            $jumlah -= $potongN2;
            $this->entityManager->persist($hakCutiN2);
        }

        // 2. Kurangi dari carry over N-1
        if ($jumlah > 0) {
            $hakCutiN1 = $this->hakCutiRepository->findByUserAndTahun($user, $tahun - 1);
            if ($hakCutiN1 && $hakCutiN1->getSisa() > 0) {
                $potongN1 = min($hakCutiN1->getSisa(), $jumlah);
                $hakCutiN1->setTerpakai($hakCutiN1->getTerpakai() + $potongN1);
                $jumlah -= $potongN1;
                $this->entityManager->persist($hakCutiN1);
            }
        }

        // 3. Kurangi dari hak tahun berjalan (N)
        if ($jumlah > 0) {
            $hakCutiN = $this->hakCutiRepository->findByUserAndTahun($user, $tahun);
            if ($hakCutiN && $hakCutiN->getSisa() > 0) {
                $potongN = min($hakCutiN->getSisa(), $jumlah);
                $hakCutiN->setTerpakai($hakCutiN->getTerpakai() + $potongN);
                $this->entityManager->persist($hakCutiN);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Hitung masa kerja user sampai tahun tertentu
     */
    public function hitungMasaKerja(User $user, int $tahun): int
    {
        $tmtCpns = $user->getTmtCpns();
        if (!$tmtCpns) {
            return 0;
        }

        $tahunMulai = (int) $tmtCpns->format('Y');
        return $tahun - $tahunMulai;
    }

    /**
     * Check apakah user eligible untuk carry over
     */
    public function isEligibleCarryOver(User $user, int $tahun): bool
    {
        // Hanya PNS yang bisa carry over, minimal masa kerja 1 tahun
        if ($user->getStatusKepegawaian() !== 'PNS') {
            return false;
        }

        return $this->hitungMasaKerja($user, $tahun) >= 1;
    }

    /**
     * Hitung hari kalender (termasuk weekend)
     */
    private function hitungHariKalender(\DateTimeInterface $mulai, \DateTimeInterface $selesai): int
    {
        $diff = $mulai->diff($selesai);
        return $diff->days + 1;
    }

    /**
     * Hitung hari kerja (exclude weekend dan hari libur)
     */
    private function hitungHariKerja(\DateTimeInterface $mulai, \DateTimeInterface $selesai): int
    {
        $hariKerja = 0;
        $current = clone $mulai;
        
        // Get all hari libur dalam rentang tanggal untuk optimasi query
        $hariLiburList = $this->hariLiburRepository->findByDateRange($mulai, $selesai);
        $tanggalLibur = [];
        foreach ($hariLiburList as $hariLibur) {
            $tanggalLibur[] = $hariLibur->getTanggal()->format('Y-m-d');
        }
        
        while ($current <= $selesai) {
            // Skip weekend (Saturday = 6, Sunday = 0)
            $dayOfWeek = (int) $current->format('w');
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                // Check if current date is hari libur
                $currentDateString = $current->format('Y-m-d');
                if (!in_array($currentDateString, $tanggalLibur, true)) {
                    $hariKerja++;
                }
            }
            $current->modify('+1 day');
        }

        return $hariKerja;
    }
}