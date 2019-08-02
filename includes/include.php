<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


function __autoload($classname) {
    include("classes/$classname.php");
}

function token($token){
    $stmt = database::$conn->prepare("SELECT * FROM eos_tokens WHERE token = ?");
    $res = $stmt->execute(array($token));
    $res = $stmt->fetch(PDO::FETCH_ASSOC); 
    
    if($res != null){
        return "valid";
    }else{
        return false;
    }
}

?>