<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validation
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
            $stmt = $pdo->prepare($check_query);
            $stmt->execute(['username' => $username, 'email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username or email already exists.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // In your signup.php, update the INSERT query:
            $insert_query = "INSERT INTO users (first_name, last_name, username, email, password, user_type) 
                 VALUES (:first_name, :last_name, :username, :email, :password, 'user')";;
            $stmt = $pdo->prepare($insert_query);
            
            if ($stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password
            ])) {
                // SUCCESS - redirect back to signup.html with success message
                header("Location: signup.html?success=Registration+successful!+You+can+now+login.");
                exit();
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $error_string = urlencode(implode("||", $errors));
        // Also pass back form values to repopulate
        $first_name_param = urlencode($first_name);
        $last_name_param = urlencode($last_name);
        $username_param = urlencode($username);
        $email_param = urlencode($email);
        
        header("Location: signup.html?error=$error_string&first_name=$first_name_param&last_name=$last_name_param&username=$username_param&email=$email_param");
        exit();
    }
} else {
    // If someone accesses this page directly without POST, redirect to signup form
    header("Location: signup.html");
    exit();
}
?>