function formhash(form, password) {
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

function resetformhash(form, validationCode, password, passconf){
    if (validationCode == '' || password.value == '' || passconf.value == ''){
        alert('You must provide all the requested details. Please try again');
        return false;
    }
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
    if (password.value != passconf.value) {
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
    passconf.value = "";

    form.submit();
    return true;
}

function regformhash(form, email, password, companyname, billing, phone, passconf) {
     // Check each field has a value
	if (  email.value == ''     || 
          password.value == ''  || 
          passconf.value == ''      ||
          companyname.value == ''  || 
          billing.value == ''   || 
          phone.value == '') {
 
        alert('You must provide all the requested details. Please try again');
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
    if (password.value != passconf.value) {
        alert('Your password and confirmation do not match. Please try again');
        form.password.focus();
        return false;
    }

    // Create a new element input, this will be our hashed password field. 
    var p = document.createElement("input");
 
    //don't necessarily need this since we're using https, but it doesn't hurt.
    //no plaintext passwords visible to heartbleed righto?
    // Add the new element to our form. 
    form.appendChild(p);
    p.name = "p";
    p.type = "hidden";
    p.value = hex_sha512(password.value);
      
    // Make sure the plaintext password doesn't get sent. 
    password.value = "";
    passconf.value = "";
 
    // Finally submit the form. 
    form.submit();
    return true;
}

function modifyAccountBalance(form, modifyAmount, companyBudget){
    if (isNaN(modifyAmount)){
        alert('The account modification amount must be a number.');
        form.companyBudget.focus();
        return false;
    }
    if (modifyAmount < 0){
        alert('You can\'t withdraw money from your account this way.');
        form.companyBudget.focus();
        return false;
    }
    form.submit();
    return true;
}

function zip(array0, array1) {
    if (typeof array0 != 'undefined' && typeof array1 != 'undefined'){
        var array = new Array();
        var i = 0;
        for (var i = 0; i < array0.length; i++){
            array.push([array0[i], array1[i]]);
        }
        return array;
    }
    return 'BROKEN';
}
//latitude and longitude are in degrees (float) and radius is in meters.
function appendCoords(ev, form, bids, budgets, dates, totalBudget){
    var bidBudgets = new Array();
    var sumBudgets = 0;
    bidBudgets = zip(bids, budgets);
    for (var i = 0; i < bidBudgets.length; i++){
        if (bidBudgets[i][0].value == ''){
            alert('You must provide all the requested details. Please try again');
            form.bidBudgets[i][0].focus();
            return false;
        }
        if (isNaN(bidBudgets[i][0].value)){
            alert('Bid must be a number');
            form.bidBudgets[i][0].focus();
            return false;
        }
        if (bidBudgets[i][0].value < .05){
            alert('5 cents (.05 dollars) is the minimum bid value');
            form.bidBudgets[i][0].focus();
            return false;
        }
        if (bidBudgets[i][1].value == ''){
            alert('You must provide all the requested details. Please try again');
            form.bidBudgets[i][1].focus();
            return false;
        }
        if (isNaN(bidBudgets[i][1].value)){
            alert('Budget must be a number');
            form.bidBudgets[i][1].focus();
            return false;
        }
        if (bidBudgets[i][1].value < bidBudgets[i][0].value){
            alert('Budget must be at least equal to bid');
            form.bidBudgets[i][1].focus();
            return false;
        }
        var re = /^\d{2}\/\d{2}\/\d{4} \d{2} ([ap]m)$/
        if (!re.test(dates[i].value) && dates[i].value != ''){
            alert('End date must be of the form 00/00/00 00 am (or pm) or empty.');
            form.dates[i].focus();
            return false;
        }
        sumBudgets += bidBudgets[i][1];
    }
    if (sumBudgets > totalBudget){
        alert("Your total budget is less than the sum of the budgets you've allocated for individual ad campaigns.");
        return false;
    }
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

function validateQuestionInsertOrUpdate(form, question, minage, maxage){
	if (question == ''){
        alert('You must provide all the requested details. Please try again');
        form.question.focus();
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
    if (Number(maxage) < Number(minage)){
        alert('Minimum age cannot be more than maximum age');
        console.log(minage);
        console.log(maxage);
        form.minage.focus();
        return false;
    }
    if (maxage > 130){
        alert('There is no one alive at that maximum age!');
        form.maxage.focus();
        return false;
    }
    if (minage < 0){
        alert('Minimum age cannot be less than 0');
        form.minage.focus();
        return false;
    }
    form.submit();
    return true;
} 
