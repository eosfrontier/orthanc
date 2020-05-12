<?php 

if(empty($post["id"]) || empty($post["meta"])){
    //HAVEN'T ANSWERED A WAY TO ACCESS
    http_response_code(400);
    echo json_encode("You haven't included a 'id'.");
    die();
}

$id     = $post["id"];
$meta   = $post["meta"];

$aResult = $cMeta->deleteMeta($id, $meta);

if(!empty($aResult)){
    http_response_code(200);
    echo json_encode($aResult);
}else{
    http_response_code(404);
    echo "No result found";
}
die();
