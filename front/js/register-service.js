var UserRegisterService = {
    init: function(){
      var token = localStorage.getItem("token");
      if (token){
        window.location.replace("index.html");
      }
      $('#register-form').validate({
        submitHandler: function(form) {
          var entity = Object.fromEntries((new FormData(form)).entries());
          UserRegisterService.register(entity);
        }
      });
    },
    register: function(entity){
      $('.login-button').attr('disabled', true);
      $.ajax({
        url: '../api/register',
        type: 'POST',
        data: JSON.stringify(entity),
        contentType: "application/json",
        dataType: "json",
        success: function(result) {
          //console.log(result);
          toastr.success("Registrated succesfully");
          window.location.replace("login.html");
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            if (XMLHttpRequest.status === 404) {
              // Handle 404 Not Found (wrong password)
              toastr.error("Wrong password");
            } else {
              // Extract the error message from the response JSON, if available
              var response = JSON.parse(XMLHttpRequest.responseText);
              var errorMessage = response && response.error ? response.error : "An error occurred. Please try again later.";
              toastr.error(errorMessage);
            }
            $('.login-button').attr('disabled', false);
        }
    
      });
      
    },
  
    logout: function(){
      localStorage.clear();
      window.location.replace("login.html");
    },
  }