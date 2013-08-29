<?php

namespace SansPapier\UserDataBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
 * Description of CustomizationFormType
 *
 * @author nunja
 */
class CustomizationFormType extends AbstractType
{

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    
  }

  public function buildForm(FormBuilder $builder, array $options)
  {
    $builder
    ->add('publisher_1', 'text', array('required'=>false))
    ->add('publisher_2', 'text', array('required'=>false))
    ->add('publisher_3', 'text', array('required'=>false))
    ->add('publisher_4', 'text', array('required'=>false))
    ->add('publisher_5', 'text', array('required'=>false))
     
    ->add('genre_1', 'choice',array('required'=>false))
    ->add('genre_2', 'choice',array('required'=>false))
    ->add('genre_3', 'choice',array('required'=>false))
    ->add('genre_4', 'choice',array('required'=>false))
    ->add('genre_5', 'choice',array('required'=>false))
     
    ->add('search_1', 'text', array('required'=>false))
    ->add('search_2', 'text', array('required'=>false))
    ->add('search_3', 'text', array('required'=>false))
    ->add('search_4', 'text', array('required'=>false))
    ->add('search_5', 'text', array('required'=>false));
  }

  /*
    public function getDefaultOptions(array $options)
    {
    return array(
    'data_class' => 'SansPapier\UserDataBundle\Entity\User',
    'validation_groups' => array('Preferences'),
    );
    } */

  public function getName()
  {
    return 'sanspapier_userdatabundle_customizationtype';
  }

}

?>
