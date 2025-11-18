<?php

namespace Dawan\Services;

use Dawan\Entity\Teaching\Training;
use Dompdf\Dompdf;
use Dompdf\Options;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

readonly class PDFGenerator
{
    public function __construct(
        private FilesystemOperator $filesStorage,
        private Environment $twig,
        private RequestStack $stack,
    ) {
    }

    /**
     * Convert an HTML file to PDF file.
     */
    public function convertHtmlFileToPdf(string $fileName, string $path): bool
    {
        if (!$this->filesStorage->fileExists($path.DIRECTORY_SEPARATOR.$fileName)) {
            return false;
        }

        $pdfFileName = pathinfo($fileName, PATHINFO_FILENAME).'.pdf';

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true)
            ->set('isRemoteEnabled', true);

        $domPdf = new Dompdf($options);
        $domPdf->setBaseHost($this->stack->getMainRequest()->getHttpHost())
            ->setProtocol('https://')
            ->setPaper('A4')
            ->loadHtml($this->filesStorage->read($path.DIRECTORY_SEPARATOR.$fileName), 'UTF-8');
        $domPdf->render();

        try {
            $this->filesStorage->write($path.DIRECTORY_SEPARATOR.$pdfFileName, $domPdf->output());

            return true;
        } catch (FilesystemException) {
            return false;
        }
    }

    public function generateTrainingPdf(Training $training): string
    {
        // Dompdf config
        $pdfOption = new Options();
        $pdfOption->set('isHtml5ParserEnabled', true);
        $pdfOption->set('isRemoteEnable', true);

        // Dompdf instance with options
        $domPdf = new Dompdf($pdfOption);
        $domPdf->setBaseHost($this->stack->getMainRequest()->getHttpHost())
            ->setProtocol('https://')
            ->setPaper('A4');

        $html = $this->twig->render('teaching/training/pdf.html.twig', [
            'training' => $training,
        ]);

        // paper type option
        $domPdf->setPaper('A4');
        $domPdf->loadHtml($html);

        // pdf render
        $domPdf->render();

        return $domPdf->output();
    }
}
