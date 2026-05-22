<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\RestaurantTable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateTimeType::class, [ // CHANGÉ: de DateType à DateTimeType
                'label' => 'Date et heure de réservation',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control datetimepicker',
                    'min' => (new \DateTime())->format('Y-m-d\TH:i')
                ],
                'required' => true,
                'empty_data' => null,
            ])
            ->add('nbPersonnes', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'attr' => [
                    'min' => 1,
                    'max' => 20,
                    'class' => 'form-control'
                ],
                'required' => true,
                'empty_data' => 2,
            ])
            ->add('restaurantTable', EntityType::class, [
                'class' => RestaurantTable::class,
                'label' => 'Table',
                'choice_label' => function ($table) {
                    return 'Table ' . $table->getNumero() . ' - ' . $table->getCapacite() . ' pers';
                },
                'attr' => ['class' => 'form-control'],
                'required' => true,
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('t')
                        ->where('t.status = :status')
                        ->setParameter('status', true);
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}