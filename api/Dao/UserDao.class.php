<?php

require_once __DIR__.'/BaseDao.class.php';
require '../vendor/autoload.php';
require_once __DIR__.'/../../config_default.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtilException;

use Ramsey\Uuid\Uuid;

use OTPHP\TOTP;

use GuzzleHttp\Client;

use RandomLib\Factory;

error_reporting(E_ALL);
ini_set('display_errors', true);

class UserDao extends BaseDao{
  /**
  * constructor of dao class
  */
  public function __construct(){
    parent::__construct("users");
  }

  public function registerUser($username,$email, $password,$phoneNumber) {
    // Validate username
    if ($username === null ||  strlen($username) < 4 || !ctype_alnum($username)) {
        return ['error' => 'Invalid username format.'];
    }

    // Check if username already exists
    $existingUser = $this->get_user_by_username($username);
    $existingEmail= $this->get_user_by_email($email);
    $existingPhoneNumber=$this->get_user_by_phoneNumber($phoneNumber);
    if ($existingUser) {
        return ['error' => 'Username already exists.'];
    }
    elseif($existingEmail)
    {
      return ['error' => 'Email is already used'];
    }
    elseif($existingPhoneNumber)
    {
      return ['error' => 'Phonenumber is already used'];
    }
    elseif($this->checkPassword($password))
    {
      return ['error' => 'Password is breached or it is shorter than 8 characters try another one'];
    }
    elseif($this->checkEmail($email))
    {
      return ['error' => 'Email does not exist'];
    }
    elseif($this->checkPhoneNumber($phoneNumber))
    {
      return ['error' => 'Phone number is not possiable '];
    }
    $isVerified=0;
    $token = Uuid::uuid4()->toString();
    $otp=$this->generateOtp();
    $loginAttempts=0;
    $forgotAttempts=0;
    // Insert new user into the database
    $user = ['username' => $username, 'email' => $email,'password' => $password,'phoneNumber' => $phoneNumber,'token'=> $token,'isVerified'=>$isVerified,'otp'=>$otp,'loginAttempts'=>$loginAttempts,'forgotAttempts'=>$forgotAttempts];
    $this->add($user);
    $otpQR = TOTP::createFromSecret($otp);
    $otpQR->setLabel($username.'SSSD');
    $grCodeUri = $otpQR->getQrCodeUri(
    'https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M',
    '[DATA]'
);
//echo "<img src='{$grCodeUri}'>";
      // Send confirmation email
      $subject = 'Registration Confirmation';
      //$body = 'Thank you for registering! Your account has been successfully created.';
      $body = 'Click the following link to verify your email: ' . $this->getVerificationLink($token) . "<br>"."Scan QR code on Google Authenticator APP to be able to log in with otp code"."<br><img src='{$grCodeUri}'>";
      $this->sendEmail($email, $username, $subject, $body);
      

    return ['success' => 'User registered successfully.'];
}


  public function get_user_by_email($email){
     return $this->query_unique("SELECT * FROM users WHERE email = :email", ['email' => $email]);
   }
   public function get_smsCode_by_id($id){
    $result= $this->query_unique("SELECT smsCode FROM users WHERE id = :id", ['id' => $id]);
    return $result['smsCode']; 
  }
  
   public function get_phoneNumber_by_id($id){
    $result= $this->query_unique("SELECT phoneNumber FROM users WHERE id = :id", ['id' => $id]);
    return $result['phoneNumber']; 
  }
  public function get_user_by_resetToken($resetToken){
    $userRecord= $this->query_unique("SELECT * FROM users WHERE resetToken = :resetToken", ['resetToken' => $resetToken]);
    if ($userRecord) {
      return $userRecord; // return the user record as an array
  } else {
      return false; // return false if the token is not found or invalid
  }
    
  }
  public function set_phoneNumber_by_id($id,$smsCode){
    return $this->query_unique("UPDATE users SET smsCode=:smsCode WHERE id = :id", ['smsCode' => $smsCode,'id' => $id]);
  }
  public function set_password_by_id($id, $password)
{
    $this->query("UPDATE users SET password = :password WHERE id = :id", ['password' => $password, 'id' => $id]);
    return true; // Return a success indicator if the query executed successfully
}

   public function get_user_by_username($username){
    return $this->query_unique("SELECT * FROM users WHERE username = :username", ['username' => $username]);
  }
  public function get_user_by_phoneNumber($phoneNumber){
    return $this->query_unique("SELECT * FROM users WHERE phoneNumber = :phoneNumber", ['phoneNumber' => $phoneNumber]);
  }
  public function get_userOtp_by_token($token){
    return $this->query_unique("SELECT otp FROM users WHERE token = :token", ['token' => $token]);
  }
  public function get_userOtp_by_id($id) {
    $result = $this->query_unique("SELECT otp FROM users WHERE id = :id", ['id' => $id]);
    
    if (is_array($result)) {
        $result = reset($result); // Get the first value of the array
    }
    
    return (string) $result;
}
public function get_user_by_id($id) {
  return  $this->query_unique("SELECT * FROM users WHERE id = :id", ['id' => $id]);
  
}

  public function checkPassword($password)
  {
  $hashed_password = strtoupper(sha1($password));
  $first5characters = substr($hashed_password,0,5);
  //echo $first5characters;
  $otherCharacters = substr($hashed_password, 5, strlen($hashed_password));
  $response = file_get_contents("https://api.pwnedpasswords.com/range/".$first5characters);
  if(strpos($response, $otherCharacters) ||  strlen($password) < 8 ) {
      return TRUE;
  } else {
      return FALSE;
  }
  }
  public function checkEmail($email)
  {

  $split=explode("@",$email);
  $hostname=$split[1];

  getmxrr($hostname,$hosts);

  //print_r($hosts);

  if(count($hosts)==0)
  {
  return TRUE;
  }
  else
  {
    return FALSE;
  }

}
public function checkPhoneNumber($phoneNumber)
{
    $phoneNumberUtil = PhoneNumberUtil::getInstance();
    $defaultRegion = 'US';

    try {
        $parsedNumber = $phoneNumberUtil->parse($phoneNumber, $defaultRegion );
        $isValidNumber = $phoneNumberUtil->isValidNumber($parsedNumber);
        $isPossibleNumber = $phoneNumberUtil->isPossibleNumber($parsedNumber);
        $numberType = $phoneNumberUtil->getNumberType($parsedNumber);

        if ($isValidNumber && $isPossibleNumber && $numberType !== PhoneNumberType::FIXED_LINE) {
            return false;
        }
    } catch (PhoneNumberUtilException $e) {
        // Handle any exceptions thrown by the library, such as an invalid phone number format
        // You can log the error or perform any other necessary actions here
    }

    return true;
}


public function sendEmail($toEmail, $toName, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration for Gmail
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username   = SMTP_USERNAME;                     //SMTP username
        $mail->Password   = SMTP_PASSWORD; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom('from@example.com', 'Mailer');
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo('info@example.com', 'Information');

    

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
private function getVerificationLink($token) {
  $hostname=gethostname();
  // Return the URL of the verification endpoint with the verification token as a parameter
  $rootFolder = basename($_SERVER['DOCUMENT_ROOT']);
  $projectFolder = basename(dirname(dirname(__DIR__)));

  return 'http://'.$hostname.'/'.$projectFolder.'/api/verify/' . $token;
}
public function verifyEmail($token) {
  $user = $this->query_unique("SELECT * FROM users WHERE token = :token", ['token' => $token]);
  var_dump($user);

  if ($user) {
      // Update the user's email verification status in the database
      $this->query_unique("UPDATE users SET isVerified = 1 WHERE id = :id", ['id' => $user['id']]);

      return ['success' => 'Email verified successfully.'];
  } else {
      return ['error' => 'Invalid verification token.'];
  }
}
public function generateOtp()
{


// A random secret will be generated from this.
// You should store the secret with the user for verification.

$otp = TOTP::generate();
//echo "The OTP secret is: {$otp->getSecret()}\n";
$secret=$otp->getSecret();
//echo $secret;
return $secret;
}
public function showOtp($secret)
{

// Note: use your own way to load the user secret.
// The function "load_user_secret" is simply a placeholder.
//$secret = "HYHSPCGRZRX6O4TO3SC2URB7BMO6MYBZLH3DC5VLOH63K5RY4S6EPO3XDLFEIK4DLBKZTWPHNEN46S7LNZCLHQSVKHWLUC7BUVQ2VHI";
$otp = TOTP::createFromSecret($secret);

//echo "The current OTP is: {$otp->now()}\n";




// Note: You must set label before generating the QR code
$otp->setLabel('SSSD Project');
$grCodeUri = $otp->getQrCodeUri(
    'https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=300x300&ecc=M',
    '[DATA]'
);
echo "<img src='{$grCodeUri}'>";
}
public function getOtp($secret)
{

$otp = TOTP::createFromSecret($secret);
return $otp->now();



}
/*
public function checkOtp($id,$number)
{
$otp=$this->get_userOtp_by_id($id);


$checkNumber = TOTP::createFromSecret($otp);

if($checkNumber==$number)
{
  return ['success' => 'Otp verified successfully.'];
} else {
    return ['error' => 'Invalid verification otp.'];
}
}
*/
public function checkOtp($id, $number)
{
    // Retrieve the OTP secret for the given user ID
    $otpSecret = $this->get_userOtp_by_id($id);

    if (!$otpSecret) {
        return ['error' => 'User not found.'];
    }

    // Create a TOTP instance using the OTP secret
    $otp = TOTP::createFromSecret($otpSecret);

    $current_time = date('Y-m-d H:i:s');

    // Validate the input parameters
    if (empty($number)) {
        return ['error' => 'OTP is required.'];
    }

    try {
        // Verify the provided OTP
        $isValid = $otp->verify($number);

        if ($isValid) {
            return ['success' => 'OTP verified successfully.'];
        } else {
          
          echo $current_time;
            return ['error' => 'Invalid OTP'];

        }
    } catch (Exception $e) {
        return ['error' => 'An error occurred during OTP verification.'];
    }
}
public function incrementLoginAttempts($id) {


  $this->query_unique("UPDATE users SET loginAttempts = loginAttempts + 1 WHERE id = :id;", ['id' => $id]);
}
public function resetLoginAttempts($id) {
  

  $this->query_unique("UPDATE users SET loginAttempts = 0 WHERE id = :id;", ['id' => $id]);
}
public function getLoginAttempts($id) {
  

  return $this->query_unique("SELECT  loginAttempts FROM users WHERE id = :id;", ['id' => $id]);
}
public function incrementForgotAttempts($id) {


  $this->query_unique("UPDATE users SET forgotAttempts = forgotAttempts + 1 WHERE id = :id;", ['id' => $id]);
}
public function resetForgotAttempts($id) {
  

  $this->query_unique("UPDATE users SET forgotAttempts = 0 WHERE id = :id;", ['id' => $id]);
}
public function getForgotAttempts($id) {
  

  return $this->query_unique("SELECT  forgotAttempts FROM users WHERE id = :id;", ['id' => $id]);
}
public function checkCaptcha($hCaptchaResponse)
{


  $data = array(
    'secret' => "0xAc5eBC681c3E73ba239cbb22014ca17b9152e9C0",
    'response' => $hCaptchaResponse
);
$verify = curl_init();
curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
curl_setopt($verify, CURLOPT_POST, true);
curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($verify);
//$var_dump($response);
$responseData = json_decode($response);
if($responseData->success) {
// your success code goes here
print_r("succes");
} 
else {
// return error to user; they did not pass
print_r("fail");
}
/*

if ($response->getStatusCode() === 200) {
    $responseData = json_decode($response->getBody());
    if ($responseData->success) {
        // Success code goes here
        echo "Success";
        return TRUE;
    } else {
        // Error code goes here
        return FALSE;
    }
} 
/*else {
    // Error handling
    echo "Error retrieving response";
}
*/
}

public function sendSMS($number, $message)
{
    $client = new Client();

    $api_key = TEXT_MESSAGE_API_KEY; 
    $api_secret = TEXT_MESSAGE_SECRET; // Replace with your actual Nexmo API secret
    $from = 'Vonage APIs'; // URL-encoded sender ID

    $url = 'https://rest.nexmo.com/sms/json';

    $response = $client->post($url, [
        'form_params' => [
            'from' => $from,
            'text' => $message,
            'to' => $number,
            'api_key' => $api_key,
            'api_secret' => $api_secret
        ]
    ]);

    $statusCode = $response->getStatusCode();
    $body = $response->getBody();

    echo "Status code: $statusCode\n";
    echo "Response body: $body\n";
}

/*
function sendSMS($number, $message)
{
    $client = new Client();

    $from = 'Your Sender ID'; // The name or number the message should be sent from
    $text = $message; // The body of the message being sent
    $to = $number; // The number that the message should be sent to
    $username = 'Your Database Username'; // Your database username
    $access_token = hash('sha384', strtolower($username)); // Hashing and transforming the username to lowercase

    $url = 'https://sssd-2022.adnan.dev/api/sms';

    try {
        $response = $client->post($url, [
            'headers' => [
                'User-Agent' => 'SSSDBot/1.0.0' // Set the required user agent header
            ],
            'form_params' => [
                'from' => $from,
                'text' => $text,
                'to' => $to,
                'username' => $username,
                'access_token' => $access_token
            ]
        ]);

        $statusCode = $response->getStatusCode();
        $body = $response->getBody();

        echo "Status code: $statusCode\n";
        echo "Response body: $body\n";
    } catch (RequestException $e) {
        // Handle the exception if the request fails
        echo "Request failed: " . $e->getMessage();
    }
}
*/


public function generateSMSCode() {
  $factory = new Factory();
  $generator = $factory->getMediumStrengthGenerator();
  $code = $generator->generateString(6, '0123456789');
  return $code;
}


public function generateResetToken()
{
    // Generate a unique token using uniqid() function
    $token = uniqid();

    return $token;
}
public function storeResetToken($id, $resetToken, $expirationDate) {
  $this->query_unique(
      "UPDATE users SET resetToken = :resetToken, expirationDate = :expirationDate WHERE id = :id;",
      ['resetToken' => $resetToken, 'expirationDate' => $expirationDate, 'id' => $id]
  );
}
public function sendResetEmail($toEmail, $resetToken) {
  $hostname=gethostname();
  $projectFolder = basename(dirname(dirname(__DIR__)));
  // Return the URL of the verification endpoint with the verification token as a parameter
  $subject = 'Password Reset';
  $body = 'Click the following link to reset your password: <a href="http://' . $hostname . '/'.$projectFolder.'/front/forgotpassword.html?token=' . $resetToken . '">Reset Password</a>';

  
  $this->sendEmail($toEmail, 'Recipient Name', $subject, $body);
}



}




  
  
  



?>
