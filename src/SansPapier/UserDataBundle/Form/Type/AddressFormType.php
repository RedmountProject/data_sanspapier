<?php

namespace SansPapier\UserDataBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
 * Description of AddressType
 *
 * @author nunja
 */
class AddressFormType extends AbstractType
{

  public function buildForm(FormBuilder $builder, array $options)
  {
    $builder
//     ->add('addressee')
//     ->add('company_name')
//     ->add('address')
//     ->add('complement')
//     ->add('zip')
//     ->add('city')
     ->add('country_code','country');
  }

  public function getDefaultOptions(array $options)
  {
    return array(
      'data_class' => 'SansPapier\UserDataBundle\Entity\Address',
      'validation_groups' => array('Preferences'),
    );
  }

  public function getName()
  {
    return 'sanspapier_userdatabundle_addresstype';
  }

}

?>
