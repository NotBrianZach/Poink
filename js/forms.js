function formhash(form, password) {
    // Create a new element input, this will be our hashed password field. 
    var p = document.createElement("input");
 
    // Add the new element to our form. 
    form.appendChild(p);
    p.name = "p";
    p.type = "hidden";
    p.value = hex_sha512(password.value);
 
    // Make sure the plaintext password doesn't get sent. 
    password.value = "";
 
    // Finally submit the form. 
    form.submit();
}
 
function regformhash(form, uid, email, password, companyname, billing, phone, conf) {
     // Check each field has a value
	if ( uid.value == '' ||
          email.value == ''     || 
          password.value == ''  || 
          conf.value == ''      ||
          companyname.value == ''  || 
          billing.value == ''   || 
          phone.value == '') {
 
        alert('You must provide all the requested details. Please try again');
        return false;
    }
 
    // Check the username
 
    re = /^\w+$/; 
    if(!re.test(form.username.value)) { 
        alert("Username must contain only letters, numbers and underscores. Please try again"); 
        form.username.focus();
        return false; 
    }
 
    // Check that the password is sufficiently long (min 6 chars)
    // The check is duplicated below, but this is included to give more
    // specific guidance to the user
    if (password.value.length < 6) {
        alert('Passwords must be at least 6 characters long.  Please try again');
        form.password.focus();
        return false;
    }
 
    // At least one number, one lowercase and one uppercase letter 
    // At least six characters 
 
    var re = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}/; 
    if (!re.test(password.value)) {
        alert('Passwords must contain at least one number, one lowercase and one uppercase letter.  Please try again');
        return false;
    }
 
    // Check password and confirmation are the same
    if (password.value != conf.value) {
        alert('Your password and confirmation do not match. Please try again');
        form.password.focus();
        return false;
    }
 
    // Create a new element input, this will be our hashed password field. 
    var p = document.createElement("input");
 
    // Add the new element to our form. 
    form.appendChild(p);
    p.name = "p";
    p.type = "hidden";
    p.value = hex_sha512(password.value);
 
    // Make sure the plaintext password doesn't get sent. 
    password.value = "";
    conf.value = "";
 
    // Finally submit the form. 
    form.submit();
    return true;
}

//latitude and longitude are in degrees (float) and radius is in meters.
function appendCoords(ev, form){
    $.each(circles, function(i,param){
        $('<input />').attr('type', 'hidden')
            .attr('name', 'lat[]')
            .attr('value' , param.getCenter().lat())
            .prependTo('form#submitquestioncoords');
        $('<input />').attr('type', 'hidden')
            .attr('name' , 'lng[]')
            .attr('value', param.getCenter().lng())
            .prependTo('form#submitquestioncoords');
        $('<input />').attr('type', 'hidden')
            .attr('name' , 'radius[]')
            .attr('value', param.getRadius())
            .prependTo('form#submitquestioncoords');
    });
  form.submit();
  return true;  
}

function validateQuestionInsertOrUpdate(form, question, minage, maxage, bid, budget){
    //need form validation, one at a time..
        console.log(bid);
	if (question == ''){
        alert('You must provide all the requested details. Please try again');
        form.question.focus();
        return false;
    }
    if (bid == ''){
        alert('You must provide all the requested details. Please try again');
        form.bid.focus();
        return false;
    }
    if (isNaN(bid)){
        alert('Bid must be a number');
        form.bid.focus();
        return false;
    }
    if (budget == ''){
        alert('You must provide all the requested details. Please try again');
        form.budget.focus();
        return false;
    }
    if (isNaN(budget)){
        alert('Budget must be a number');
        form.budget.focus();
        return false;
    }
    if (minage == '' || maxage == ''){
        alert('You must provide all the requested details. Please try again');
        form.minage.focus();
        return false;
    }
    if (isNaN(minage) || isNaN(maxage)){
        alert('Age ranges must both be numbers');
        form.minage.focus();
        return false;
    }
    form.submit();
    return true;
} 
