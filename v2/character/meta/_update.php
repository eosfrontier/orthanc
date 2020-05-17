<?php 

if(empty($input["id"])){
    //HAVEN'T ANSWERED A WAY TO ACCESS
    http_response_code(400);
    echo json_encode("You haven't included a 'id'.");
    die();
}

if(empty($input["meta"])){
    http_response_code(400);
    echo json_encode("You haven't included meta.");
    die();
}

$id     = $input["id"];
$metas  = $input["meta"];

$aResult = $cMeta->updateMeta($id, $metas);
    http_response_code(200);
    echo json_encode($aResult);
die();

?>
