<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use thiagoalessio\TesseractOCR\TesseractOCR;

class Pdf_converter extends CI_Controller
{
    public function index()
    {
        $this->load->view('converter_view');
    }

    public function convert_to_excel()
    {
        /*
         * Put your PDF here:
         * /uploads/pdf/1005 Store Dispo 2026-05-15.pdf
         */
        $pdf_path = FCPATH . 'uploads/pdf/1005 Store Dispo 2026-05-15.pdf';

        if (!file_exists($pdf_path)) {
            show_error('PDF file not found: ' . $pdf_path);
            return;
        }

        $tmp_dir = FCPATH . 'uploads/tmp_san_ocr/';

        if (!is_dir($tmp_dir)) {
            mkdir($tmp_dir, 0777, true);
        }

        $san_rows = $this->extract_san_from_pdf($pdf_path, $tmp_dir);

        if (empty($san_rows)) {
            show_error('No SAN data extracted. Please adjust crop settings in crop_san_column().');
            return;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('SAN Data');

        $sheet->setCellValue('A1', 'Page');
        $sheet->setCellValue('B1', 'SAN');

        $row = 2;

        foreach ($san_rows as $item) {
            $sheet->setCellValue('A' . $row, $item['page']);
            $sheet->setCellValueExplicit(
                'B' . $row,
                $item['san'],
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(18);

        $filename = 'SAN_Extracted_' . date('Ymd_His') . '.xlsx';
        $filepath = $tmp_dir . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        $this->download_file($filepath, $filename);
    }

    private function extract_san_from_pdf($pdf_path, $tmp_dir)
    {
        $all_san = array();

        $imagick = new Imagick();

        /*
         * Higher resolution improves OCR accuracy.
         */
        $imagick->setResolution(250, 250);
        $imagick->readImage($pdf_path);

        $page_number = 1;

        foreach ($imagick as $page) {
            $page->setImageFormat('png');
            $page->setImageBackgroundColor('white');
            $page = $page->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

            $page_image = $tmp_dir . 'page_' . $page_number . '.png';
            $page->writeImage($page_image);

            /*
             * Crop only the first SAN column.
             */
            $san_image = $this->crop_san_column($page_image, $tmp_dir, $page_number);

            /*
             * OCR the cropped SAN column.
             */
            $ocr_text = $this->ocr_numbers_only($san_image);

            /*
             * Extract 4-digit SAN numbers.
             */
            preg_match_all('/\b\d{4}\b/', $ocr_text, $matches);

            if (!empty($matches[0])) {
                foreach ($matches[0] as $san) {
                    $all_san[] = array(
                        'page' => $page_number,
                        'san'  => $san
                    );
                }
            }

            $page_number++;
        }

        $imagick->clear();
        $imagick->destroy();

        return $all_san;
    }

    private function crop_san_column($page_image, $tmp_dir, $page_number)
    {
        $img = new Imagick($page_image);

        $width  = $img->getImageWidth();
        $height = $img->getImageHeight();

        /*
         * These crop percentages are based on your uploaded PDF layout.
         *
         * It crops the FIRST SAN column on the left side only.
         *
         * If OCR misses numbers, adjust these:
         * - $x_percent
         * - $y_percent
         * - $w_percent
         * - $h_percent
         */
        $x_percent = 0.055; // left position of first SAN column
        $y_percent = 0.155; // start below header
        $w_percent = 0.090; // width of SAN column
        $h_percent = 0.640; // height covering item rows

        $x = (int) round($width * $x_percent);
        $y = (int) round($height * $y_percent);
        $w = (int) round($width * $w_percent);
        $h = (int) round($height * $h_percent);

        $img->cropImage($w, $h, $x, $y);

        /*
         * Improve OCR readability.
         */
        $img->setImageColorspace(Imagick::COLORSPACE_GRAY);
        $img->contrastImage(1);
        $img->sharpenImage(0, 1);
        $img->resizeImage($w * 2, $h * 2, Imagick::FILTER_LANCZOS, 1);

        $cropped_path = $tmp_dir . 'san_page_' . $page_number . '.png';
        $img->writeImage($cropped_path);

        $img->clear();
        $img->destroy();

        return $cropped_path;
    }

    private function ocr_numbers_only($image_path)
    {
        /*
         * PSM 6 means assume a uniform block of text.
         * Whitelist forces OCR to read digits only.
         */
        return (new TesseractOCR($image_path))
            ->psm(6)
            ->config('tessedit_char_whitelist', "0123456789\n")
            ->run();
    }

    private function download_file($filepath, $filename)
    {
        if (!file_exists($filepath)) {
            show_error('Excel file was not generated.');
            return;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Content-Length: ' . filesize($filepath));

        readfile($filepath);
        exit;
    }
}