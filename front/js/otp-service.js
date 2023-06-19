var OtpService = {
    init: function(){
      var token = localStorage.getItem("token");
      if (token){
        window.location.replace("index.html");
      }
      $('#otp-form').validate({
        submitHandler: function(form) {
          var entity = Object.fromEntries((new FormData(form)).entries());
          OtpService.login(entity);
        }
      });
    },
    login: function(entity){
      $('.otp-button').attr('disabled', true);
      $.ajax({
        url: '../api/',
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
          if (XMLHttpRequest.status === 403) {
            // Handle 403 Forbidden (maximum login attempts exceeded)
            toastr.error("Maximum login attempts exceeded");
            window.location.replace("loginCaptcha.html");
          } else if (XMLHttpRequest.status === 404) {
            // Handle 404 Not Found (wrong password)
            toastr.error("Wrong password");
            $('.login-button').attr('disabled', false);
          } else {
            // Handle other errors
            toastr.error("An error occurred. Please try again later.");
            $('.login-button').attr('disabled', false);
          }
        }
      });
    },
  
    logout: function(){
      localStorage.clear();
      window.location.replace("login.html");
    },
  }
  