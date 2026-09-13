<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class BiController extends AbstractController{
  #[Route('/bi', name:'app_bi_index')]
  public function index(): Response{ return $this->render('bi/index.html.twig'); }
}
