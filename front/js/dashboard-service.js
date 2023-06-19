var DashboardService = {
  init: function(){
    DashboardService.get_name();
    DashboardService.setupForgotPasswordModal();
  },

  get_name: function(){
    $("#name").html(`<a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" id="name"> Welcome `+ parse_jwt(localStorage.getItem('logInToken')).username + `</a>`);
  },

  setupForgotPasswordModal: function() {
    var logInToken = localStorage.getItem("logInToken");
    var id = parse_jwt(logInToken).id;
    $('#change-password-form').validate({
      submitHandler: function(form) {

        var password = $('input[name="password"]').val();
        
        var entity = {
          id: id,
          password: password
        };
        DashboardService.sendForgotPasswordRequest(entity);
      }
    });
  },

  sendForgotPasswordRequest: function(entity) {
    $.ajax({
      url: '../api/changePassword',
      type: 'POST',
      data: JSON.stringify(entity),
        contentType: "application/json",
        dataType: "json",
      success: function(response) {
        // Handle the success response
        // Display a success message or redirect to a success page
        toastr.success("Password reset");
        // Additional logic after successfully sending the password reset request
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
        var response = JSON.parse(XMLHttpRequest.responseText);
        //var errorMessage = response && response.message ? response.message : "An error occurred. Please try again later.";
        toastr.error("Password is breached or too short try another");
      }
    });
  }
};

// Rest of your code...
