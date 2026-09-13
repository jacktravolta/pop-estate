<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class ChatController extends AbstractController{
  #[Route('/chat', name:'app_chat_index')]
  public function index(): Response{ return $this->render('chat/index.html.twig'); }
}
