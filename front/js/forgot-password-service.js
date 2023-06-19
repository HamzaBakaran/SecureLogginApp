var UserResetPasswordService = {
    init: function(){
      $('#reset-password-form').submit(function(event) {
        event.preventDefault(); // Prevent the default form submission
    
        var urlParams = new URLSearchParams(window.location.search);
        var token = urlParams.get('token');
        var password = $('input[name="password"]').val();
    
        UserResetPasswordService.resetPassword(token, password);
      });
    },
    resetPassword: function(token, password) {
      var entity = {
        token: token,
        password: password
      };
    
      $.ajax({
        url: '../api/resetPassword',
        type: 'POST',
        data: JSON.stringify(entity),
        contentType: "application/json",
        dataType: "json",
        success: function(result) {
          toastr.success("Password reset successful");
          localStorage.clear();
          window.location.replace("login.html");
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
          var response = JSON.parse(XMLHttpRequest.responseText);
          var errorMessage = response && response.message ? response.message : "An error occurred. Please try again later.";
          toastr.error(errorMessage);
        }
      });
    }
  };