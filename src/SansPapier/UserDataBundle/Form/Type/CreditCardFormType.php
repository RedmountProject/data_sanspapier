<?php

namespace SansPapier\UserDataBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class CreditCardFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('number')
            ->add('expiration')
            ->add('holder')
            ->add('credit_card_type')
        ;
    }

    public function getName()
    {
        return 'sanspapier_userdatabundle_creditcardtype';
    }
}
