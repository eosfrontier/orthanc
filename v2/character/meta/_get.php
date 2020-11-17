<?php

if (isset($input["meta"])) {
    $meta   = $input["meta"];
    $aMetas = explode(",", $meta);
    $meta = "";
    foreach ($aMetas as $aMeta) {
        $meta .= "'" . $aMeta . "',";
    }
    $meta = rtrim($meta, ",");

    if (isset($input["id"])) {
        $aResult = $cMeta->getByMeta($input["id"], $meta);
    } else {
        $aResult = $cMeta->getAllByMeta($meta);
    }
} else {
    if (isset($input["id"])) {
        $aResult = $cMeta->getAllMetaById($input["id"]);
    } else {
        http_response_code(400);
        echo json_encode("You haven't included a 'id' or 'meta'.");
        die();
    }
}


if (!empty($aResult)) {
    http_response_code(200);
    echo json_encode($aResult);
} else {
    http_response_code(404);
    echo "No result found";
}
die();
