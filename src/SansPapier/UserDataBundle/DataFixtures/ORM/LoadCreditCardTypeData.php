<?php
namespace SansPapier\UserDataBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use SansPapier\UserDataBundle\Entity\CreditCardType;
/**
 * Description of LoadCreditCardType
 *
 * @author nunja
 */
class LoadCreditCardTypeData implements FixtureInterface
{
  public function load(ObjectManager $manager)
  {
    $visa = new CreditCardType();
    $visa->setName("visa");
    $visa->setRegex("/^4[0-9]{12}(?:[0-9]{3})?$/");
    
    $mastercard = new CreditCardType();
    $mastercard->setName("mastercard");
    $mastercard->setRegex("/^5[1-5][0-9]{14}$/");
    
    $amex = new CreditCardType();
    $amex->setName("amex");
    $amex->setRegex("/^3[47][0-9]{13}$/");
    
    $manager->persist($visa);
    $manager->persist($mastercard);
    $manager->persist($amex);
    $manager->flush();
  }
}

?>
