<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
include 'config.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
        $username_encoded = urlencode($username);
        header("Location: login.html?error=" . urlencode($error) . "&username=" . $username_encoded);
        exit();
    }
    
    try {
        // Check if user exists by username OR email
        $query = "SELECT id, username, password, first_name FROM users 
                  WHERE username = :username OR email = :username";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Login successful - set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                // Invalid password
                $error = "Invalid password.";
                $username_encoded = urlencode($username);
                header("Location: login.html?error=" . urlencode($error) . "&username=" . $username_encoded);
                exit();
            }
        } else {
            // No user found
            $error = "No account found with that username/email.";
            $username_encoded = urlencode($username);
            header("Location: login.html?error=" . urlencode($error) . "&username=" . $username_encoded);
            exit();
        }
    } catch (PDOException $e) {
        // Database error
        $error = "Database error. Please try again.";
        header("Location: login.html?error=" . urlencode($error));
        exit();
    }
} else {
    // If accessed directly without POST, redirect to login form
    header("Location: login.html");
    exit();
}
?>