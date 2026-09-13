<?php
namespace App\Controller;
use App\Entity\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvoicePdfController extends AbstractController
{
    #[Route('/invoices/{id}/pdf', name: 'app_invoice_pdf', methods: ['GET'])]
    public function pdf(Invoice $invoice): Response
    {
        $html = $this->renderView('invoice/pdf.html.twig', ['invoice' => $invoice]);
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Factura-'.($invoice->getFolio() ?? $invoice->getId()).'.pdf"'
        ]);
    }
    #[Route('/invoices/{id}/print', name: 'app_invoice_print', methods: ['GET'])]
    public function printView(Invoice $invoice): Response
    {
        return $this->render('invoice/pdf.html.twig', ['invoice' => $invoice, 'print' => true]);
    }
}
