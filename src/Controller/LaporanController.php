<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PengajuanCuti;
use App\Repository\PengajuanCutiRepository;
use App\Repository\UserRepository;
use App\Repository\JenisCutiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/laporan')]
class LaporanController extends AbstractController
{
    public function __construct(
        private readonly PengajuanCutiRepository $pengajuanCutiRepository,
        private readonly UserRepository $userRepository,
        private readonly JenisCutiRepository $jenisCutiRepository
    ) {
    }

    #[Route('', name: 'laporan_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isApprover = $this->isGranted('ROLE_APPROVER');

        // Get filter parameters
        $tahun = $request->query->get('tahun', date('Y'));
        $bulan = $request->query->get('bulan');
        $status = $request->query->get('status');
        $jenisCutiId = $request->query->get('jenis_cuti');
        $userId = $request->query->get('user_id');

        // Build query criteria
        $criteria = ['tahun' => $tahun];
        
        if ($bulan) {
            $criteria['bulan'] = $bulan;
        }
        
        if ($status) {
            $criteria['status'] = $status;
        }
        
        if ($jenisCutiId) {
            $criteria['jenisCuti'] = $jenisCutiId;
        }

        // User-specific filtering
        if (!$isAdmin && !$isApprover) {
            $criteria['user'] = $user;
        } elseif ($userId) {
            $userObj = $this->userRepository->find($userId);
            if ($userObj) {
                $criteria['user'] = $userObj;
            }
        }

        // Get pengajuan data
        $pengajuanList = $this->pengajuanCutiRepository->findByCriteria($criteria);

        // Calculate statistics
        $statistics = $this->calculateStatistics($pengajuanList, $isAdmin || $isApprover ? null : $user);

        // Get filter options
        $jenisCutiOptions = $this->jenisCutiRepository->findAll();
        $userOptions = ($isAdmin || $isApprover) ? $this->userRepository->findAll() : [];

        return $this->render('laporan/index.html.twig', [
            'pengajuan_list' => $pengajuanList,
            'statistics' => $statistics,
            'current_tahun' => $tahun,
            'current_bulan' => $bulan,
            'current_status' => $status,
            'current_jenis_cuti' => $jenisCutiId,
            'current_user_id' => $userId,
            'jenis_cuti_options' => $jenisCutiOptions,
            'user_options' => $userOptions,
            'is_admin' => $isAdmin,
            'is_approver' => $isApprover,
        ]);
    }

    #[Route('/export', name: 'laporan_export', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function export(Request $request): Response
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isApprover = $this->isGranted('ROLE_APPROVER');

        // Get same filter parameters as index
        $tahun = $request->query->get('tahun', date('Y'));
        $bulan = $request->query->get('bulan');
        $status = $request->query->get('status');
        $jenisCutiId = $request->query->get('jenis_cuti');
        $userId = $request->query->get('user_id');

        // Build query criteria (same as index)
        $criteria = ['tahun' => $tahun];
        
        if ($bulan) {
            $criteria['bulan'] = $bulan;
        }
        
        if ($status) {
            $criteria['status'] = $status;
        }
        
        if ($jenisCutiId) {
            $criteria['jenisCuti'] = $jenisCutiId;
        }

        if (!$isAdmin && !$isApprover) {
            $criteria['user'] = $user;
        } elseif ($userId) {
            $userObj = $this->userRepository->find($userId);
            if ($userObj) {
                $criteria['user'] = $userObj;
            }
        }

        $pengajuanList = $this->pengajuanCutiRepository->findByCriteria($criteria);

        // Generate CSV content
        $csv = $this->generateCSV($pengajuanList);

        // Generate filename
        $filename = 'laporan_cuti_' . $tahun;
        if ($bulan) {
            $filename .= '_' . str_pad($bulan, 2, '0', STR_PAD_LEFT);
        }
        $filename .= '_' . date('Y-m-d') . '.csv';

        // Return CSV response
        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    private function calculateStatistics(array $pengajuanList, $user = null): array
    {
        $stats = [
            'total' => count($pengajuanList),
            'draft' => 0,
            'diajukan' => 0,
            'disetujui' => 0,
            'ditolak' => 0,
            'dibatalkan' => 0,
            'total_hari' => 0,
            'by_jenis' => [],
            'by_bulan' => []
        ];

        foreach ($pengajuanList as $pengajuan) {
            // Status statistics
            $status = $pengajuan->getStatus();
            if (isset($stats[$status])) {
                $stats[$status]++;
            }

            // Total hari cuti
            $stats['total_hari'] += $pengajuan->getLamaCuti();

            // By jenis cuti
            $jenisNama = $pengajuan->getJenisCuti()->getNama();
            if (!isset($stats['by_jenis'][$jenisNama])) {
                $stats['by_jenis'][$jenisNama] = ['count' => 0, 'hari' => 0];
            }
            $stats['by_jenis'][$jenisNama]['count']++;
            $stats['by_jenis'][$jenisNama]['hari'] += $pengajuan->getLamaCuti();

            // By bulan
            $bulan = $pengajuan->getTanggalMulai()->format('n');
            if (!isset($stats['by_bulan'][$bulan])) {
                $stats['by_bulan'][$bulan] = ['count' => 0, 'hari' => 0];
            }
            $stats['by_bulan'][$bulan]['count']++;
            $stats['by_bulan'][$bulan]['hari'] += $pengajuan->getLamaCuti();
        }

        return $stats;
    }

    private function generateCSV(array $pengajuanList): string
    {
        $csv = "Nama,NIP,Unit Kerja,Jenis Cuti,Tanggal Mulai,Tanggal Selesai,Lama Cuti,Status,Tanggal Pengajuan,Alasan\n";

        foreach ($pengajuanList as $pengajuan) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s",%d,"%s","%s","%s"' . "\n",
                $pengajuan->getUser()->getNama(),
                $pengajuan->getUser()->getNip() ?: '-',
                $pengajuan->getUser()->getUnitKerja() ? $pengajuan->getUser()->getUnitKerja()->getNama() : '-',
                $pengajuan->getJenisCuti()->getNama(),
                $pengajuan->getTanggalMulai()->format('d/m/Y'),
                $pengajuan->getTanggalSelesai()->format('d/m/Y'),
                $pengajuan->getLamaCuti(),
                ucfirst($pengajuan->getStatus()),
                $pengajuan->getTanggalPengajuan()->format('d/m/Y H:i'),
                str_replace('"', '""', $pengajuan->getAlasan())
            );
        }

        return $csv;
    }
}