<?php
// admin.php - Admin control panel
include 'config.php';
requireAdmin(); // Only admins can access

// Get statistics
$stats = [
    'total_artworks' => 0,
    'available_artworks' => 0,
    'sold_artworks' => 0,
    'total_artists' => 0,
    'total_sales' => 0
];


try {
    // Artwork stats - 
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM gallery GROUP BY status");
    $status_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize counts
    $status_counts = [
        'available' => 0,
        'sold' => 0,
        'reserved' => 0,
        'archived' => 0
    ];
    
    // Sum up counts properly
    foreach ($status_results as $row) {
        $status = $row['status'];
        $count = (int)$row['count']; 
        
        if (isset($status_counts[$status])) {
            $status_counts[$status] = $count;
        }
    }
    
    // Now calculate totals
    $stats['total_artworks'] = array_sum($status_counts);
    $stats['available_artworks'] = $status_counts['available'];
    $stats['sold_artworks'] = $status_counts['sold'];
    $stats['reserved_artworks'] = $status_counts['reserved'] ?? 0;
    $stats['archived_artworks'] = $status_counts['archived'] ?? 0;
    
    // Artist count -  Handle NULL values
    $stmt = $pdo->query("SELECT COUNT(DISTINCT artist_id) as count FROM gallery WHERE artist_id IS NOT NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_artists'] = $result ? (int)$result['count'] : 0;
    
    // Total sales -  Handle NULL values
    $stmt = $pdo->query("SELECT SUM(price) as total FROM gallery WHERE status = 'sold' AND price IS NOT NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_sales'] = $result && $result['total'] ? (float)$result['total'] : 0;
    
    // Recent activity - Handle empty results
    $stmt = $pdo->query("SELECT * FROM gallery ORDER BY last_updated DESC LIMIT 5");
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    // Set defaults if error
    $recent_activity = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ArtFlow®</title>
    
    <style>
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--Primary);
            margin-bottom: 0.5rem;
        }
        
        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 2rem 0;
        }
        
        .admin-btn {
            padding: 1rem;
            background: linear-gradient(135deg, var(--Primary), var(--Secondary));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .admin-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(106, 17, 203, 0.3);
        }
        
        .admin-btn i {
            font-size: 2rem;
        }
    </style>
</head>
<body>
   <!-- Header -->
        <header class="gallery-header">
            <div class="header-top">
                <div class="logo">ArtFlow®</div>
                <div class="header-features">
                    <ul class="nav-links">
                        <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="gallery.php" class="active"><i class="fas fa-images"></i> Gallery</a></li>
                        <li><a href="artists.php"><i class="fas fa-palette"></i> Artists</a></li>
                        <?php if (isAdmin()): ?>
                        <li><a href="admin.php"><i class="fas fa-cog"></i> Admin</a></li>
                        <?php endif; ?>
                    </ul>
                    <div class="user-info">
                        <span class="user-badge">
                            <i class="fas <?php echo isAdmin() ? 'fa-crown' : 'fa-user'; ?>"></i>
                            <?php echo isAdmin() ? 'Admin' : 'User'; ?>
                        </span>
                        <div>
                            <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                        </div>
                        <a href="logout.php" style="color: inherit; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>
    
    <div class="admin-container">
        <h1>Admin Control Panel</h1>
        
        <!-- Stats Overview -->
        <div class="admin-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_artworks']; ?></div>
                <div class="stat-label">Total Artworks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['available_artworks']; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['sold_artworks']; ?></div>
                <div class="stat-label">Sold</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($stats['total_sales'], 2); ?></div>
                <div class="stat-label">Total Sales</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="admin-actions">
            <a href="admin_upload.php" class="admin-btn">
                <i class="fas fa-plus-circle"></i>
                <span>Add New Artwork</span>
            </a>
            <a href="admin_inventory.php" class="admin-btn">
                <i class="fas fa-boxes"></i>
                <span>Manage Inventory</span>
            </a>
            <a href="admin_artists.php" class="admin-btn">
                <i class="fas fa-users"></i>
                <span>Manage Artists</span>
            </a>
            <a href="admin_sales.php" class="admin-btn">
                <i class="fas fa-chart-line"></i>
                <span>Sales Reports</span>
            </a>
            <a href="gallery.php?admin=1" class="admin-btn">
                <i class="fas fa-eye"></i>
                <span>View Gallery</span>
            </a>
            <a href="dashboard.php" class="admin-btn">
                <i class="fas fa-home"></i>
                <span>User Dashboard</span>
            </a>
        </div>
        
        <!-- Recent Activity -->
        <div style="margin-top: 3rem;">
            <h2>Recent Activity</h2>
            <!-- Show recent artwork updates -->
        </div>
    </div>
    
    
</body>
</html>