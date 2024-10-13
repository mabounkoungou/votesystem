<?php
  	session_start();
  	if(isset($_SESSION['admin'])){
    	header('location: admin/home.php');
  	}

    if(isset($_SESSION['voter'])){
      header('location: home.php');
    }
?>
<!DOCTYPE html>
<html dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="16x16" href="ass/assets/images/favicon.png">
    <title>Voting System - Voters</title>
    <link href="ass/dist/css/style.min.css" rel="stylesheet">
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
    <div class="main-wrapper">
        <div class="preloader">
            <div class="lds-ripple">
                <div class="lds-pos"></div>
                <div class="lds-pos"></div>
            </div>
        </div>
        <div class="auth-wrapper d-flex no-block justify-content-center align-items-center position-relative"
            style="background:url(ass/assets/images/big/auth-bg.jpg) no-repeat center center;">
            <div class="auth-box row" style="width: 1000px; border-radius: 10px;" >
                <div class="col-lg-7 col-md-5 modal-bg-img" style="background-image: url(ass/assets/images/big/img1.jpg); border-radius: 10px;">
                </div>
                <div class="col-lg-5 col-md-7 bg-white" style="border-radius: 10px; border-right: 0;" >
                    <div class="p-3">
                        
                        <h2 class="mt-3 text-center">TMS Voting Hub</h2>
                        <p class="text-center">Enter your Voter's ID and password to access Voting panel.</p>
                        <form class="mt-4" action="login.php" method="POST">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label class="text-dark" for="uname">Voter ID</label>
                                        <input class="form-control" id="uname" type="text" name="voter" placeholder="Enter your Voter's ID" required>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label class="text-dark" for="pwd">Password</label>
                                        <input class="form-control" id="pwd" type="password" name="password" placeholder="Enter your password" required>
                                    </div>
                                </div>
                                <div class="col-lg-12 text-center">
                                    <button type="submit" class="btn btn-block btn-rounded btn-success" name="login">Sign In</button>
                                </div>
                                <?php
                                if (isset($_SESSION['error'])) {
                                    echo "
                                    <div class='col-lg-12 text-center mt-3'>
                                        <div class='alert alert-danger'>".$_SESSION['error']."</div>
                                    </div>
                                    ";
                                    unset($_SESSION['error']);
                                }
                                ?>
                              
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="ass/assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="ass/assets/libs/popper.js/dist/umd/popper.min.js"></script>
    <script src="ass/assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    <script>
        $(".preloader").fadeOut();
    </script>
</body>

</html>
