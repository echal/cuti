<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\PengajuanCuti;
use App\Entity\JenisCuti;
use App\Entity\User;
use App\Repository\JenisCutiRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormInterface;

class PengajuanCutiType extends AbstractType
{
    public function __construct(
        private readonly JenisCutiRepository $jenisCutiRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tanggalMulai', DateType::class, [
                'label' => 'Tanggal Mulai Cuti',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d')
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Tanggal mulai cuti harus diisi'
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'Tanggal mulai cuti tidak boleh kurang dari hari ini'
                    ])
                ]
            ])
            ->add('tanggalSelesai', DateType::class, [
                'label' => 'Tanggal Selesai Cuti',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d')
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Tanggal selesai cuti harus diisi'
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'propertyPath' => 'parent.all[tanggalMulai].data',
                        'message' => 'Tanggal selesai harus sama atau setelah tanggal mulai'
                    ])
                ]
            ])
            ->add('alasan', TextareaType::class, [
                'label' => 'Alasan Cuti',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Jelaskan alasan pengajuan cuti...'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Alasan cuti harus diisi'
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 500,
                        'minMessage' => 'Alasan cuti minimal 10 karakter',
                        'maxMessage' => 'Alasan cuti maksimal 500 karakter'
                    ])
                ]
            ])
            ->add('alamatCuti', TextareaType::class, [
                'label' => 'Alamat Selama Menjalankan Cuti',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Masukkan alamat lengkap selama menjalankan cuti...'
                ],
                'help' => 'Alamat ini akan dicantumkan dalam surat cuti resmi',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Alamat selama menjalankan cuti harus diisi'
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 300,
                        'minMessage' => 'Alamat minimal 10 karakter',
                        'maxMessage' => 'Alamat maksimal 300 karakter'
                    ])
                ]
            ])
            ->add('userTelp', TextType::class, [
                'label' => 'Nomor Telepon',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contoh: 08123456789 atau +628123456789',
                    'maxlength' => 15
                ],
                'help' => 'Nomor telepon yang dapat dihubungi selama cuti (akan disimpan ke profil Anda)',
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^(\+62|0)[0-9]{8,13}$/',
                        'message' => 'Format nomor telepon tidak valid. Gunakan format Indonesia: 08xxxxxxxxx atau +628xxxxxxxxx'
                    ])
                ]
            ])
            // File pendukung dinonaktifkan sementara
            /*->add('filePendukung', FileType::class, [
                'label' => 'File Pendukung',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx'
                ],
                'help' => 'Format yang diizinkan: PDF, JPG, PNG, DOC, DOCX (maksimal 5MB)',
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ],
                        'mimeTypesMessage' => 'Format file tidak valid. Gunakan PDF, JPG, PNG, DOC, atau DOCX',
                        'maxSizeMessage' => 'File terlalu besar ({{ size }} {{ suffix }}). Maksimal {{ limit }} {{ suffix }}'
                    ])
                ]
            ])*/;

        // Add event subscribers for dynamic jenisCuti filtering
        $this->addJenisCutiEventSubscribers($builder, $options);
    }

    /**
     * Add event subscribers to filter jenisCuti based on user status
     */
    private function addJenisCutiEventSubscribers(FormBuilderInterface $builder, array $options): void
    {
        $formModifier = function (FormInterface $form, ?User $user = null): void {
            $jenisCutiOptions = [
                'class' => JenisCuti::class,
                'label' => 'Jenis Cuti',
                'choice_label' => fn(JenisCuti $jenisCuti): string => 
                    sprintf('%s - %s', $jenisCuti->getKode(), $jenisCuti->getNama()),
                'placeholder' => '-- Pilih Jenis Cuti --',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Jenis cuti harus dipilih'
                    ])
                ]
            ];

            if ($user) {
                // Filter jenis cuti based on user's status kepegawaian
                $statusKepegawaian = $user->getStatusKepegawaian();
                
                $jenisCutiOptions['choices'] = $this->jenisCutiRepository->findByTersediUntuk($statusKepegawaian);
                
                // Add help text based on user status
                if ($statusKepegawaian === 'PPPK') {
                    $jenisCutiOptions['help'] = 'Catatan: Sebagai PPPK, Anda tidak dapat mengajukan Cuti Besar (CB) dan Cuti di Luar Tanggungan Negara (CLTN)';
                }
            } else {
                // If no user context, show all available jenis cuti
                $jenisCutiOptions['choices'] = $this->jenisCutiRepository->findAllOrdered();
            }

            $form->add('jenisCuti', EntityType::class, $jenisCutiOptions);
        };

        // PRE_SET_DATA: When form is created/populated
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($formModifier, $options): void {
            /** @var PengajuanCuti|null $data */
            $data = $event->getData();
            $user = $data?->getUser() ?? $options['user'] ?? null;
            
            $formModifier($event->getForm(), $user);
            
            // Set default value for userTelp
            if ($user && $event->getForm()->has('userTelp')) {
                $event->getForm()->get('userTelp')->setData($user->getTelp());
            }
        });

        // PRE_SUBMIT: When form is submitted (for AJAX updates)
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($formModifier, $options): void {
            $user = $options['user'] ?? null;
            
            $formModifier($event->getForm(), $user);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PengajuanCuti::class,
            'user' => null, // Pass current user context
        ]);

        $resolver->setAllowedTypes('user', ['null', User::class]);
    }
}