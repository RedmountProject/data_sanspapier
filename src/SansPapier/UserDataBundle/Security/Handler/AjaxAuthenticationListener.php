<?php

namespace SansPapier\UserDataBundle\Security\Handler;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Bridge\Monolog\Logger as Logger;
use Symfony\Component\Translation\Translator as Translator;
use FOS\RestBundle\View\ViewHandler as ViewHandler;
use FOS\RestBundle\View\View;

/**
 * Handler for Ajax Login
 *
 * @author nunja
 */
class AjaxAuthenticationListener implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{

  private $logger;
  private $router;
  private $viewHandler;
  private $translator;

  public function __construct(Router $router, Logger $logger, ViewHandler $viewHandler, Translator $translator)
  {
    $this->router = $router;
    $this->logger = $logger;
    $this->viewHandler = $viewHandler;
    $this->translator = $translator;
  }

  /**
   * @param Request $request
   * @param TokenInterface $token
   * @return RedirectResponse 
   */
  public function onAuthenticationSuccess(Request $request, TokenInterface $token)
  {
    if ($request->isXmlHttpRequest()) // ajax here, so JSON response
    {
      // successful data;
      $result = array();
      $result['status'] = TRUE;
      $result['message'] = $this->translator->trans('layout.logged_in_as', array("%email%" => $token->getUser()->getEmail()), 'FOSUserBundle');
      $targetPath = $request->getSession()->get('_security.target_path');

      if ($targetPath)
      {
        $result['redirection'] = $targetPath;
      }
      // create a FOSRest View
      $view = View::create()
       ->setStatusCode(200)
       ->setData($result);
      $view->setFormat('json');
      return $this->viewHandler->handle($view);
    } else
    {
      // If the user tried to access a protected resource and was forces to login
      // redirect him back to that resource
      if ($targetPath = $request->getSession()->get('_security.target_path'))
      {
        $url = $targetPath;
      } else
      {
        // SHOULD REDIRECT TO THE USER PROFILE
        $url = $this->router->generate('/profile', array());
      }

      return new RedirectResponse($url);
    }
  }

  /**
   * @param Request $request
   * @param AuthenticationException $exception
   * @return RedirectResponse 
   */
  public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
  {
    if ($request->isXmlHttpRequest())
    {
      // Handle XHR here
      // successful data;
      $result = array();
      $result['status'] = FALSE;
      $result['message'] = $this->translator->trans('security.loggin_failed', array(), 'FOSUserBundle');
      $valid_errors = array(array('global', $result['message']));
      $result['valid_errors'] = $valid_errors;

      // create a FOSRest View
      $view = View::create()
       ->setStatusCode(200)
       ->setData($result);
      $view->setFormat('json');
      return $this->viewHandler->handle($view);
    } else
    {
      // Create a flash message with the authentication error message
      $request->getSession()->setFlash('error', $exception->getMessage());
      $url = $this->router->generate('fos_user_security_login');

      return new RedirectResponse($url);
    }
  }

}

