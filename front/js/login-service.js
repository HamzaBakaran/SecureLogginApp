
var UserLoginService = {
  init: function(){
    var token = localStorage.getItem("token");
    if (token){
      window.location.replace("index.html");
    }
    $('#login-form').validate({
      submitHandler: function(form) {
        var entity = Object.fromEntries((new FormData(form)).entries());
        UserLoginService.login(entity);
      }
    });
  },
  login: function(entity){
    $('.login-button').attr('disabled', true);
    $.ajax({
      url: '../api/login',
      type: 'POST',
      data: JSON.stringify(entity),
      contentType: "application/json",
      dataType: "json",
      success: function(result) {
        console.log(result);
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
        if (XMLHttpRequest.status === 403) {
          // Handle 403 Forbidden (maximum login attempts exceeded)
          toastr.error("Maximum login attempts exceeded");
          window.location.replace("loginCaptcha.php");
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
};

$(document).ready(function() {
  var logInToken = localStorage.getItem("logInToken");
  if (logInToken){
    var id = parse_jwt(logInToken).id;
    $('#otp-form').validate({
      submitHandler: function(form) {
        var otp = $('input[name="otp"]').val();
        UserLoginService.verifyOTP(id, otp);
      }
    });
    $('#sms-form').validate({
      submitHandler: function(form) {
        var sms = $('input[name="sms"]').val();
        UserLoginService.verifySMS(id, sms);
      }
    });
   
  }
});

UserLoginService.verifyOTP = function(id, otp) {
  $.ajax({
    url: '../api/' + id + '/' + otp,
    type: 'GET',
    contentType: "application/json",
    dataType: "json",
    success: function(result) {
      console.log(result);
      var logInToken = localStorage.getItem("logInToken");
      //localStorage.setItem("token", parse_jwt(logInToken));
      //var token=logInToken;
      localStorage.setItem("token", result.token);
      //var token += logInToken; 
      window.location.replace("index.html");
    },
    error: function(XMLHttpRequest, textStatus, errorThrown) {
      if (XMLHttpRequest.status === 400) {
        // Handle 404 Not Found (invalid OTP)
        toastr.error("Invalid OTP");
      } else {
        // Handle other errors
        toastr.error("An error occurred. Please try again later.");
      }
    }
  });
};
  UserLoginService.verifySMS = function(id, sms) {
    $.ajax({
      url: '../api/verifySms/'+parse_jwt(localStorage.getItem('logInToken')).id+'/'+sms,
      type: 'GET',
      contentType: "application/json",
      dataType: "json",
      success: function(result) {
        console.log(result);
        var logInToken = localStorage.getItem("logInToken");
        //localStorage.setItem("token", parse_jwt(logInToken));
        //var token=logInToken;
        localStorage.setItem("token", result.token);
        //var token += logInToken; 
        window.location.replace("index.html");
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
        if (XMLHttpRequest.status === 400) {
          // Handle 404 Not Found (invalid OTP)
          toastr.error("Invalid SMS code");
        } else {
          // Handle other errors
          toastr.error("An error occurred. Please try again later.");
        }
      }
    });
  
};

