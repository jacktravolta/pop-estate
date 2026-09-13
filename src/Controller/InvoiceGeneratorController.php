<?php
namespace App\Controller;

use App\Service\InvoicePeriodGenerator;
use App\Repository\PropertyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoices/actions', priority: 20)]
class InvoiceGeneratorController extends AbstractController
{
    #[Route('/generator', name: 'app_invoice_generator', methods: ['GET'])]
    public function generator(PropertyRepository $propRepo): Response
    {
        return $this->render('invoice/generator.html.twig', [
            'properties' => $propRepo->findAll(),
        ]);
    }

    #[Route('/preview-by-period', name: 'app_invoice_preview_period', methods: ['GET'])]
    public function preview(Request $request, InvoicePeriodGenerator $gen): JsonResponse
    {
        try {
            $inicio = new \DateTime($request->query->get('inicio','2025-01-01'));
            $fin = new \DateTime($request->query->get('fin','2025-12-31'));
            $propId = $request->query->get('property')? (int)$request->query->get('property') : null;
            $data = $gen->preview($inicio, $fin, $propId);
            return $this->json([
                'ok' => true,
                'total' => $data['total'],
                'total_fmt' => '$'.number_format($data['total'],0,',','.'),
                'count' => $data['count'],
                'breakdown' => $data['breakdown'],
                'settlements' => array_map(fn($s) => [
                    'id' => $s->getId(),
                    'propiedad' => $s->getProperty()?->getDireccion(),
                    'periodo' => $s->getFechaInicio()->format('d/m/y').' - '.$s->getFechaTermino()->format('d/m/y'),
                    'total' => (float)$s->getTotal(),
                    'total_fmt' => '$'.number_format((float)$s->getTotal(),0,',','.'),
                    'estado' => $s->getEstado(),
                ], $data['settlements'])
            ]);
        } catch (\Exception $e) {
            return $this->json(['ok'=>false,'error'=>$e->getMessage()],400);
        }
    }

    #[Route('/generate-by-period', name: 'app_invoice_generate_period', methods: ['POST'])]
    public function generateByPeriod(Request $request, InvoicePeriodGenerator $gen): Response
    {
        $inicio = new \DateTime($request->request->get('inicio'));
        $fin = new \DateTime($request->request->get('fin'));
        $propId = $request->request->get('property')? (int)$request->request->get('property') : null;
        $emisor = $request->request->get('emisor','Pop Estate');
        $receptor = $request->request->get('receptor');
        try {
            $invoices = $gen->generate($inicio, $fin, $propId, $emisor, $receptor, $this->getUser());
            $this->addFlash('success', sprintf('✅ %d facturas generadas por periodo %s - %s', count($invoices), $inicio->format('d/m/Y'), $fin->format('d/m/Y')));
        } catch (\Exception $e) {
            $this->addFlash('danger', '❌ '.$e->getMessage());
        }
        return $this->redirectToRoute('app_invoice_index');
    }
}
