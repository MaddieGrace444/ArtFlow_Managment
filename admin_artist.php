<?php
// admin_artists.php - Full artist CRUD
include 'config.php';
requireAdmin();

// Handle artist updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_artist'])) {
        // Update artist profile
        $artist_id = $_POST['artist_id'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $bio = $_POST['bio'];
        
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $bio, $artist_id]);
        $success = "Artist updated successfully!";
    }
    
    if (isset($_POST['delete_artist'])) {
        // Delete artist (with confirmation)
        $artist_id = $_POST['artist_id'];
        
        // First, update their artworks to have no artist
        $stmt = $pdo->prepare("UPDATE gallery SET artist_id = NULL WHERE artist_id = ?");
        $stmt->execute([$artist_id]);
        
        // Then delete the artist
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type != 'admin'");
        $stmt->execute([$artist_id]);
        $success = "Artist removed from system.";
    }
    
    if (isset($_POST['add_artist'])) {
        // Add new artist (could be external artist not in users table)
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $bio = $_POST['bio'];
        
        $stmt = $pdo->prepare("INSERT INTO artists_external (first_name, last_name, email, bio) VALUES (?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $email, $bio]);
        $success = "New artist added!";
    }
}
?>

<!-- Artist management interface with:
1. Add new artist form
2. Edit existing artists
3. Delete artists (with artwork reassignment)
-->