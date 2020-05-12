<?php 
require_once($_SERVER["DOCUMENT_ROOT"] . '/api/orthanc/includes/include.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/api/orthanc/includes/token.php');
$cMeta = new meta();

switch ($method) {
  case 'DELETE':
    require_once './delete.php';
    break;
  case 'PUT':
    require_once './update.php';;  
    break;
  case 'GET':
    require_once './get.php';
    break;
  default:
        break;
}

?>
