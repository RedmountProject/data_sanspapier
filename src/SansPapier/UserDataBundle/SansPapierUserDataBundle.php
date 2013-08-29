<?php

namespace SansPapier\UserDataBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SansPapierUserDataBundle extends Bundle
{
  public function getParent()
  {
    return 'FOSUserBundle';
  }
}
