var UserLoginCaptchaService = {
  init: function(){
    var token = localStorage.getItem("token");
    if (token){
      window.location.replace("index.html");
    }
    $('#login-captcha-form').validate({
      submitHandler: function(form) {
        var entity = {
          email: $('[name="email"]').val(),
          password: $('[name="password"]').val(),
          'h-captcha-response': $('[name="h-captcha-response"]').val()
        };

        UserLoginCaptchaService.login(entity);
      }
    });
  },

  login: function(entity) {
    $('.login-button').attr('disabled', true);
    $.ajax({
      url: '../api/captcha.php',
      type: 'POST',
      data: JSON.stringify(entity),
      contentType: "application/json",
      dataType: "json",
      success: function(result) {
        console.log(result);

        // Check the response for captcha verification success
        if (result.success) {
          // Captcha verification successful, proceed to login with credentials
          $.ajax({
            url: '../api/logincaptcha',
            type: 'POST',
            data: JSON.stringify(entity),
            contentType: "application/json",
            dataType: "json",
            success: function(result) {
              //console.log(result);
              localStorage.setItem("logInToken", result.token);
              //window.location.replace("otp.html");
              var authenticationMethod = $('#authentication-method').val();
              if (authenticationMethod === 'sms') {
                $.ajax({
                  url: '../api/sms/'+ parse_jwt(localStorage.getItem('logInToken')).id,
                  type: 'GET',
                  contentType: "application/json",
                  dataType: "json",
                  success: function(response) {
                    // Handle the response from api/sms/parseJWT endpoint
                    //console.log(response);
                    window.location.replace("sms.html");
                    // Perform any necessary actions based on the response
                  },
                  error: function(XMLHttpRequest, textStatus, errorThrown) {
                    // Handle error if the AJAX call to api/sms/parseJWT fails
                    toastr.error("An error occurred while parsing JWT for SMS authentication.");
                  }
                });
                window.location.replace("sms.html");
              } else if (authenticationMethod === 'otp') {
                window.location.replace("otp.html");
              } else {
                toastr.error("Invalid authentication method");
                $('.login-button').attr('disabled', false);
              }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
              if (XMLHttpRequest.status === 404) {
                // Handle 404 Not Found (wrong password)
                toastr.error("Wrong password");
              } else {
                // Handle other errors
                var response = JSON.parse(XMLHttpRequest.responseText);
                var errorMessage = response && response.error ? response.error : "An error occurred. Please try again later.";
                toastr.error(errorMessage);
                //toastr.error("An error occurred. Please try again later.");
              }
              $('.login-button').attr('disabled', false);
            }
          });
        } else {
          // Captcha verification failed
          toastr.error("Captcha verification failed");
          console.log(entity);
          $('.login-button').attr('disabled', false);
        }
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
        // Handle error in captcha verification request
        toastr.error("An error occurred. Please try again later.");
        $('.login-button').attr('disabled', false);
      }
    });
  },

  logout: function(){
    localStorage.clear();
    window.location.replace("login.html");
  },
};