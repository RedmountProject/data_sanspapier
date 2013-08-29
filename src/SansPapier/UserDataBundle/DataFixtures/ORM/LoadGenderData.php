<?php 
namespace SansPapier\UserDataBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use SansPapier\UserDataBundle\Entity\Gender;

class LoadGenderData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $mr = new Gender();
        $mr->setName('Mr');
        $mme = new Gender();
        $mme->setName('Mme');
        $mlle = new Gender();
        $mlle->setName('Mlle');

        $manager->persist($mr);
        $manager->persist($mme);
        $manager->persist($mlle);
        $manager->flush();
    }
}