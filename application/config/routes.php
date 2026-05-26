<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['converter']   = 'pdf_converter/index';
$route['convertpdf_converter/convert_to_excel']     = 'pdf_converter/convert_to_excel';
$route['default_controller'] = 'pdf_converter/index';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;