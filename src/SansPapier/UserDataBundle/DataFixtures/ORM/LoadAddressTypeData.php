<?php
namespace SansPapier\UserDataBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use SansPapier\UserDataBundle\Entity\AddressType;

class LoadAddressTypeData implements FixtureInterface
{
  public function load(ObjectManager $manager)
  {
    $del = new AddressType();
    $del->setName("delivery");
    $bill = new AddressType();
    $bill->setName("billing");

    $manager->persist($del);
    $manager->persist($bill);
    $manager->flush();
  } 
}