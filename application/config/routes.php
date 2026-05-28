<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'pdf_converter';

$route['converter'] = 'pdf_converter/index';

$route['convert_to_excel'] = 'pdf_converter/convert_to_excel';

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;