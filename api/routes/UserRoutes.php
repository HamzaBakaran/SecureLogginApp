<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
* @OA\Post(
*     path="/login",
*     description="Login to the system",
*     tags={"users"},
*     @OA\RequestBody(description="Basic user info", required=true,
*       @OA\MediaType(mediaType="application/json",
*    			@OA\Schema(
*    				@OA\Property(property="email", type="string", example="hamzabakaran@gmail.com",	description="Email"),
*    				@OA\Property(property="password", type="string", example="123",	description="Password" )
*        )
*     )),
*     @OA\Response(
*         response=200,
*         description="JWT Token on successful response"
*     ),
*     @OA\Response(
*         response=404,
*         description="Wrong Password | User doesn't exist"
*     )
* )
*/

Flight::route('POST /login', function(){
  $login = Flight::request()->data->getData();
  $user = Flight::userDao()->get_user_by_email($login['email']);

  
  
  if ($user !== false && isset($user['id'])) {
      $id1 = (string) $user['id'];
      if (isset($user['isVerified']) && $user['isVerified'] == 0) {
        Flight::json(["message" => "Please verify your email"], 403);
        return;
      }
      
      try {
          $loginAttemptsArray = Flight::userDao()->getLoginAttempts($id1);
          var_dump($loginAttemptsArray);
          
          if (!empty($loginAttemptsArray) && isset($loginAttemptsArray['loginAttempts'])) {
              $loginAttempts = (int) $loginAttemptsArray['loginAttempts'];

              if ($loginAttempts >= 3) {
                  Flight::json(["message" => "Maximum login attempts exceeded"], 403);
                  return;
              }

              if ($user['password'] == $login['password']) {
                  unset($user['password']);
                  Flight::userDao()->resetLoginAttempts($id1);

                  //$user['iat'] = time();
                  //$user['exp'] = $user['iat'] + 20;
                  $jwt = JWT::encode($user, 'ezcb9s8UcF', 'HS256');//initialy planed to define 'ezcb9s8UcF' in config but it won work on other devices if not in config 
                  Flight::json(['token' => $jwt]);
              } else {
                  Flight::userDao()->incrementLoginAttempts($id1);
                  Flight::json(["message" => "Wrong password"], 404);
              }
          } else {
              Flight::json(["message" => "Error retrieving login attempts"], 500);
          }
      } catch (Exception $e) {
          Flight::json(["message" => "Database error: " . $e->getMessage()], 500);
      }
  } else {
      Flight::json(["message" => "User doesn't exist"], 404);
  }
});
/**
* @OA\Post(
*     path="/forgotPassword",
*     description="Forgot password",
*     tags={"users"},
*     @OA\RequestBody(description="Basic user info", required=true,
*       @OA\MediaType(mediaType="application/json",
*    			@OA\Schema(
*    				@OA\Property(property="email", type="string", example="hamzabakaran@gmail.com",	description="Email")
*        )
*     )),
*     @OA\Response(
*         response=200,
*         description="JWT Token on successful response"
*     ),
*     @OA\Response(
*         response=404,
*         description="Wrong Password | User doesn't exist"
*     )
* )
*/
Flight::route('POST /forgotPassword', function(){
  $forgot = Flight::request()->data->getData();
  $user = Flight::userDao()->get_user_by_email($forgot['email']);
  
  if ($user !== false && isset($user['id'])) {
    // Generate a password reset token
    $resetToken = Flight::userDao()->generateResetToken(); // Implement a function to generate a unique token
    
    // Store the reset token and its expiration date in the user's record in the database
    Flight::userDao()->storeResetToken($user['id'], $resetToken, time() + 3600); // Store the token for 1 hour
    
    // Send an email to the user with a link to reset the password
    Flight::userDao()->sendResetEmail($user['email'], $resetToken); // Implement a function to send the email
    
    Flight::json(['message' => 'Password reset link sent to your email'], 200);
  } else {
    Flight::json(['message' => 'User not found'], 404);
  }
});
/**
* @OA\Post(
*     path="/resetPassword",
*     description="Forgot password",
*     tags={"users"},
*     @OA\RequestBody(description="Basic user info", required=true,
*       @OA\MediaType(mediaType="application/json",
*    			@OA\Schema(
*    				@OA\Property(property="token", type="string", example="6473a9c8195ec",	description="token"),
*    				@OA\Property(property="password", type="string", example="H@mza1306",	description="password")
*        )
*     )),
*     @OA\Response(
*         response=200,
*         description="JWT Token on successful response"
*     ),
*     @OA\Response(
*         response=404,
*         description="Wrong Password | User doesn't exist"
*     )
* )
*/
// Define a route to handle the password update
Flight::route('POST /resetPassword', function(){
  $data = Flight::request()->data->getData();
  $token = $data['token'];
  $password = $data['password'];
  $user = Flight::userDao()->get_user_by_resetToken($token);
  
  if ($user === false) {
    Flight::json(['success' => false, 'message' => 'Invalid token'], 400);
    return;
  }
  $userID = $user['id'];
  
  $forgotAttemptsArray = Flight::userDao()->getForgotAttempts($userID);
  $forgotAttempts = (int) $forgotAttemptsArray['forgotAttempts'];

  if (empty($token)) {
    Flight::json(['success' => false, 'message' => 'Token is missing'], 400);
    //Flight::userDao()->incrementForgotAttempts($userID);
    return;
  }

  if (!$user) {
    //Flight::userDao()->incrementForgotAttempts($userID);
    Flight::json(['success' => false, 'message' => 'Invalid token'], 400);
    
    return;
  }

  if ($forgotAttempts >= 5) {
    Flight::json(['success' => false, 'message' => 'Too many failed attempts. Please try again later.'], 404);
    return;
  }

  // Check the password
  $isPasswordWeak = Flight::userDao()->checkPassword($password);
  if ($isPasswordWeak) {
    Flight::userDao()->incrementForgotAttempts($userID); 
    Flight::json(['success' => false, 'message' => 'Password is breached. Please choose a stronger password.'], 400);
    // Increment forgotAttempts
    return;
  }

  // Add your password update logic here
  // For example, validate the token and update the password in the database
  $isPasswordUpdated = Flight::userDao()->set_password_by_id($userID, $password);

  if (!$isPasswordUpdated) {
    Flight::json(['success' => false, 'message' => 'Failed to update password'], 500);
    return;
  }
  $subject = 'Reset password Confirmation';
  $body='Password updated successfully';
  $email=$user['email'];
  $username=$user['username'];  // Reset forgotAttempts on successful password update
  Flight::userDao()->resetForgotAttempts($userID);
  Flight::userDao()->sendEmail($email, $username, $subject, $body);

  Flight::json(['success' => true, 'message' => 'Password updated successfully']);
});
/**
* @OA\Post(
*     path="/changePassword",
*     description="Forgot password",
*     tags={"users"},
*     @OA\RequestBody(description="Basic user info", required=true,
*       @OA\MediaType(mediaType="application/json",
*    			@OA\Schema(
*    				@OA\Property(property="id", type="number", example="45",	description="id"),
*    				@OA\Property(property="password", type="string", example="H@mza1306",	description="password")
*        )
*     )),
*     @OA\Response(
*         response=200,
*         description="JWT Token on successful response"
*     ),
*     @OA\Response(
*         response=404,
*         description="Wrong Password | User doesn't exist"
*     )
* )
*/
// Define a route to handle the password update
Flight::route('POST /changePassword', function(){
  $data = Flight::request()->data->getData();
  $id = $data['id'];
  $password = $data['password'];
  $user = Flight::userDao()->get_user_by_id($id);
  
  if ($user === false) {
    Flight::json(['success' => false, 'message' => 'Invalid token'], 400);
    return;
  }
  $userID = $user['id'];
  
  if (empty($id)) {
    Flight::json(['success' => false, 'message' => 'Token is missing'], 400);
    //Flight::userDao()->incrementForgotAttempts($userID);
    return;
  }

  if (!$user) {
    //Flight::userDao()->incrementForgotAttempts($userID);
    Flight::json(['success' => false, 'message' => 'Invalid id'], 400);
    
    return;
  }
  


  // Check the password
  $isPasswordWeak = Flight::userDao()->checkPassword($password);
  if ($isPasswordWeak) {
    Flight::json(['success' => false, 'message' => 'Password is breached. Please choose a stronger password.'], 400);
    // Increment forgotAttempts
    return;
  }

  // Add your password update logic here
  // For example, validate the token and update the password in the database
  $isPasswordUpdated = Flight::userDao()->set_password_by_id($userID, $password);

  if (!$isPasswordUpdated) {
    Flight::json(['success' => false, 'message' => 'Failed to update password'], 500);
    return;
  }

  Flight::json(['success' => true, 'message' => 'Password updated successfully']);
});





/**
* @OA\Post(
*     path="/logincaptcha",
*     description="Login to the system",
*     tags={"users"},
*     @OA\RequestBody(description="Basic user info", required=true,
*       @OA\MediaType(mediaType="application/json",
*    			@OA\Schema(
*    				@OA\Property(property="email", type="string", example="hamzabakaran@gmail.com",	description="Email"),
*    				@OA\Property(property="password", type="string", example="123",	description="Password" )
*        )
*     )),
*     @OA\Response(
*         response=200,
*         description="JWT Token on successful response"
*     ),
*     @OA\Response(
*         response=404,
*         description="Wrong Password | User doesn't exist"
*     )
* )
*/

Flight::route('POST /logincaptcha', function(){
  $login = Flight::request()->data->getData();
  $user = Flight::userDao()->get_user_by_email($login['email']);

  if (isset($user['id'])){
    $id1 = (string) $user['id'];
    if (isset($user['isVerified']) && $user['isVerified'] == 0) {
      Flight::json(["message" => "Please verify your email "], 403);
      return;
    }
       if($user['password'] == $login['password']){
          unset($user['password']);
          Flight::userDao()->resetLoginAttempts($id1);

          //$user['iat'] = time();
          //$user['exp'] = $user['iat'] + 20;
         $jwt = JWT::encode($user,JWT_SECRET, 'HS256');
         Flight::json(['token' => $jwt]);
       }else{
         Flight::json(["message" => "Wrong password"], 404);
       }
     }else{
       Flight::json(["message" => "User doesn't exist"], 404);
     }

});
Flight::route('POST /checkCaptcha', function(){
  $data = Flight::request()->data->getData();
/*
  print_r($data);
  Flight::json(['data' => $data['h-captcha-response']]);
  
  */
  $captcha = (string) $data['h-captcha-response'];
  $result=Flight::userDao()->checkCaptcha($captcha);

  if($result)
  {
    Flight::json(['sucess' => "Verified"]);
  }
  else
  {
    Flight::json($result,404);
  }
 



});

/**
 * @OA\Get(path="/users", tags={"users"}, security={{"ApiKeyAuth": {}}},
 *         summary="Return all user  from the API. ",
 *         @OA\Response( response=200, description="List of notes.")
 * )
 */

/**
* List all todos
*/
Flight::route('GET /users', function(){
  Flight::json(Flight::userService()->get_all());
});
/**
 * @OA\Get(path="/users/{id}", tags={"users"}, security={{"ApiKeyAuth": {}}},
 *     @OA\Parameter(in="path", name="id", example=1, description="Id of user"),
 *     @OA\Response(response="200", description="Fetch individual note")
 * )
 */


/**
* List invidiual todo
*/
Flight::route('GET /users/@id', function($id){
  Flight::json(Flight::userService()->get_by_id($id));
});

/**
* add todo
*/
/**
* @OA\Post(
*     path="/register",
*     description="Proba",
*     tags={"users"},
*     @OA\RequestBody(description="Basic user info", required=true,
*       @OA\MediaType(mediaType="application/json",
*    			@OA\Schema(
*    				@OA\Property(property="username", type="string", example="user1",	description="Username"),
*    				@OA\Property(property="email", type="string", example="imeprezime@gmail.com",	description="Email"),
*           @OA\Property(property="phoneNumber", type="string", example="38761123456",	description="Phone number"),
*    				@OA\Property(property="password", type="string", example="Test12345678",	description="Password" )
*        )
*     )),
*     @OA\Response(
*         response=200,
*         description="JWT Token on successful response"
*     ),
*     @OA\Response(
*         response=404,
*         description="Wrong Password | User doesn't exist"
*     )
* )
*/
Flight::route('POST /register', function(){
  $request = Flight::request();
  $username = $request->data['username'];
  $email=$request->data['email'];
  $password = $request->data['password'];
  $phoneNumber = $request->data['phoneNumber'];
  

  // Retrieve UserDao instance
  $userDao = Flight::userDao();

  // Register the user
  $result = $userDao->registerUser($username,$email, $password,$phoneNumber);

  if (isset($result['error'])) {
      // Handle error
      Flight::json($result, 400);
  } else {
      // Handle success
      Flight::json($result, 201);
  }
});
/**
 * @OA\Get(path="/verify/{token}", tags={"users"}, security={{"ApiKeyAuth": {}}},
 *     @OA\Parameter(in="path", name="token", example="319046f1-d4fe-40d1-ab50-72c799f74dbd", description="token"),
 *     @OA\Response(response="201", description="sucess")
 * )
 */
Flight::route('GET /verify/@token', function($token){
  // Verify the email using the provided token
  
  // Update the user's email verification status in the database
  // Retrieve UserDao instance
  $userDao = Flight::userDao();

  // Register the user
  $result = $userDao->verifyEmail($token);

  if (isset($result['error'])) {
      // Handle error
      Flight::json($result, 400);
  } else {
      // Handle success
      Flight::json($result, 201);
      /*
      $otpArray = $userDao->get_userOtp_by_token($token);
      $otp = $otpArray['otp'];
      $userDao->showOtp($otp);
      */
  }
});
/**
 * @OA\Get(path="/sms/{id}", tags={"users"}, security={{"ApiKeyAuth": {}}},
 *     @OA\Parameter(in="path", name="id", example="47", description="id"),
 *     @OA\Response(response="201", description="sucess")
 * )
 */

Flight::route('GET /sms/@id', function($id){
  // Verify the email using the provided token
  
  // Update the user's email verification status in the database
  // Retrieve UserDao instance
  $id1 = (string)$id;
  $smsCode= Flight::userDao()->generateSMSCode();
  $phoneNumber=Flight::userDao()->get_phoneNumber_by_id($id1);
  

  Flight::userDao()->set_phoneNumber_by_id($id1,$smsCode);

  Flight::userDao()->sendSMS($phoneNumber,$smsCode);

  $userDao = Flight::userDao();


});
/**
 * @OA\Get(
 *     path="/{id}/{otp}",
 *     tags={"users"},
 *     security={{"ApiKeyAuth": {}}},
 *     @OA\Parameter(in="path", name="id", example="25", description="user id"),
 *     @OA\Parameter(in="path", name="otp", example="123456", description="otp"),
 *     @OA\Response(response="201", description="success")
 * )
 */

  Flight::route('GET /@id/@otp', function($id,$otp){
    // Verify the email using the provided token
    
    // Update the user's email verification status in the database
    // Retrieve UserDao instance
    $userDao = Flight::userDao();
    /*
    $idArray = $id['id'];
    $otpArray = $otp['otp'];
    */
    // Convert id and otp to strings
    $id1 = (string)$id;
    $otp1 = (string)$otp;
  
    // Register the user
    $result = $userDao->checkOtp($id1,$otp);
  
    if (isset($result['error'])) {
        // Handle error
        Flight::json($result, 400);
    } else {
        // Handle success
        Flight::json($result, 201);
        /*
        $otpArray = $userDao->get_userOtp_by_token($token);
        $otp = $otpArray['otp'];
        $userDao->showOtp($otp);
        */
    }
  
  
});
/**
 * @OA\Get(
 *     path="/verifySMS/{id}/{smsCode}",
 *     tags={"users"},
 *     security={{"ApiKeyAuth": {}}},
 *     @OA\Parameter(in="path", name="id", example="25", description="user id"),
 *     @OA\Parameter(in="path", name="smsCode", example="123456", description="smsCode"),
 *     @OA\Response(response="201", description="success")
 * )
 */

 Flight::route('GET /verifySMS/@id/@smsCode', function($id,$smsCode){
  // Convert id and otp to strings
  $id1 = (string)$id;
  $sms1 = (string)$smsCode;

  // Retrieve UserDao instance
  $userSMSCode = Flight::userDao()->get_smsCode_by_id($id1);
  
  if ($sms1 == $userSMSCode) {
    Flight::json(["message" => "Success"], 200);
  } else {
    Flight::json(["message" => "Error"], 400);
  }



});


/**
* update user
*/
/**
* @OA\Put(
*     path="/users/{id}", security={{"ApiKeyAuth": {}}},
*     description="Update user",
*     tags={"users"},
*     @OA\Parameter(in="path", name="id", example=1, description="Note ID"),
*     @OA\RequestBody(description="Basic note info", required=true,
*       @OA\MediaType(mediaType="application/json",
*    			@OA\Schema(
*    				@OA\Property(property="name", type="string", example="Ime",	description="Name"),
*    				@OA\Property(property="surname", type="string", example="Prezime",	description="description"),
*    				@OA\Property(property="email", type="string", example="imeprezime@gmail.com",	description="Email"),
*           @OA\Property(property="phoneNumber", type="string", example="38761123456",	description="Phone number"),
*    				@OA\Property(property="password", type="string", example="81dc9bdb52d04dc20036dbd8313ed055",	description="Password" )
*        )
*     )),
*     @OA\Response(
*         response=200,
*         description="User that has been updated"
*     ),
*     @OA\Response(
*         response=500,
*         description="Error"
*     )
* )
*/
Flight::route('PUT /users/@id', function($id){
  $data = Flight::request()->data->getData();
  //$data['id'] = $id;
  Flight::json(Flight::userService()->update(Flight::get('user'), $id, $data));;

});

/**
* delete todo
*/
/**
* @OA\Delete(
*     path="/users/{id}", security={{"ApiKeyAuth": {}}},
*     description="Delete ",
*     tags={"users"},
*     @OA\Parameter(in="path", name="id", example=5, description="User ID"),
*     @OA\Response(
*         response=200,
*         description="User deleted"
*     ),
*     @OA\Response(
*         response=500,
*         description="Error"
*     )
* )
*/
Flight::route('DELETE /users/@id', function($id){
  Flight::userService()->delete($id);
  Flight::json(["message" => "deleted"]);
});






?>
