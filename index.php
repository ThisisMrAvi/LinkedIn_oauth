<?php
// Include configuration file
require_once 'config.php';

// Include seeker class
require_once 'seeker_class.php';

$authUrl = $output = '';

// If user already verified 
if(isset($_SESSION['oauth_status']) && $_SESSION['oauth_status'] == 'verified' && !empty($_SESSION['userData'])){
    // Retrieve user's profile data from session
    $userData = $_SESSION['userData'];
    
    // Prepare output to show LinkedIn profile data
    if(!empty($userData)){
        $output  = '<h2>LinkedIn Profile Details</h2>';
        $output .= '<div class="ac-data">';
        $output .= '<img src="'.$userData['picture'].'"/>';
        $output .= '<p><b>LinkedIn ID:</b> '.$userData['oauth_uid'].'</p>';
        $output .= '<p><b>Name:</b> '.$userData['first_name'].' '.$userData['last_name'].'</p>';
        $output .= '<p><b>Email:</b> '.$userData['email'].'</p>';
        $output .= '<p><b>Logged in with:</b> LinkedIn'.'</p>';
        $output .= '<p><b>Profile Link:</b> <a href="'.$userData['link'].'" target="_blank">Click to visit LinkedIn page</a></p>';
        $output .= '<p><b>Logout from</b> <a href="logout.php">LinkedIn</a></p>';
        $output .= '</div>';
    }
}elseif((isset($_GET["oauth_init"]) && $_GET["oauth_init"] == 1) || (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])) || (isset($_GET['code']) && isset($_GET['state']))){
    $client = new oauth_client_class;
    
    $client->client_id = LIN_CLIENT_ID;
    $client->client_secret = LIN_CLIENT_SECRET;
    $client->redirect_uri = LIN_REDIRECT_URL;
    $client->scope = LIN_SCOPE;
    $client->debug = 1;
    $client->debug_http = 1;
    $application_line = __LINE__;
    
    if(strlen($client->client_id) == 0 || strlen($client->client_secret) == 0){
        die('Please go to LinkedIn Apps page https://www.linkedin.com/secure/developer?newapp= , '.
            'create an application, and in the line '.$application_line.
            ' set the client_id to Consumer key and client_secret with Consumer secret. '.
            'The Callback URL must be '.$client->redirect_uri.'. Make sure you enable the '.
            'necessary permissions to execute the API calls your application needs.');
    }
    
    // If authentication returns success
    if($success = $client->Initialize()){
        if(($success = $client->Process())){
            if(strlen($client->authorization_error)){
                $client->error = $client->authorization_error;
                $success = false;
            }elseif(strlen($client->access_token)){
                $success = $client->CallAPI(
                    'https://api.linkedin.com/v2/me?projection=(id,firstName,lastName,profilePicture(displayImage~:playableStreams))', 
                    'GET', array(
                        'format'=>'json'
                    ), array('FailOnAccessError'=>true), $userInfo);
                $emailRes = $client->CallAPI(
                    'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))', 
                    'GET', array(
                        'format'=>'json'
                    ), array('FailOnAccessError'=>true), $userEmail);
            }
        }
        $success = $client->Finalize($success);
    }
    
    if($client->exit) exit;
    
    if(strlen($client->authorization_error)){
        $client->error = $client->authorization_error;
        $success = false;
    }
    
    if($success){
        // Initialize User class
        $user = new seeker();
        
        // Getting user's profile data
        $inUserData = array();
        $inUserData['oauth_uid']  = !empty($userInfo->id)?$userInfo->id:'';
        $inUserData['first_name'] = !empty($userInfo->firstName->localized->en_US)?$userInfo->firstName->localized->en_US:'';
        $inUserData['last_name']  = !empty($userInfo->lastName->localized->en_US)?$userInfo->lastName->localized->en_US:'';
        $inUserData['email']      = !empty($userEmail->elements[0]->{'handle~'}->emailAddress)?$userEmail->elements[0]->{'handle~'}->emailAddress:'';
        $inUserData['picture']    = !empty($userInfo->profilePicture->{'displayImage~'}->elements[0]->identifiers[0]->identifier)?$userInfo->profilePicture->{'displayImage~'}->elements[0]->identifiers[0]->identifier:'';
        $inUserData['link']       = 'https://www.linkedin.com/';

        // Insert or update user data to the database
        $inUserData['oauth_provider'] = 'linkedin';
        $userData = $user->checkUser($inUserData);
        
        //Storing user data into session
        $_SESSION['userData'] = $userData;
        $_SESSION['oauth_status'] = 'verified';
        
        //Redirect the user back to the same page
        header('Location: ./');
    }else{
         $output = '<h3 style="color:red">Error connecting to LinkedIn! try again later!</h3><p>'.HtmlSpecialChars($client->error).'</p>';
    }
}elseif(isset($_GET["oauth_problem"]) && $_GET["oauth_problem"] <> ""){
    $output = '<h3 style="color:red">'.$_GET["oauth_problem"].'</h3>';
}else{
    $authUrl = '?oauth_init=1';
    
    // Render LinkedIn login button
    $output = '<P>  Or Login with:   <a href="?oauth_init=1"><img src="linkedin-icon.png"></a></P>';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sign Up</title>
        <link rel="shortcut icon" href="./img/srtcticon.png" type="image/png">

      <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap Core CSS -->
      <link href="css/bootstrap.css" rel="stylesheet">
    <!-- Custom CSS -->
      <link href="css/style.css" rel="stylesheet">
    <!-- jQuery -->
      <script src="js/jquery.js"></script>
    <!-- Bootstrap Core JavaScript -->
      <script src="js/bootstrap.min.js"></script>
</head>

<body>

    <div class="container">
        <div class="row">

            <div class="col-sm-8 col-sm-offset-2">

                <h2 style="color:orange">SIGN UP</h2>

                    <form  action="signup_script.php" method="POST">

                        <div class="form-group col-sm-10">
                            <input class="form-control" placeholder="Name" name="name" pattern="[A-Za-z-0-9]+\s[A-Za-z-'0-9]+" required>
                        </div>

                        <div class="form-group col-sm-10">
                                <input type="email" class="form-control"  placeholder="Email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$"  name="email" required>
                                <?php
                                if(isset($_GET["m1"])){
                                  echo $_GET['m1'];
                                }
                                ?>
                        </div>

                        <div class="form-group col-sm-10">
                                <input type="password" class="form-control" placeholder="Password" pattern=".{6,}" name="password" required>
                        </div>

                        <div class="form-group col-sm-10">
                                <input type="text" class="form-control"  placeholder="Mobile No." maxlength="10" size="10" name="mobile_no" required>
                                <?php
                                if(isset($_GET["m2"])){
                                  echo $_GET['m2'];
                                }
                                ?>
                        </div>

                        <div class="form-group col-sm-10">
                                <input  type="text" class="form-control"  placeholder="City" name="city"  >
                        </div>

                        <div class="form-group col-sm-10">
                                <input  type="text" class="form-control"  placeholder="Address" name="address" >
                        </div>

                        <div class="col-sm-10">
                            <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                        </div>  
                        <div class="col-sm-10">
                                  Already have an account ?<a href="login.php"> Login</a>
                        </div>

                </form>
        </div>
    </div>
    </div>
    <div class="container">
        <div class="col-sm-8 col-sm-offset-2 in-box">
            <!-- Display login button / profile information -->
            <?php echo $output; ?>
        </div>
    </div>
    </body>
</html>

