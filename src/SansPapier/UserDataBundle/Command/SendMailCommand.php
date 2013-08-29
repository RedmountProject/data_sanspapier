<?php

namespace SansPapier\UserDataBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

class SendMailCommand extends ContainerAwareCommand {

    protected function configure() {
        $this->setName('sanspapier:SendMail_SDL2013')
                ->setDescription('Send an Email to <user_mail> with specified <packids>, <password> and account status ( 0 : new, > 0 : not new )')
                ->addArgument('user_mail')
                ->addArgument('password')
                ->addArgument('packids')
                ->addArgument('new_account');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln("Init");

        $to = $input->getArgument('user_mail');

        $output->writeln("Sending Email to " . $to);

        $statut = $input->getArgument('new_account');
        
        $pack_ids = explode('-', $input->getArgument('packids')) or $pack_ids = "aucun";

                
        $subject = "[Salon du livre 2013] Confirmation de la création de votre compte chez sanspapier.com";
        $html = $this->getContainer()
                ->get('templating')
                ->render('SansPapierUserDataBundle:EventMailing:sendSDL2013AccountAndBooksMail.html.twig', array(
            'user_mail' => $to,
            'password' => $input->getArgument('password'),
            'packids' => $pack_ids,
                ));

        if ($statut > 0) {
            $html = $this->getContainer()->get('templating')->render('SansPapierUserDataBundle:EventMailing:sendSDL2013BooksMail.html.twig', array(
                'user_mail' => $to,
                'packids' => explode('-', $input->getArgument('packids')),
                    ));
            $subject = "[Salon du livre 2013] Vos livres du domaine public dans votre bibliothèque sanspapier.com";
        }


        $from = "info@sanspapier.com";

        $this->getContainer()->get('mail_helper')->sendEmail($from, $to, $html, $subject);
    }

}

?>
