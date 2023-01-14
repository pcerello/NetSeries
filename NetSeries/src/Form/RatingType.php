<?php

namespace App\Form;

use App\Entity\Rating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Choice;

class RatingType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', ChoiceType::class, [ 'choices' => [
                '0 ⭐' => 0,
                '0.5 ⭐' => 1,
                '1 ⭐' => 2,
                '1.5 ⭐' => 3,
                '2 ⭐' => 4,
                '2.5 ⭐' => 5,
                '3 ⭐' => 6,
                '3.5 ⭐' => 7,
                '4 ⭐' => 8,
                '4.5 ⭐' => 9,
                '5 ⭐' => 10,
            ],
            'label' => 'Rating of series',
            'constraints' => [new Choice(['choices' => [0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5]])]
        ])
            ->add('comment');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rating::class,
        ]);
    }
}
