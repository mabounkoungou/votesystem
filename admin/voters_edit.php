<?php
include 'includes/session.php';

if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $password = $_POST['password'];

    // Fetch current voter data
    $sql = "SELECT * FROM voters WHERE id = '$id'";
    $query = $conn->query($sql);
    $row = $query->fetch_assoc();

    // Check if the password is unchanged
    if ($password == $row['password']) {
        $password = $row['password'];
    } else {
        // Hash new password
        $password = password_hash($password, PASSWORD_DEFAULT);
    }

    // Update the voter
    $sql = "UPDATE voters SET firstname = '$firstname', lastname = '$lastname', password = '$password' WHERE id = '$id'";
    if ($conn->query($sql)) {
        $_SESSION['success'] = 'Voter updated successfully';
    } else {
        $_SESSION['error'] = $conn->error;
    }
} else {
    $_SESSION['error'] = 'Fill up edit form first';
}

header('location: voters.php');
?>
