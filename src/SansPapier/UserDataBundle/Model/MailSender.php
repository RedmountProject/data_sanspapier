<?php

namespace SansPapier\UserDataBundle\Model;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\EngineInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;


class MailHelper extends BaseHandler{

    protected $mailer;

    protected $templating;
    
    public function __construct(\Swift_Mailer $mailer, EngineInterface $templating) {
        $this->mailer = $mailer;
        $this->templating = $templating;
    }

    public function sendEmail($from, $to, $body, $subject = '') {
        $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom($from)
                ->setTo($to)
                ->setBody($body);

        $this->mailer->send($message);
    }

}

?>
