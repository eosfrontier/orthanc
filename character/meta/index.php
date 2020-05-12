<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$post = json_decode(file_get_contents('php://input'), true);

include_once "../../includes/include.php";
include_once "../../includes/token.php";

$cMeta = new meta();

/*
if(empty($post["id"])){
    //HAVEN'T ANSWERED A WAY TO ACCESS
    http_response_code(400);
    echo json_encode("You haven't included a 'id'.");
    die();
}
*/

if(isset($post["meta"])){
    $meta   = $post["meta"];
    $aMetas = explode(",", $meta);
    $meta = "";
    foreach($aMetas as $aMeta){
        $meta .= "'".$aMeta."',";
    }
    $meta = rtrim($meta, ",");

    if(isset($post["id"])) {
        $aResult = $cMeta->getByMeta($post["id"], $meta);
    } else {
        $aResult = $cMeta->getAllByMeta($meta);
    }
} else {
    if(isset($post["id"])) {
        $aResult = $cMeta->getAllMetaById($post["id"]);
    } else {
        http_response_code(400);
        echo json_encode("You haven't included a 'id' or 'meta'.");
        die();
    }
}


if(!empty($aResult)){
    http_response_code(200);
    echo json_encode($aResult);
}else{
    http_response_code(404);
    echo "No result found";
}
die();

?>
