<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config_default.php';

$inputData = file_get_contents('php://input');
$requestData = json_decode($inputData);

$data = array(
    'secret' => HCAPTCHA_SERVER_SECRET,
    'response' => $requestData->{'h-captcha-response'}
);

$verify = curl_init();
curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
curl_setopt($verify, CURLOPT_POST, true);
curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($verify);
$responseData = json_decode($response);

if ($responseData->success) {
    // Your success code goes here
    //echo "success";
    $successResponse = array(
        'success' => true,
        'message' => 'Captcha verification successful'
    );
    echo json_encode($successResponse);
} else {
    // Return error to the user; they did not pass
    //echo "fail";
    $errorResponse = array(
        'success' => false,
        'message' => 'Captcha verification failed'
    );
    echo json_encode($errorResponse);
}
