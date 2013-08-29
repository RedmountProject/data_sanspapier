<?php

namespace SansPapier\UserDataBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class PreferenceFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            //->add('gender','entity', array('class'=>'SansPapier\UserDataBundle\Entity\Gender', 'expanded'=>true))
            ->add('firstname', 'text',array('required'=>true))
            ->add('lastname', 'text',array('required'=>true))
            ->add('notifiable','checkbox', array('required'=>false))
            ->add('billing_address', new AddressFormType())
        ;
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'SansPapier\UserDataBundle\Entity\Preference'
        );
    }

    public function getName()
    {
        return 'sanspapier_userdatabundle_preferencetype';
    }
}
