<?php

namespace SansPapier\UserDataBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class UserPreferencesFormType extends AbstractType
{

  public function buildForm(FormBuilder $builder, array $options)
  {
      $builder
     ->add('email', 'email', array('required'=>true))
     ->add('preference', new PreferenceFormType());
  }

  public function getDefaultOptions(array $options)
  { 
    return array(
      'data_class' => 'SansPapier\UserDataBundle\Entity\User'
    );
  }

  public function getName()
  {
    return 'sanspapier_userdatabundle_usertype';
  }

}
