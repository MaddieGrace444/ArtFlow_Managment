<?php
// gallery.php - Public gallery browsing
include 'config.php';
requireLogin(); // User must be logged in to view

// Get filter parameters
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Initialize variables
$artworks = [];
$categories = [];
$total_artworks = 0;
$featured_count = 0;

try {
    // Only show available items to users
    $status = 'available';
    
    // Build query
    $query = "SELECT g.*, u.username as artist_username, u.first_name, u.last_name 
              FROM gallery g 
              LEFT JOIN users u ON g.artist_id = u.id 
              WHERE g.status = :status";
    $params = ['status' => $status];
    
    if ($category) {
        $query .= " AND g.category = :category";
        $params['category'] = $category;
    }
    
    if ($search) {
        $query .= " AND (g.title LIKE :search OR g.description LIKE :search OR u.username LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
        $params['search'] = "%$search%";
    }
    
    $query .= " ORDER BY g.featured DESC, g.upload_date DESC";
    
    // Execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_artworks = count($artworks);
    
    // Count featured artworks
    foreach ($artworks as $artwork) {
        if ($artwork['featured']) {
            $featured_count++;
        }
    }
    
    // Get distinct categories for filter
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM gallery WHERE status = 'available' ORDER BY category");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Art Gallery - ArtFlow®</title>
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

        /* Header - Match Dashboard */
        .gallery-header {
            padding: 1.5rem 0;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 2rem;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: var(--Link-Link, #2C2825);
            font-family: 'Caprasimo', serif;
            font-size: 30px;
            font-style: normal;
            font-weight: 400;
            line-height: 92%;
            letter-spacing: -0.9px;
        }

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

        /* Gallery Content */
        .gallery-content {
            padding: 2rem 0;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--Text-Headline);
            margin-bottom: 1rem;
        }

        .page-header p {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Filter Section */
        .filter-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 1px solid #e0e0e0;
        }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--Text-Headline);
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--Primary);
            outline: none;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--Primary), var(--Secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(106, 17, 203, 0.3);
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn-secondary:hover {
            background: #555;
        }

        /* Artworks Grid */
        .artworks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
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
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .featured-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
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

        .artwork-image-container {
            width: 100%;
            height: 250px;
            overflow: hidden;
        }

        .artwork-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .artwork-card:hover .artwork-image {
            transform: scale(1.05);
        }

        .artwork-info {
            padding: 1.5rem;
        }

        .artwork-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--Text-Headline);
            margin-bottom: 0.8rem;
            line-height: 1.3;
        }

        .artwork-meta {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .artist, .category {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #666;
        }

        .artist i, .category i {
            color: var(--Primary);
        }

        .artwork-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .artwork-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--Primary);
        }

        .medium, .year {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.9rem;
        }

        .artwork-actions {
            display: flex;
            gap: 10px;
        }

        .view-details, .admin-edit {
            flex: 1;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .view-details {
            background: var(--Primary);
            color: white;
            border: none;
        }

        .view-details:hover {
            background: var(--Secondary);
        }

        .admin-edit {
            background: var(--Admin);
            color: white;
            display: <?php echo isAdmin() ? 'block' : 'none'; ?>;
        }

        .admin-edit:hover {
            background: #ff8e8e;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: #f8f9fa;
            border-radius: 12px;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1.5rem;
        }

        .empty-state h2 {
            color: #666;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .empty-state p {
            color: #888;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Gallery Stats */
        .gallery-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }

        .stat-item {
            text-align: center;
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

        /* Footer */
        .main-footer {
            text-align: center;
            padding: 2rem 1rem;
            border-top: 2px solid #e0e0e0;
            color: #666;
            background: white;
            margin-top: auto;
            width: 100%;
        }

        /* Error Message */
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #ffcdd2;
            text-align: center;
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

            .artworks-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .gallery-stats {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }
            
            .artwork-image-container {
                height: 200px;
            }
            
            .artwork-info {
                padding: 1rem;
            }
            
            .artwork-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
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

        <!-- Gallery Content -->
        <div class="gallery-content">
            <div class="page-header">
                <h1>Art Gallery Collection</h1>
                <p>Browse our curated collection of fine art and discover exceptional pieces from talented artists</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                    <p>Please check your database connection and ensure the gallery table exists.</p>
                </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="search">Search Artworks</label>
                        <input type="text" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Search by title, artist, or description...">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"
                                        <?php echo ($category == $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <?php if ($search || $category): ?>
                            <a href="gallery.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Artworks Grid -->
            <?php if (empty($artworks)): ?>
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <h2>No Artworks Found</h2>
                    <p>
                        <?php 
                        if ($search || $category) {
                            echo 'No artworks match your search criteria.';
                        } elseif (isset($error_message)) {
                            echo 'Database error occurred. Please try again later.';
                        } else {
                            echo 'No artworks available in the gallery at the moment.';
                        }
                        ?>
                    </p>
                    <?php if ($search || $category): ?>
                    <a href="gallery.php" class="btn btn-primary">
                        View All Artworks
                    </a>
                    <?php endif; ?>
                    <?php if (isAdmin() && !isset($error_message)): ?>
                    <p style="margin-top: 1rem;">
                        <a href="admin_upload.php" style="color: var(--Primary);">
                            <i class="fas fa-plus"></i> Add your first artwork
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="artworks-grid">
                    <?php foreach ($artworks as $artwork): ?>
                    <div class="artwork-card">
                        <?php if ($artwork['featured']): ?>
                        <div class="featured-badge">
                            <i class="fas fa-star"></i> Featured
                        </div>
                        <?php endif; ?>
                        
                        <div class="status-badge status-<?php echo htmlspecialchars($artwork['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($artwork['status'])); ?>
                        </div>
                        
                        <div class="artwork-image-container">
                            <?php 
                            $image_path = htmlspecialchars($artwork['image_path']);
                            $alt_text = htmlspecialchars($artwork['title']);
                            ?>
                            <img src="<?php echo $image_path; ?>" 
                                 alt="<?php echo $alt_text; ?>" 
                                 class="artwork-image"
                                 onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300/cccccc/969696?text=Art+Image'">
                        </div>
                        
                        <div class="artwork-info">
                            <h3 class="artwork-title"><?php echo htmlspecialchars($artwork['title']); ?></h3>
                            
                            <div class="artwork-meta">
                                <?php if (!empty($artwork['first_name'])): ?>
                                <div class="artist">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($artwork['first_name'] . ' ' . $artwork['last_name']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($artwork['category'])): ?>
                                <div class="category">
                                    <i class="fas fa-tag"></i>
                                    <?php echo htmlspecialchars($artwork['category']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($artwork['description'])): ?>
                            <p class="artwork-description">
                                <?php echo htmlspecialchars(substr($artwork['description'], 0, 120)); ?>
                                <?php if (strlen($artwork['description']) > 120): ?>...<?php endif; ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="artwork-details">
                                <?php if (!empty($artwork['price'])): ?>
                                <div class="price">$<?php echo number_format($artwork['price'], 2); ?></div>
                                <?php endif; ?>
                                
                                <?php if (!empty($artwork['medium'])): ?>
                                <div class="medium">
                                    <i class="fas fa-paint-brush"></i>
                                    <?php echo htmlspecialchars($artwork['medium']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($artwork['year_created'])): ?>
                                <div class="year">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo htmlspecialchars($artwork['year_created']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="artwork-actions">
                                <button class="view-details" onclick="viewArtworkDetails(<?php echo $artwork['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <?php if (isAdmin()): ?>
                                <a href="admin_edit.php?id=<?php echo $artwork['id']; ?>" class="admin-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Gallery Stats -->
                <div class="gallery-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_artworks; ?></div>
                        <div class="stat-label">Artworks Displayed</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($categories); ?></div>
                        <div class="stat-label">Categories</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $featured_count; ?></div>
                        <div class="stat-label">Featured Pieces</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <p>&copy; 2024 ArtFlow®. All rights reserved. | Professional Art Gallery Management System</p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem;">
            <a href="dashboard.php" style="color: var(--Primary); text-decoration: none;">Dashboard</a> | 
            <a href="gallery.php" style="color: var(--Primary); text-decoration: none;">Gallery</a> | 
            <a href="contact.php" style="color: var(--Primary); text-decoration: none;">Contact</a>
            <?php if (isAdmin()): ?>
             | <a href="admin.php" style="color: var(--Admin); text-decoration: none;">Admin Panel</a>
            <?php endif; ?>
        </p>
    </footer>

    <script>
    function viewArtworkDetails(artworkId) {
        alert('Artwork detail view coming soon!\nArtwork ID: ' + artworkId);
    }
    </script>
</body>
</html>