<?php

if (!isset($input["id"])) {
    http_response_code(400);
    echo json_encode("You haven't include an id to delete.");
    die();
} else {
    $deleteFigu = $cFigurant->deleteFigurant($input["id"]);
    if ($deleteFigu == 0) {
        http_response_code(404);
        echo json_encode("No figuranten deleted. Either specified id is not a figurant, or they were already deleted.");
        die();
    } else {
        http_response_code(200);
        echo json_encode($deleteFigu . ' figuranten deleted.');
        die();
    }
}
