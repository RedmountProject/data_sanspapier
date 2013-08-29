<?php

namespace SansPapier\UserDataBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use SansPapier\UserDataBundle\Entity\Format;

class LoadFormatData implements FixtureInterface
{

  public function load(ObjectManager $manager)
  {
    $epub = new Format();
    $epub->setName("epub");
    $pdf = new Format();
    $pdf->setName("pdf");
    $mobi = new Format();
    $mobi->setName("mobi");

    $manager->persist($epub);
    $manager->persist($pdf);
    $manager->persist($mobi);
    $manager->flush();
  }

}