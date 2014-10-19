<?php
include_once 'psl-config.php';
 
//function to include at top of html to make it secure
function sec_session_start() {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") {
        header('Location: https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        exit(1);
    }
    $session_name = 'sec_session_id';   // Set a custom session name
    $secure = SECURE;
    // This stops JavaScript being able to access the session id.
    $httponly = true;
    // Forces sessions to only use cookies.
    if (ini_set('session.use_only_cookies', 1) === FALSE) {
        header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
        exit();
    }
    // Gets current cookies params.
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params($cookieParams["lifetime"],
        $cookieParams["path"], 
        $cookieParams["domain"], 
        $secure,
        $httponly);
    // Sets the session name to the one set above.
    session_name($session_name);
    session_start();            // Start the PHP session 
    session_regenerate_id();    // regenerated the session, delete the old one. 
}

function login($email, $password, $mysqli) {
    // Using prepared statements means that SQL injection is not possible. 
    if ($stmt = $mysqli->prepare("SELECT COMPANY_ID, EMAIL, PASSWORD, SALT 
        FROM COMPANIES
       WHERE EMAIL = ?
        LIMIT 1")) {
        $stmt->bind_param('s', $email);  // Bind "$email" to parameter.
        $stmt->execute();    // Execute the prepared query.
        $stmt->store_result();
 
        // get variables from result.
        $stmt->bind_result($companyId, $email, $db_password, $salt);
        $stmt->fetch();
 
        // hash the password with the unique salt.
        $password = hash('sha512', $password . $salt);//need to use password function with this instead of hash/salt.
        if ($stmt->num_rows == 1) {
            // If the user exists we check if the account is locked
            // from too many login attempts 
            if (checkbrute($companyId, $mysqli) == true) {
                // Account is locked 
                // accomplish by invalidating and generating a new validation code
                $invalidate = $database->prepare(' UPDATE COMPANIES SET VALIDATED = 0, VALIDATION_CODE = :validationCode WHERE COMPANY_ID = :companyId');
                $invalidate->bindValue(':companyId', $companyId, PDO::PARAM_INT);
                $invalidate->bindValue(':validationCode', hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true)) , PDO::PARAM_STR);
                //hopefully this hash is not more than 128 characters
                $invalidate->execute();
                $invalidate->closeCursor();
                // Send an email to user saying their account is locked
                $to = $email;
                $validationURL = "https://72.182.40.185/accountConfirmationReceiver.php?validationCode="; //to be replaced with www.poink.org or something similar.
                $subject = "Poink Account Locked: Re-validation link";
                $body = "Click on this link to confirm you are not a robot and re-activate your account: " . $validationURL . $validationCode;
                sendMail($to, $subject, $body);
                echo 'Too many password entry attempts. Check your email for a link to confirm you are not \n a robot trying to brute force someone\'s  account.';
                return false;
            } else {
                // Check if the password in the database matches
                // the password the user submitted.
                if ($db_password == $password) {
                    // Password is correct!
                    // Get the user-agent string of the user.
                    $user_browser = $_SERVER['HTTP_USER_AGENT'];
                    // XSS protection as we might print this value
                    $companyId = preg_replace("/[^0-9]+/", "", $companyId);
                    $_SESSION['companyId'] = $companyId;
                    // XSS protection as we might print this value
                    //$username = preg_replace("/[^a-zA-Z0-9_\-]+/", 
                    //                                            "", 
                    //                                            $username);
                    $_SESSION['email'] = $email;
                    $_SESSION['login_string'] = hash('sha512', 
                              $password . $user_browser);
                    // Login successful.
                    return true;
                } else {
                    // Password is not correct
                    // We record this attempt in the database
                    $now = time();
                    $mysqli->query("INSERT INTO LOGIN_ATTEMPTS(COMPANY_ID, TIME)
                                    VALUES ('$companyId', '$now')");
                    return false;
                }
            }
        } else {
            // No user exists.
            return false;
        }
    }
}

function checkbrute($companyId, $mysqli) {
    // Get timestamp of current time 
    $now = time();
 
    // All login attempts are counted from the past 2 hours. 
    $valid_attempts = $now - (2 * 60 * 60);
 
    if ($stmt = $mysqli->prepare("SELECT time 
                             FROM login_attempts 
                             WHERE companyId = ? 
                            AND time > '$valid_attempts'")) {
        $stmt->bind_param('i', $companyId);
 
        // Execute the prepared query. 
        $stmt->execute();
        $stmt->store_result();
 
        // If there have been more than 5 failed logins 
        if ($stmt->num_rows > 5) {
            return true;//need to implement sending user an email with reset link
        } else {
            return false;//don't do nothin'
        }
    }
}

function loginCheck($mysqli) {
    // Check if all session variables are set 
    if (isset($_SESSION['companyId'], 
                        $_SESSION['email'], 
                        $_SESSION['login_string'])) {
 
        $companyId = $_SESSION['companyId'];
        $login_string = $_SESSION['login_string'];
        $email = $_SESSION['email'];
 
        // Get the user-agent string of the user.
        $user_browser = $_SERVER['HTTP_USER_AGENT'];
 
        if ($stmt = $mysqli->prepare("SELECT PASSWORD 
                                      FROM COMPANIES
                                      WHERE COMPANY_ID = ? LIMIT 1")) {
            // Bind "$companyId" to parameter. 
            $stmt->bind_param('i', $companyId);
            $stmt->execute();   // Execute the prepared query.
            $stmt->store_result();
 
            if ($stmt->num_rows == 1) {
                // If the user exists get variables from result.
                $stmt->bind_result($password);
                $stmt->fetch();
                $loginCheck = hash('sha512', $password . $user_browser);
 
                if ($loginCheck == $login_string) {
                    // Logged In!!!! 
                    return true;
                } else {
                    // Not logged in 
                    return false;
                }
            } else {
                // Not logged in 
                return false;
            }
        } else {
            // Not logged in 
            return false;
        }
    } else {
        // Not logged in 
        return false;
    }
}

function esc_url($url) {
//sanitize the output from the PHP_SELF server variable to prevent session hijacking 
    if ('' == $url) {
        return $url;
    }
 
    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
 
    $strip = array('%0d', '%0a', '%0D', '%0A');
    $url = (string) $url;
 
    $count = 1;
    while ($count) {
        $url = str_replace($strip, '', $url, $count);
    }
 
    $url = str_replace(';//', '://', $url);
 
    $url = htmlentities($url);
 
    $url = str_replace('&amp;', '&#038;', $url);
    $url = str_replace("'", '&#039;', $url);
 
    if ($url[0] !== '/') {
        // We're only interested in relative links from $_SERVER['PHP_SELF']
        return '';
    } else {
        return $url;
    }
}
?>
