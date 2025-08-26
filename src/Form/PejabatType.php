<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Pejabat;
use App\Entity\UnitKerja;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PejabatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nama', TextType::class, [
                'label' => 'Nama Lengkap',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: Dr. Ahmad Wijaya, M.Ag',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Nama lengkap harus diisi'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Nama minimal {{ limit }} karakter',
                        'maxMessage' => 'Nama maksimal {{ limit }} karakter'
                    ])
                ]
            ])
            ->add('nip', TextType::class, [
                'label' => 'NIP',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: 199001012020121001',
                    'maxlength' => 18
                ],
                'help' => 'Nomor Induk Pegawai (18 digit, opsional)',
                'constraints' => [
                    new Assert\Length([
                        'min' => 18,
                        'max' => 18,
                        'exactMessage' => 'NIP harus tepat {{ limit }} digit'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]+$/',
                        'message' => 'NIP hanya boleh berisi angka'
                    ])
                ]
            ])
            ->add('jabatan', TextType::class, [
                'label' => 'Jabatan',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: Kepala Kantor Wilayah, Kabag Tata Usaha',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Jabatan harus diisi'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Jabatan minimal {{ limit }} karakter',
                        'maxMessage' => 'Jabatan maksimal {{ limit }} karakter'
                    ])
                ]
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
                ],
                'help' => 'Unit kerja tempat pejabat bertugas (opsional untuk pejabat struktural)'
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Aktif' => 'aktif',
                    'Non-aktif' => 'nonaktif'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Status harus dipilih'
                    ]),
                    new Assert\Choice([
                        'choices' => ['aktif', 'nonaktif'],
                        'message' => 'Status tidak valid'
                    ])
                ]
            ])
            ->add('mulaiMenjabat', DateType::class, [
                'label' => 'Mulai Menjabat',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTime())->format('Y-m-d')
                ],
                'help' => 'Tanggal mulai menjabat pada posisi ini',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Tanggal mulai menjabat harus diisi'
                    ]),
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'Tanggal mulai menjabat tidak boleh lebih dari hari ini'
                    ])
                ]
            ])
            ->add('selesaiMenjabat', DateType::class, [
                'label' => 'Selesai Menjabat',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Tanggal selesai menjabat (kosongkan jika masih aktif)',
                'constraints' => [
                    new Assert\GreaterThan([
                        'propertyPath' => 'parent.all[mulaiMenjabat].data',
                        'message' => 'Tanggal selesai harus setelah tanggal mulai menjabat'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pejabat::class,
        ]);
    }
}