<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Entity\UnitKerja;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nip', TextType::class, [
                'label' => 'NIP',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: 199001012020121001 (opsional)',
                    'maxlength' => 18
                ],
                'help' => 'Nomor Induk Pegawai (18 digit). Wajib untuk PNS, opsional untuk PPPK',
                'constraints' => [
                    new Assert\Length([
                        'max' => 18,
                        'maxMessage' => 'NIP maksimal 18 digit'
                    ])
                ]
            ])
            ->add('nama', TextType::class, [
                'label' => 'Nama Lengkap',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: Siti Aminah, S.Ag',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Nama lengkap harus diisi'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Nama minimal 2 karakter',
                        'maxMessage' => 'Nama maksimal 255 karakter'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: nama@kemenag.go.id',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Email harus diisi'
                    ]),
                    new Assert\Email([
                        'message' => 'Format email tidak valid'
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Email maksimal 255 karakter'
                    ])
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Kosongkan untuk menggunakan password default'
                ],
                'help' => 'Jika dikosongkan, password akan diset otomatis'
            ])
            ->add('jenisKelamin', ChoiceType::class, [
                'label' => 'Jenis Kelamin',
                'choices' => [
                    'Pilih Jenis Kelamin' => '',
                    'Laki-laki' => 'L',
                    'Perempuan' => 'P'
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('statusKepegawaian', ChoiceType::class, [
                'label' => 'Status Kepegawaian',
                'choices' => [
                    'Pilih Status Kepegawaian' => '',
                    'Pegawai Negeri Sipil (PNS)' => 'PNS',
                    'Pegawai Pemerintah dengan Perjanjian Kerja (PPPK)' => 'PPPK'
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Status kepegawaian akan mempengaruhi jenis cuti yang tersedia.'
            ])
            ->add('jabatan', TextType::class, [
                'label' => 'Jabatan',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: Penata Muda, Guru Madrasah',
                    'maxlength' => 255
                ]
            ])
            ->add('golongan', TextType::class, [
                'label' => 'Golongan',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: III/a - Penata Muda',
                    'maxlength' => 50
                ],
                'help' => 'Golongan kepegawaian sesuai dengan SK Penetapan'
            ])
            ->add('telp', TextType::class, [
                'label' => 'Nomor Telepon',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: 08123456789 atau +628123456789',
                    'maxlength' => 15
                ],
                'help' => 'Nomor telepon yang dapat dihubungi (opsional)'
            ])
            ->add('unitKerja', EntityType::class, [
                'label' => 'Unit Kerja',
                'class' => UnitKerja::class,
                'choice_label' => fn(UnitKerja $unitKerja): string => 
                    sprintf('%s - %s', $unitKerja->getKode(), $unitKerja->getNama()),
                'placeholder' => '-- Pilih Unit Kerja --',
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('tmtCpns', DateType::class, [
                'label' => 'TMT CPNS',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTime())->format('Y-m-d')
                ],
                'help' => 'Terhitung Mulai Tanggal sebagai CPNS (hanya untuk PNS)',
                'constraints' => [
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'TMT CPNS tidak boleh lebih dari hari ini'
                    ])
                ]
            ])
            ->add('tmtPns', DateType::class, [
                'label' => 'TMT PNS',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTime())->format('Y-m-d')
                ],
                'help' => 'Terhitung Mulai Tanggal sebagai PNS (hanya untuk PNS)',
                'constraints' => [
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'TMT PNS tidak boleh lebih dari hari ini'
                    ])
                ]
            ])
            ->add('jumlahAnak', IntegerType::class, [
                'label' => 'Jumlah Anak',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20,
                    'value' => 0
                ],
                'help' => 'Jumlah anak yang masih menjadi tanggungan'
            ])
            ->add('statusPegawai', ChoiceType::class, [
                'label' => 'Status Pegawai',
                'choices' => [
                    'Pilih Status Pegawai' => '',
                    'Aktif' => 'aktif',
                    'Cuti di Luar Tanggungan Negara (CLTN)' => 'CLTN',
                    'Diberhentikan' => 'diberhentikan',
                    'Pensiun' => 'pensiun'
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Status aktif pegawai saat ini'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}