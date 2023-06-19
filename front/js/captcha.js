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
          }
          UserLoginCaptchaService.login(entity);
        }
      });
    },
    login: function(entity){
        $('.login-button').attr('disabled', true);
        $.ajax({
          url: '../api/captcha.php',
          type: 'POST',
          data: entity,
          processData: false,
          contentType: false,
          success: function(result) {
            console.log(result);
            if (result && result.success) {
              // Captcha verification successful, proceed to login with credentials
              $.ajax({
                url: '../api/logincaptcha',
                type: 'POST',
                data: JSON.stringify(entity),
                contentType: "application/json",
                dataType: "json",
                success: function(result) {
                  console.log(result);
                  localStorage.setItem("token", result.token);
                  window.location.replace("index.html");
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                  if (XMLHttpRequest.status === 404) {
                    // Handle 404 Not Found (wrong password)
                    toastr.error("Wrong password");
                  } else {
                    // Handle other errors
                    toastr.error("An error occurred. Please try again later.");
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
  }