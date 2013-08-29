<?php

namespace SansPapier\UserDataBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;

class RegistrationFormType extends BaseType
{
    private $class;

    /**
     * @param string $class The User class name
     */
    public function __construct($class)
    {
        $this->class = $class;
    }
    
    public function buildForm(FormBuilder $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // remove username
        $builder->remove("username");
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => $this->class,
            'intention'  => 'registration',
            'validation_groups' => array('Registration'),
        );
    }

    public function getName()
    {
        return 'sans_papier_user_data_registration';
    }
}