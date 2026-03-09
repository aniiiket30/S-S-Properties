<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

session_start();

if(isset($_SESSION['user_id'])) {
    echo json_encode(array(
        "success" => true,
        "logged_in" => true,
        "user_id" => $_SESSION['user_id'],
        "user_email" => $_SESSION['user_email'],
        "user_name" => $_SESSION['user_name'],
        "user_type" => $_SESSION['user_type']
    ));
} else {
    echo json_encode(array(
        "success" => true,
        "logged_in" => false,
        "message" => "Not logged in"
    ));
}
?>