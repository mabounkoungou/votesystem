<?php include 'includes/session.php'; ?>
<?php include 'includes/slugify.php'; ?>

<?php include 'includes/print.php'; 
function generateRow($conn) {
  $contents = '';
  $sql = "SELECT * FROM positions ORDER BY priority ASC";
  $query = $conn->query($sql);
  
  while ($row = $query->fetch_assoc()) {
      $id = $row['id'];
      $contents .= '
          <tr class="position-header">
              <td colspan="3" align="center"><b>' . $row['description'] . '</b></td>
          </tr>
          <tr class="table-subheader">
              <td><b>Candidates</b></td>
              <td><b>Votes</b></td>
              <td><b>Percentage</b></td>
          </tr>
      ';

      // Get the total votes for the position
      $totalVotesSql = "SELECT COUNT(*) as total_votes FROM votes WHERE candidate_id IN (SELECT id FROM candidates WHERE position_id = '$id')";
      $totalVotesResult = $conn->query($totalVotesSql);
      $totalVotesRow = $totalVotesResult->fetch_assoc();
      $totalVotes = $totalVotesRow['total_votes'];

      $sql = "SELECT * FROM candidates WHERE position_id = '$id' ORDER BY lastname ASC";
      $cquery = $conn->query($sql);
      
      while ($crow = $cquery->fetch_assoc()) {
          $sql = "SELECT * FROM votes WHERE candidate_id = '" . $crow['id'] . "'";
          $vquery = $conn->query($sql);
          $votes = $vquery->num_rows;

          // Calculate percentage
          $percentage = $totalVotes > 0 ? ($votes / $totalVotes) * 100 : 0;

          $contents .= '
              <tr class="candidate-row">
                  <td>' . $crow['lastname'] . ", " . $crow['firstname'] . '</td>
                  <td>' . $votes . '</td>
                  <td>' . number_format($percentage, 2) . '%</td>
              </tr>
          ';
      }

      // Display total votes after candidates
      $contents .= '
          <tr class="summary-row">
              <td colspan="2"><strong>Total Votes:</strong></td>
              <td>' . $totalVotes . '</td>
          </tr>
      ';
  }
  return $contents;
}

// Get election title and total number of voters
$parse = parse_ini_file('config.ini', FALSE, INI_SCANNER_RAW);
$title = $parse['election_title'];

// Get total number of voters
$totalVotersSql = "SELECT COUNT(*) as total_voters FROM voters"; // Adjust the table name as necessary
$totalVotersResult = $conn->query($totalVotersSql);
$totalVotersRow = $totalVotersResult->fetch_assoc();
$totalVoters = $totalVotersRow['total_voters'];

// Get total number of voters who voted
$votedVotersSql = "SELECT COUNT(DISTINCT id) as voted_voters FROM votes"; // Adjust the column and table name as necessary
$votedVotersResult = $conn->query($votedVotersSql);
$votedVotersRow = $votedVotersResult->fetch_assoc();
$votedVoters = $votedVotersRow['voted_voters'];

?>



<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon.png">
    <title>TMS</title>
    <!-- Custom CSS -->
     
    <link href="assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <!-- Custom CSS -->
    <link href="dist/css/style.min.css" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->

<style>
  .modal {
  z-index: 1050 !important; /* Boosts the modal's stacking order */
}

.modal-backdrop {
  z-index: 1040 !important; /* Ensures the backdrop appears behind the modal */
}
.modal-dialog {
  margin: 30px auto; /* Ensure proper spacing from the top */
}
.icon-voted {
    color: #4caf50; /* Green */
}

.icon-total-voters {
    color: #2196f3; /* Blue */
}

.icon-candidates {
    color: #ff9800; /* Orange */
}

.icon-positions {
    color: #9c27b0; /* Purple */
}

.btn {
    display: inline-block;
    
}


.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
   
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
    padding-top: 60px;
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    border-radius: 10px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
}

.modal-header {
    display: flex;
    flex-direction: column; /* Stack elements vertically */
    align-items: center; /* Center horizontally */
    justify-content: center; /* Center vertically if needed */
    text-align: center; /* Center text inside the container */
    padding: 10px 0; /* Add padding if necessary */
    border-bottom: 1px solid #ddd;
}

.modal-header h2 {
    margin: 0; /* Remove default margin */
    font-size: 24px;
    color: #6610f2; /* Customize the color */
}

.modal-header h2 span {
    display: block; /* Treat span as block to ensure it appears on a new line */
    margin-top: 5px; /* Adjust spacing between title and span */
    font-size: 18px;
    color: #666;
}


.modal-body {
    max-height: 60vh;
    overflow-y: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

table, th, td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.position-header {
    background-color: #f2f2f2;
    font-weight: bold;
}

.table-subheader {
    background-color: #6610f2; /* Updated color */
    color: #fff;
}

.candidate-row:nth-child(even) {
    background-color: #f9f9f9;
}

.summary-row {
    background-color: #e7f3fe; /* Light blue background for summary */
    font-weight: bold;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

.print-btn {
    border: none;
    border-radius: 10px;
    cursor: pointer;
    margin: 10px auto; /* Center the button with auto margins */
    width: 150px;
    text-align: center;
    display: block; /* Make the button a block element */
}




@media print {
    .close, .print-btn, .btn {
        display: none; /* Hide close button and print button in print */
    }
    .modal {
        background-color: #fff; /* Background should be white for printing */
    }
    body{
      width: 100%;
    }
    
}


</style>
<script>
  $('#profile').on('shown.bs.modal', function () {
  $('body').addClass('modal-open');
});

</script>
</head>

<body>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div id="main-wrapper" data-theme="light" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed" data-boxed-layout="full">
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <header class="topbar" data-navbarbg="skin6">
            <nav class="navbar top-navbar navbar-expand-md">
                <div class="navbar-header" data-logobg="skin6">
                    <!-- This is for the sidebar toggle which is visible on mobile only -->
                    <a class="nav-toggler waves-effect waves-light d-block d-md-none" href="javascript:void(0)"><i
                            class="ti-menu ti-close"></i></a>
                    <!-- ============================================================== -->
                    <!-- Logo -->
                    <!-- ============================================================== -->
                    <div class="navbar-brand">
                        <!-- Logo icon -->
                        <a href="index.html">
                            <b class="logo-icon">
                                <!-- Dark Logo icon -->
                                <img src="assets/images/logo-icon.png" alt="homepage" class="dark-logo" />
                                <!-- Light Logo icon -->
                                <img src="assets/images/logo-icon.png" alt="homepage" class="light-logo" />
                            </b>
                            <!--End Logo icon -->
                            <!-- Logo text -->
                            <span class="logo-text">
                                <!-- dark Logo text -->
                                <img src="assets/images/logo-text.png" alt="homepage" class="dark-logo" />
                                <!-- Light Logo text -->
                                <img src="assets/images/logo-light-text.png" class="light-logo" alt="homepage" />
                            </span>
                        </a>
                    </div>
                    <!-- ============================================================== -->
                    <!-- End Logo -->
                    <!-- ============================================================== -->
                    <!-- ============================================================== -->
                    <!-- Toggle which is visible on mobile only -->
                    <!-- ============================================================== -->
                    <a class="topbartoggler d-block d-md-none waves-effect waves-light" href="javascript:void(0)"
                        data-toggle="collapse" data-target="#navbarSupportedContent"
                        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><i
                            class="ti-more"></i></a>
                </div>
                <!-- ============================================================== -->
                <!-- End Logo -->
                <!-- ============================================================== -->
                <div class="navbar-collapse collapse" id="navbarSupportedContent">
                    <!-- ============================================================== -->
                    <!-- toggle and nav items -->
                    <!-- ============================================================== -->
                    <ul class="navbar-nav float-left mr-auto ml-3 pl-1">
                     <span style="font-size:24px; color:black;">
                    Dashboard
                    </span>
                        <!-- Notification -->
                        <!-- End Notification -->
                        <!-- ============================================================== -->
                        <!-- create new -->
                        <!-- ============================================================== -->
                      
                      
                    </ul>
                     <section class="content">
     
                    <!-- ============================================================== -->
                    <!-- Right side toggle and nav items -->
                    <!-- ============================================================== -->
                    <ul class="navbar-nav float-left">
                   
                        <!-- ============================================================== -->
                        <!-- Search -->
                        <!-- ============================================================== -->
                        
                        <!-- ============================================================== -->
                        <!-- User profile and search -->
                        <!-- ============================================================== -->
                         <!-- Add -->

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:void(0)" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <img src="<?php echo (!empty($user['photo'])) ? '../images/'.$user['photo'] : '../images/profile.jpg'; ?>" 
     class="img-fluid rounded-circle" 
     alt="User Image" 
     style="object-fit: cover; width: 40px; height: 40px;">

                                <span class="ml-2 d-none d-lg-inline-block"><span>Hello,</span> <span
                                        class="text-dark"><?php echo $user['firstname'].' '.$user['lastname']; ?></span> <i data-feather="chevron-down"
                                        class="svg-icon"></i></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right user-dd animated flipInY">
                            <a class="dropdown-item" href="#profile" id="admin_profile" data-toggle="modal">
  <i data-feather="user" class="svg-icon mr-2 ml-1"></i> Update Profile
</a>
                                
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php"><i data-feather="power"
                                        class="svg-icon mr-2 ml-1"></i>
                                    Logout</a>
                                <div class="dropdown-divider"></div>
                                
                            </div>
                        </li>


                        <!-- Update Profile Modal -->
<div class="modal fade" id="profile" tabindex="-1" role="dialog" aria-labelledby="profileModalLabel" aria-hidden="true" data-backdrop="false">
  <div class="modal-dialog">
    <div class="modal-content" style="border: radius 20px;">
      <!-- Modal Header -->
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="profileModalLabel"><b>Admin Profile</b></h4>
      </div>
      
      <!-- Modal Body -->
      <div class="modal-body">
        <form class="form-horizontal" method="POST" action="profile_update.php?return=<?php echo basename((string) $_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
          <!-- Username -->
          <div class="form-group">
            <label for="username" class="col-sm-3 control-label">Username</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="username" name="username" value="<?php echo $user['username']; ?>" required>
            </div>
          </div>
          
          <!-- Password -->
          <div class="form-group">
            <label for="password" class="col-sm-3 control-label">Password</label>
            <div class="col-sm-9">
              <input type="password" class="form-control" id="password" name="password" value="<?php echo $user['password']; ?>" required>
            </div>
          </div>
          
          <!-- Firstname -->
          <div class="form-group">
            <label for="firstname" class="col-sm-3 control-label">Firstname</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo $user['firstname']; ?>" required>
            </div>
          </div>
          
          <!-- Lastname -->
          <div class="form-group">
            <label for="lastname" class="col-sm-3 control-label">Lastname</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo $user['lastname']; ?>" required>
            </div>
          </div>
          
          <!-- Photo Upload -->
          <div class="form-group">
            <label for="photo" class="col-sm-3 control-label">Photo</label>
            <div class="col-sm-9">
              <input type="file" class="form-control" id="photo" name="photo">
            </div>
          </div>
          
          <!-- Current Password -->
          <hr>
          <div class="form-group">
            <label for="curr_password" class="col-sm-3 control-label">Current Password</label>
            <div class="col-sm-9">
              <input type="password" class="form-control" id="curr_password" name="curr_password" placeholder="Input current password to save changes" required>
            </div>
          </div>
      </div>
      
      <!-- Modal Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success" name="save">Save</button>
        </form>
      </div>
    </div>
  </div>
</div>

                        <!-- ============================================================== -->
                        <!-- User profile and search -->
                        <!-- ============================================================== -->
                    </ul>
                </div>
            </nav>
        </header>
        <!-- ============================================================== -->
        <!-- End Topbar header -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Left Sidebar - style you can find in sidebar.scss  -->
        <div id="resultsModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <div class="modal-header">
    <h2 class="text-center"><?php echo $title; ?> <br> 
    <span>Election Result</span></h2>
   


  </div>

        <div class="modal-body">
            <p><strong>Total Number of Registered Voters:</strong> <?php echo $totalVoters; ?></p>
            <p><strong>Total Number of Votes :</strong> <?php echo $votedVoters; ?></p>
            <table>
                <?php echo generateRow($conn); ?>
            </table>
        </div>
        <button class="btn-primary btn-rounded print-btn" id="printButton">Print Results</button>
        
    </div>
</div>

         
         <!-- Config -->
<div class="modal fade" id="config">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title"><b>Configure</b></h4>
            </div>
            <div class="modal-body">
              <div class="text-center">
                <?php
                  $parse = parse_ini_file('config.ini', FALSE, INI_SCANNER_RAW);
                  $title = $parse['election_title'];
                ?>
                <form class="form-horizontal" method="POST" action="config_save.php?return=<?php echo basename((string) $_SERVER['PHP_SELF']); ?>">
                  <div class="form-group">
                    <label for="title" class="col-sm-3 control-label">Title</label>

                    <div class="col-sm-9">
                      <input type="text" class="form-control" id="title" name="title" value="<?php echo $title; ?>">
                    </div>
                  </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-danger btn-rounded pull-left" data-dismiss="modal"><i class="fa fa-close"></i> Close</button>
              <button type="submit" class="btn btn-success btn-rounded" name="save"><i class="fa fa-save"></i> Save</button>
              </form>
            </div>
        </div>
    </div>
</div>
        <!-- ============================================================== -->
        <aside class="left-sidebar" data-sidebarbg="skin6">
            <!-- Sidebar scroll-->
            <div class="scroll-sidebar" data-sidebarbg="skin6">
                <!-- Sidebar navigation-->
                <nav class="sidebar-nav">
                    <ul id="sidebarnav">
                        <li class="sidebar-item"> <a class="sidebar-link sidebar-link" href="home.php"
                                aria-expanded="false"><i data-feather="home" class="feather-icon"></i><span
                                    class="hide-menu">Dashboard</span></a></li>
                        <li class="list-divider"></li>
                        <li class="nav-small-cap"><span class="hide-menu"></span></li>

                        <li class="sidebar-item">
    <a class="sidebar-link" href="votes.php" aria-expanded="false">
        <i data-feather="bar-chart-2" class="feather-icon"></i>
        <span class="hide-menu">Votes</span>
    </a>
</li>

<li class="sidebar-item">
    <a class="sidebar-link" href="voters.php" aria-expanded="false">
        <i data-feather="users" class="feather-icon"></i>
        <span class="hide-menu">Voters</span>
    </a>
</li>

<li class="sidebar-item">
    <a class="sidebar-link" href="positions.php" aria-expanded="false">
        <i data-feather="layers" class="feather-icon"></i>
        <span class="hide-menu">Positions</span>
    </a>
</li>

<li class="sidebar-item">
    <a class="sidebar-link" href="candidates.php" aria-expanded="false">
        <i data-feather="user-check" class="feather-icon"></i>
        <span class="hide-menu">Candidates</span>
    </a>
</li>

<li class="sidebar-item">
    <a class="sidebar-link" href="#config" data-toggle="modal" data-target="#config" aria-expanded="false">
        <i data-feather="settings" class="feather-icon"></i>
        <span class="hide-menu">Election Title</span>
    </a>
</li>

                                     

                         
                    </ul>
                </nav>
                <!-- End Sidebar navigation -->
            </div>
            <!-- End Sidebar scroll-->
        </aside>
        <!-- ============================================================== -->
        <!-- End Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">
            <!-- ============================================================== -->
            <!-- Bread crumb and right sidebar toggle -->
            <!-- ============================================================== -->
            <div class="page-breadcrumb">
                <div class="row">
                    <div class="col-7 align-self-center">
                        <h3 class="page-title text-truncate text-dark font-weight-medium mb-1">Welcome to TMS voting Hub, Have Fun</h3>
                        <div class="d-flex align-items-center">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb m-0 p-0">
                                    <li class="breadcrumb-item"><a href="home.php"></a>
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                     <?php
        if(isset($_SESSION['error'])){
          echo "
            <div class='alert alert-danger alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-warning'></i> Error!</h4>
              ".$_SESSION['error']."
            </div>
          ";
          unset($_SESSION['error']);
        }
        if(isset($_SESSION['success'])){
          echo "
            <div class='alert alert-success alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-check'></i> Success!</h4>
              ".$_SESSION['success']."
            </div>
          ";
          unset($_SESSION['success']);
        }
      ?>
                    
                  <div class="col-5 align-self-center">
                        <div class="customize-input float-right bg-white border-0 custom-shadow custom-radius">
                        <button class="btn-primary btn-rounded" id="showModal">
    <i class="fas fa-eye"></i> View Election Results
</button>
                     </div>
                    </div>  
                </div>
            </div>
            <!-- ============================================================== -->

            
            <!-- End Bread crumb and right sidebar toggle -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- Container fluid  -->
            <!-- ============================================================== -->
            <div class="container-fluid">
                <!-- *************************************************************** -->
                <!-- Start First Cards -->
                <!-- *************************************************************** -->
                <div class="card-group">
                    <div class="card border-right">
                        <div class="card-body">
                            <div class="d-flex d-lg-flex d-md-block align-items-center">
                                <div style="border-radius:10px">
                                    <div class="d-inline-flex align-items-center">
                                        <h2 class="text-dark mb-1 font-weight-medium"> <?php
                $sql = "SELECT * FROM positions";
                $query = $conn->query($sql);
echo $query->num_rows;
              ?> </h2>
                                      <span class="badge bg-primary font-12 text-white font-weight-medium badge-pill ml-2 d-lg-block d-md-none">
    <a href="positions.php" class="text-white text-decoration-none">More info</a>
</span>
<br>

                                    </div>
                                    <h6 class="text-muted font-weight-normal mb-0 w-100 text-truncate">No. of Positions</h6>
                                </div>
                                <div class="ml-auto mt-md-3 mt-lg-0">
                                    <span class="opacity-7 text-muted"><i data-feather="layers" class="feather-icon icon-positions"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-right">
                        <div class="card-body">
                            <div class="d-flex d-lg-flex d-md-block align-items-center">
                                <div style="border-radius:10px">
                                    <div class="d-inline-flex align-items-center">
                                        <h2 class="text-dark mb-1 font-weight-medium"> <?php
                $sql = "SELECT * FROM candidates";
                $query = $conn->query($sql);

                echo $query->num_rows;
              ?> </h2>
                                      <span class="badge bg-primary font-12 text-white font-weight-medium badge-pill ml-2 d-lg-block d-md-none">
    <a href="candidates.php" class="text-white text-decoration-none">More info</a>
</span>
<br>

                                    </div>
                                    <h6 class="text-muted font-weight-normal mb-0 w-100 text-truncate">No. of Candidates</h6>
                                </div>
                                <div class="ml-auto mt-md-3 mt-lg-0">
                                    <span class="opacity-7 text-muted"><i data-feather="user-check" class="feather-icon icon-candidates"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-right">
                        <div class="card-body">
                            <div class="d-flex d-lg-flex d-md-block align-items-center">
                                <div style="border-radius:10px">
                                    <div class="d-inline-flex align-items-center">
                                        <h2 class="text-dark mb-1 font-weight-medium"> 
                                        <?php
                $sql = "SELECT * FROM voters";
                $query = $conn->query($sql);

                echo $query->num_rows;
              ?>
              </h2>
                                      <span class="badge bg-primary font-12 text-white font-weight-medium badge-pill ml-2 d-lg-block d-md-none">
    <a href="voters.php" class="text-white text-decoration-none">More info</a>
</span>
<br>

                                    </div>
                                    <h6 class="text-muted font-weight-normal mb-0 w-100 text-truncate">Total Voters</h6>
                                </div>
                                <div class="ml-auto mt-md-3 mt-lg-0">
                                    <span class="opacity-7 text-muted"><i data-feather="users" class="feather-icon icon-total-voters">></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-right">
                        <div class="card-body">
                            <div class="d-flex d-lg-flex d-md-block align-items-center">
                                <div style="border-radius:10px">
                                    <div class="d-inline-flex align-items-center">
                                        <h2 class="text-dark mb-1 font-weight-medium"> <?php
                $sql = "SELECT * FROM votes GROUP BY voters_id";
                $query = $conn->query($sql);

                echo $query->num_rows;
              ?></h2>
                                      <span class="badge bg-primary font-12 text-white font-weight-medium badge-pill ml-2 d-lg-block d-md-none">
    <a href="votes.php" class="text-white text-decoration-none">More info</a>
</span>
<br>

                                    </div>
                                    <h6 class="text-muted font-weight-normal mb-0 w-100 text-truncate">Voters Voted</h6>
                                </div>
                                <div class="ml-auto mt-md-3 mt-lg-0">
                                    <span class="opacity-7 text-muted"><i data-feather="check-circle"  class="feather-icon icon-voted"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <br>

                <!-- *************************************************************** -->
                <!-- End First Cards -->
                <!-- *************************************************************** -->
                <!-- *************************************************************** -->
                <!-- Start Sales Charts Section -->
                <!-- *************************************************************** -->
                               <!-- End Top Leader Table -->
                <!-- *************************************************************** -->
                 
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
  google.charts.load("current", {packages:["corechart"]});
  google.charts.setOnLoadCallback(drawChart);

  function drawChart() {
    <?php
    $sql = "SELECT * FROM positions ORDER BY priority ASC";
    $query = $conn->query($sql);
    while($row = $query->fetch_assoc()){
      $sql = "SELECT * FROM candidates WHERE position_id = '".$row['id']."'";
      $cquery = $conn->query($sql);
      $candidates = array();
      while($crow = $cquery->fetch_assoc()){
        $sql = "SELECT * FROM votes WHERE candidate_id = '".$crow['id']."'";
        $vquery = $conn->query($sql);
        $candidates[] = "['".$crow['lastname']."', ".$vquery->num_rows."]";
      }
      // Output the chart for each position dynamically
    ?>
    var data = google.visualization.arrayToDataTable([
      ['Candidate', 'Votes'],
      <?php echo implode(",", $candidates); ?>
    ]);

    var options = {
      is3D: true,
    };

    var chart = new google.visualization.PieChart(document.getElementById('piechart_<?php echo $row['id']; ?>'));
    chart.draw(data, options);

    <?php } ?>
  }
</script>

<div class="row">
  <?php
  $sql = "SELECT * FROM positions ORDER BY priority ASC";
  $query = $conn->query($sql);
  while($row = $query->fetch_assoc()){
  ?>
  <div class="col-lg-6 col-md-12" style="boder: radius 20px;">
    <div class="card ">
      <div class="card-body text-primary">
        <h4 class="card-title text-center text-primary"><?php echo $row['description']; ?> Votes</h4>
        <div id="piechart_<?php echo $row['id']; ?>" style="height: 283px; width: 100%;"></div>
        <ul class="list-style-none mb-0">
          <?php
          $sql = "SELECT * FROM candidates WHERE position_id = '".$row['id']."'";
          $cquery = $conn->query($sql);
          while($crow = $cquery->fetch_assoc()){
            $sql = "SELECT * FROM votes WHERE candidate_id = '".$crow['id']."'";
            $vquery = $conn->query($sql);
            $votes = $vquery->num_rows;
          ?>
          <li>
            <i class="fas fa-circle text-primary font-10 mr-2"></i>
            <span class="text-muted"><?php echo $crow['lastname']; ?></span>
            <span class="text-dark float-right font-weight-medium"><?php echo $votes; ?> votes</span>
          </li>
          <?php } ?>
        </ul>
      </div>
    </div>
  </div>
  <?php } ?>
</div>


            </div>
            



     <!-- ============================================================== -->
            <!-- End Container fluid  -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- footer -->
            <!-- ============================================================== -->
            <footer class="footer text-center text-muted">
                 Designed with Love
            </footer>
            <!-- ============================================================== -->
            <!-- End footer -->
            <!-- ============================================================== -->
        </div>
        <!-- ============================================================== -->
        <!-- End Page wrapper  -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
     
<script>
    // JavaScript to handle modal display and printing
    const modal = document.getElementById('resultsModal');
    const showModalBtn = document.getElementById('showModal');
    const closeModalBtn = document.getElementById('closeModal');
    const printButton = document.getElementById('printButton');

    showModalBtn.onclick = function() {
        modal.style.display = 'block';
    }

    closeModalBtn.onclick = function() {
        modal.style.display = 'none';
    }

    printButton.onclick = function() {
        window.print();
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>
    <script src="assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="assets/libs/popper.js/dist/umd/popper.min.js"></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- apps -->
    <!-- apps -->
    <script src="dist/js/app-style-switcher.js"></script>
    <script src="dist/js/feather.min.js"></script>
    <script src="assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="dist/js/sidebarmenu.js"></script>
    <!--Custom JavaScript -->
    <script src="dist/js/custom.min.js"></script>
    <!--This page JavaScript -->
    <script src="assets/extra-libs/c3/d3.min.js"></script>
    <script src="assets/extra-libs/c3/c3.min.js"></script>
    <script src="assets/libs/chartist/dist/chartist.min.js"></script>
    <script src="assets/libs/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js"></script>
    <script src="assets/extra-libs/jvector/jquery-jvectormap-2.0.2.min.js"></script>
    <script src="assets/extra-libs/jvector/jquery-jvectormap-world-mill-en.js"></script>
    <script src="dist/js/pages/dashboards/dashboard1.min.js"></script>
</body>

</html>