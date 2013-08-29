<?php

namespace SansPapier\UserDataBundle\Model;

use Symfony\Component\Validator\Constraint;
use FOS\UserBundle\Entity\UserManager as BaseUserManager;
use FOS\UserBundle\Model\UserInterface;
/**
 * UserManager to override the FOSUser manager
 *
 * @author nunja
 */
class UserManager extends BaseUserManager
{
  public function validateUnique(UserInterface $value, Constraint $constraint)
  {
    $res = $this->findUserByEmail($value->getEmail());
    if($res){
      return false;
    }
    return true;
  }

}


