var ForgotPasswordService = (function() {
    var init = function() {
      $('#forgot-password-form').validate({
        rules: {
          email: {
            required: true,
            email: true
          }
        },
        submitHandler: function(form) {
          var formData = $(form).serialize();
          sendForgotPasswordRequest(formData);
        }
      });
    };
  
    var sendForgotPasswordRequest = function(formData) {
      $.ajax({
        url: '../api/forgotPassword',
        type: 'POST',
        data: formData,
        success: function(response) {
          // Handle the success response
          // Display a success message or redirect to a success page
          // Handle the response from api/sms/parseJWT endpoint
          // console.log(response);
          toastr.success("Link for password reset sent ");
          window.location.replace("login.html");
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
          if (XMLHttpRequest.status === 403) {
            var response = JSON.parse(XMLHttpRequest.responseText);
            // Handle 403 Forbidden (maximum login attempts exceeded)
            // toastr.error("Maximum login attempts exceeded");
            toastr.error(response.errorMessage);
            window.location.replace("loginCaptcha.php");
          } else if (XMLHttpRequest.status === 404) {
            // Handle 404 Not Found (wrong password)
            toastr.error("Wrong password");
            $('.login-button').attr('disabled', false);
          } else {
            // Handle other errors
            var response = JSON.parse(XMLHttpRequest.responseText);
            var errorMessage = response && response.error ? response.error : "An error occurred. Please try again later.";
            toastr.error(errorMessage);
            $('.login-button').attr('disabled', false);
          }
        }
      });
    };
  
    return {
      init: init
    };
  })();
  
  
  