<?php

namespace App\Form;

use App\Entity\Bet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            //->add('id_match')
            // ->add('bet_bet')
            ->add('price_bet')
            //->add('odds_bet')
            //->add('result_bet')
            //->add('paid_bet')
            // ->add('id_user', EntityType::class, [
            //     'class' => User::class,
            //     'choice_label' => 'id',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bet::class,
        ]);
    }
}
