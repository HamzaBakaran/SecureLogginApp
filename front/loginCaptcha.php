<?php
// Include the config.php file
require_once '../config_default.php';
?>
   <!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="utf-8">
  <title>SSSD Project</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">
  <style>
    .error {
      color: red;
    }
  </style>
</head>

<body>
  <main>
    <div class="container marketing" style="margin-top:100px;">
      
      <div class="row">
        <form id="login-captcha-form">
            <script src='https://www.hCaptcha.com/1/api.js' async defer></script>
          <div class="mb-3">
            <label class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" value="hamzabakaran@gmail.com">
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" value="123">
          </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Select Authentication Method</label>
            <select name="method" id="authentication-method" class="form-control">
              <option value="sms">SMS</option>
              <option value="otp">OTP</option>
            </select>
          </div>
          <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>"></div>
          <button type="submit" class="btn btn-primary login-button">Login</button>
        </form>
      </div>
    </div>
  </main>
  <script src="js/jquery-3.6.0.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/jquery.validate.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
  <script src="js/login-service.js"></script>
  <script src="js/util.js"></script>
  <script src="js/login-captcha-service.js"></script>
  <script type="text/javascript">
    UserLoginCaptchaService.init();
  </script>
</body>
