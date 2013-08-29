<?php

namespace SansPapier\UserDataBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class getUserIdCommand extends ContainerAwareCommand {

    protected function configure() {

        $this->setName('sanspapier:get:id')
                ->addArgument('user_mail', InputArgument::REQUIRED, 'mail of the user')
                ->setDescription('get User Id');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $mail = $input->getArgument('user_mail');
        
        $con = $this->getContainer()->get("doctrine.dbal.user_connection");
      
        
        $stmt = $con->prepare("SELECT user_id from spdata_user WHERE email = :mail");
        $stmt->bindValue('mail', $mail);
        $stmt->execute();
        $id = $stmt->fetchAll();
        
        if(count($id) != 1)
            return -1;
        
        echo $id[0]['user_id'];
        return 1;
    }
}

