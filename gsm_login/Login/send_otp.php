<?php
require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function generateOTP() {
    return sprintf('%06d', mt_rand(100000, 999999));
}

function sendOTPEmail($email, $otp) {
    $config = include 'email_config.php';
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['smtp_port'];
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'GoServePH - Your OTP Code';
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #4A90E2; color: white; padding: 20px; text-align: center;'>
                <h1>GoServePH</h1>
            </div>
            <div style='padding: 30px; background: #f9f9f9;'>
                <h2>Your OTP Code</h2>
                <div style='background: white; padding: 20px; text-align: center; margin: 20px 0;'>
                    <h1 style='color: #4A90E2; font-size: 36px; letter-spacing: 8px;'>{$otp}</h1>
                </div>
                <p>This OTP is valid for 3 minutes only.</p>
            </div>
        </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['email'])) {
        sendResponse(false, 'Email is required');
    }
    
    $email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        sendResponse(false, 'Invalid email format');
    }
    
    $action = $input['action'] ?? 'send_otp';
    
    switch ($action) {
        case 'send_otp':
            $otp = generateOTP();
            
            if (sendOTPEmail($email, $otp)) {
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_expiry'] = time() + 180;
                
                sendResponse(true, 'OTP sent successfully to your email');
            } else {
                sendResponse(false, 'Failed to send OTP');
            }
            break;
            
        case 'verify_otp':
            $submitted_otp = $input['otp'] ?? '';
            
            if (!isset($_SESSION['otp']) || time() > $_SESSION['otp_expiry']) {
                sendResponse(false, 'OTP expired');
            }
            
            if ($_SESSION['otp'] === $submitted_otp && $_SESSION['otp_email'] === $email) {
                $_SESSION['otp_verified'] = true;
                $_SESSION['verified_email'] = $email;
                sendResponse(true, 'OTP verified');
            } else {
                sendResponse(false, 'Invalid OTP');
            }
            break;
    }
}
?>