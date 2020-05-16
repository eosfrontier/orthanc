<?php
require_once($_SERVER['DOCUMENT_ROOT'] . 'orthanc/includes/include.php');
require_once($_SERVER['DOCUMENT_ROOT'] . 'orthanc/includes/token.php');

$cFigurant = new figurant();

switch ($method) {
    case 'DELETE':
      require_once './_delete.php';
      break;
    case 'POST':
      require_once './_post.php';
      break;
    case 'PUT':
      require_once './_update.php';;  
      break;
    case 'GET':
      require_once './_get.php';
      break;
    default:
      require_once './_get.php';
      break;
  }

?>