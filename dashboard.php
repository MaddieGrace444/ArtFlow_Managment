<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, username, email, created_at, user_type FROM users WHERE id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Store user_type in session if not set
if (!isset($_SESSION['user_type'])) {
    $_SESSION['user_type'] = $user['user_type'];
}

// Get gallery stats (browse-only for users)
$gallery_stats = [
    'total_artworks' => 0,
    'available_artworks' => 0,
    'featured_artworks' => []
];

try {
    // Total artworks in gallery (only available ones for users)
    $status_condition = isAdmin() ? "" : "AND status = 'available'";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery WHERE 1=1 $status_condition");
    $gallery_stats['total_artworks'] = $stmt->fetch()['count'];
    
    // Available artworks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM gallery WHERE status = 'available'");
    $gallery_stats['available_artworks'] = $stmt->fetch()['count'];
    
    // Featured artworks for browsing (4 most recent available)
    $limit = isAdmin() ? 6 : 4;
    $stmt = $pdo->query("SELECT g.*, u.username as artist_name FROM gallery g LEFT JOIN users u ON g.artist_id = u.id WHERE g.status = 'available' ORDER BY g.featured DESC, g.upload_date DESC LIMIT $limit");
    $gallery_stats['featured_artworks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Gallery table might not exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ArtFlow</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Caprasimo&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --Link-Link: #2C2825;
            --Text-Headline: #171615;
            --Primary: #6a11cb;
            --Secondary: #2575fc;
            --Admin: #ff6b6b;
            --User: #4ecdc4;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #ffffff;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            flex: 1;
        }

        /* Header Styles */
        .dashboard-header {
            padding: 1.5rem 0;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 2rem;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Logo Styles */
        .logo {
            color: var(--Link-Link, #2C2825);
            font-family: 'Caprasimo', serif;
            font-size: 30px;
            font-style: normal;
            font-weight: 400;
            line-height: 92%;
            letter-spacing: -0.9px;
        }

        /* Header Features */
        .header-features {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        <?php if (isAdmin()): ?>
        .user-info {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(78, 205, 196, 0.1));
            border: 1px solid rgba(255, 107, 107, 0.2);
            color: var(--Admin);
        }
        <?php else: ?>
        .user-info {
            background: linear-gradient(135deg, rgba(78, 205, 196, 0.1), rgba(106, 17, 203, 0.1));
            border: 1px solid rgba(78, 205, 196, 0.2);
            color: var(--User);
        }
        <?php endif; ?>

        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        <?php if (isAdmin()): ?>
        .user-badge {
            background: var(--Admin);
            color: white;
        }
        <?php else: ?>
        .user-badge {
            background: var(--User);
            color: white;
        }
        <?php endif; ?>

        .nav-links {
            display: flex;
            gap: 1rem;
            list-style: none;
        }

        .nav-links a {
            color: #666;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-family: 'Outfit', sans-serif;
            font-weight: 500;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .nav-links a:hover {
            background: #f0f0f0;
            color: var(--Primary);
        }

        .nav-links a.active {
            background: linear-gradient(135deg, var(--Primary), var(--Secondary));
            color: white;
        }

        /* Admin Controls (only visible to admins) */
        .admin-controls {
            display: <?php echo isAdmin() ? 'flex' : 'none'; ?>;
            gap: 10px;
            margin-bottom: 2rem;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }

        .admin-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, var(--Admin), #ff8e8e);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 3rem;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        .action-card.gallery {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .action-card.inventory {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .action-card.artists {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .action-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-card p {
            font-size: 0.9rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .action-btn {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .action-btn:hover {
            background: white;
            color: #333;
            transform: translateY(-2px);
        }

        /* Gallery Preview */
        .gallery-preview {
            margin-bottom: 3rem;
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--Text-Headline, #171615);
        }

        .view-all {
            color: var(--Primary);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .artworks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .artwork-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .artwork-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .artwork-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 2;
        }

        .status-available {
            background: rgba(78, 205, 196, 0.9);
            color: white;
        }

        .status-sold {
            background: rgba(255, 107, 107, 0.9);
            color: white;
        }

        .status-reserved {
            background: rgba(255, 193, 7, 0.9);
            color: white;
        }

        .artwork-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }

        .artwork-info {
            padding: 1rem;
        }

        .artwork-title {
            font-weight: 600;
            color: var(--Text-Headline);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .artwork-artist {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .artwork-price {
            color: var(--Primary);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .artwork-date {
            color: #888;
            font-size: 0.8rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--Primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Image Section */
        .image-section {
            margin-bottom: 2rem;
            text-align: center;
        }

        .dashboard-image {
            width: 100%;
            height: 400px;
            border-radius: 20px;
            background: url('images/Dashboard_main.jpg') center/cover no-repeat;
            margin: 0 auto;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        /* Mission Statement */
        .mission-section {
            margin-bottom: 3rem;
            text-align: center;
        }

        .mission-text {
            color: var(--Text-Headline, #171615);
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            font-style: normal;
            font-weight: 700;
            line-height: 130%;
            letter-spacing: -0.5px;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 2rem 1rem;
            border-top: 2px solid #e0e0e0;
            color: #666;
            background: white;
            margin-top: auto;
            width: 100%;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .header-top {
                flex-direction: column;
                gap: 1rem;
            }

            .header-features {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
            }

            .nav-links {
                justify-content: center;
                flex-wrap: wrap;
            }

            .dashboard-image {
                height: 300px;
            }

            .mission-text {
                font-size: 1.4rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .artworks-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }
            
            .dashboard-image {
                height: 250px;
            }
            
            .mission-text {
                font-size: 1.2rem;
            }
            
            .action-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 1. Header with Features -->
        <header class="dashboard-header">
            <div class="header-top">
                <div class="logo">ArtFlow®</div>
                <div class="header-features">
                    <ul class="nav-links">
                        <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="gallery.php"><i class="fas fa-images"></i> Gallery</a></li>
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
                            Welcome, <?php echo htmlspecialchars($user['first_name']); ?>
                        </div>
                        <a href="logout.php" style="color: inherit; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Admin Controls (only for admins) -->
        <?php if (isAdmin()): ?>
        <div class="admin-controls">
            <a href="admin_upload.php" class="admin-btn">
                <i class="fas fa-plus"></i> Add Artwork
            </a>
            <a href="admin_inventory.php" class="admin-btn">
                <i class="fas fa-boxes"></i> Manage Inventory
            </a>
            <a href="admin_artists.php" class="admin-btn">
                <i class="fas fa-users"></i> Manage Artists
            </a>
            <a href="admin_sales.php" class="admin-btn">
                <i class="fas fa-chart-line"></i> View Sales
            </a>
        </div>
        <?php endif; ?>

        <!-- Quick Action Cards -->
        <div class="quick-actions">
            <div class="action-card gallery">
                <h3><i class="fas fa-images"></i> Browse Gallery</h3>
                <p>View <?php echo $gallery_stats['available_artworks']; ?> available artworks in our collection</p>
                <a href="gallery.php" class="action-btn">Browse Collection →</a>
            </div>
            
            <div class="action-card inventory">
                <h3><i class="fas fa-boxes"></i> Inventory Status</h3>
                <p><?php echo $gallery_stats['available_artworks']; ?> available out of <?php echo $gallery_stats['total_artworks']; ?> total artworks</p>
                <?php if (isAdmin()): ?>
                <a href="admin_inventory.php" class="action-btn">Manage Inventory →</a>
                <?php else: ?>
                <a href="gallery.php" class="action-btn">View Collection →</a>
                <?php endif; ?>
            </div>
            
            <div class="action-card artists">
                <h3><i class="fas fa-palette"></i> Featured Artists</h3>
                <p>Discover talented artists in our gallery collection</p>
                <a href="artists.php" class="action-btn">View Artists →</a>
            </div>
        </div>

        <!-- Gallery Preview Section -->
        <section class="gallery-preview">
            <div class="preview-header">
                <h2 class="section-title">Featured Artworks</h2>
                <a href="gallery.php" class="view-all">View All Artworks <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <?php if (empty($gallery_stats['featured_artworks'])): ?>
                <div style="text-align: center; padding: 3rem; background: #f8f9fa; border-radius: 12px;">
                    <i class="fas fa-images" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h3 style="color: #666; margin-bottom: 0.5rem;">Gallery Collection</h3>
                    <p style="color: #888; margin-bottom: 1.5rem;">Browse our curated collection of fine art</p>
                    <a href="gallery.php" class="action-btn" style="background: var(--Primary); color: white;">Browse Gallery</a>
                </div>
            <?php else: ?>
                <div class="artworks-grid">
                    <?php foreach ($gallery_stats['featured_artworks'] as $artwork): ?>
                    <div class="artwork-card">
                        <div class="artwork-status status-<?php echo htmlspecialchars($artwork['status'] ?? 'available'); ?>">
                            <?php echo ucfirst(htmlspecialchars($artwork['status'] ?? 'available')); ?>
                        </div>
                        <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($artwork['title']); ?>" 
                             class="artwork-image"
                             onerror="this.src='gallery/default/placeholder.jpg'">
                        <div class="artwork-info">
                            <h4 class="artwork-title"><?php echo htmlspecialchars($artwork['title']); ?></h4>
                            <p class="artwork-artist">By <?php echo htmlspecialchars($artwork['artist_name'] ?: 'Gallery Artist'); ?></p>
                            <?php if (!empty($artwork['price'])): ?>
                            <p class="artwork-price">$<?php echo number_format($artwork['price'], 2); ?></p>
                            <?php endif; ?>
                            <p class="artwork-date">Added <?php echo date('M Y', strtotime($artwork['upload_date'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- 2. Dashboard Main Photo -->
        <section class="image-section">
            <div class="dashboard-image">
                <?php if (!file_exists('images/Dashboard_main.jpg')): ?>
                    <div class="image-placeholder">
                        Image: Dashboard_main.jpg
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- 3. Mission Statement -->
        <section class="mission-section">
            <div class="mission-text">
                Streamlining art gallery management with a centralized platform for artists, inventory, and customer inquiries
            </div>
        </section>

        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $gallery_stats['total_artworks']; ?></div>
                <div class="stat-label">Total Artworks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $gallery_stats['available_artworks']; ?></div>
                <div class="stat-label">Available Now</div>
            </div>
            <?php if (isAdmin()): ?>
            <div class="stat-card">
                <div class="stat-number">$0</div>
                <div class="stat-label">Monthly Sales</div>
            </div>
            <?php else: ?>
            <div class="stat-card">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Support Available</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 5. Footer -->
    <footer class="dashboard-footer">
        <p>&copy; 2024 ArtFlow®. All rights reserved. | Professional Art Gallery Management System</p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem;">
            <a href="gallery.php" style="color: var(--Primary); text-decoration: none;">Gallery</a> | 
            <a href="artists.php" style="color: var(--Primary); text-decoration: none;">Artists</a> | 
            <a href="contact.php" style="color: var(--Primary); text-decoration: none;">Contact</a>
            <?php if (isAdmin()): ?>
             | <a href="admin.php" style="color: var(--Admin); text-decoration: none;">Admin Panel</a>
            <?php endif; ?>
        </p>
    </footer>
</body>
</html>