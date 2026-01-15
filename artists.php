<?php
// artist.php - Artist Directory (Display Only)
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// Handle artist search/filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$specialization = isset($_GET['specialization']) ? $_GET['specialization'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT * FROM artists WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR display_name LIKE ? OR email LIKE ? OR specialization LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($specialization) {
    $query .= " AND specialization LIKE ?";
    $params[] = "%$specialization%";
}

if ($status_filter) {
    if ($status_filter == 'featured') {
        $query .= " AND is_featured = 1";
    } elseif ($status_filter == 'verified') {
        $query .= " AND is_verified = 1";
    }
}

// Add sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'display_name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$valid_sorts = ['first_name', 'last_name', 'display_name', 'specialization', 'years_experience', 'created_at'];
$sort = in_array($sort, $valid_sorts) ? $sort : 'display_name';
$order = $order === 'desc' ? 'desc' : 'asc';

$query .= " ORDER BY $sort $order";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique specializations for filter dropdown
    $spec_stmt = $pdo->query("SELECT DISTINCT specialization FROM artists WHERE specialization IS NOT NULL AND specialization != ''");
    $specializations = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get counts
    $count_stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(is_featured) as featured,
        SUM(is_verified) as verified
        FROM artists");
    $counts = $count_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Error loading artists: " . $e->getMessage();
    $artists = [];
    $specializations = [];
    $counts = ['total' => 0, 'featured' => 0, 'verified' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Directory - ArtFlow®</title>
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
            --Featured: #FFD700;
            --Verified: #4CAF50;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header - Match Dashboard Style */
        .dashboard-header {
            padding: 1.5rem 0;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 2rem;
            background: white;
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
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.1), rgba(78, 205, 196, 0.1));
            border: 1px solid rgba(255,107,107,0.2);
            color: var(--Admin);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            background: var(--Admin);
            color: white;
        }

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

        /* Page Header */
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--Text-Headline);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }

        .stat-card.total { border-left-color: var(--Primary); }
        .stat-card.featured { border-left-color: var(--Featured); }
        .stat-card.verified { border-left-color: var(--Verified); }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card.total .stat-number { color: var(--Primary); }
        .stat-card.featured .stat-number { color: var(--Featured); }
        .stat-card.verified .stat-number { color: var(--Verified); }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--Text-Headline);
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--Primary);
            outline: none;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--Primary), var(--Secondary));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 17, 203, 0.3);
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn-secondary:hover {
            background: #555;
        }

        /* Sort Controls */
        .sort-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .sort-controls select {
            padding: 0.5rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.9rem;
        }

        /* Artists Grid */
        .artists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .artist-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .artist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .artist-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--Primary), var(--Secondary));
            color: white;
            position: relative;
        }

        .artist-badges {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-featured {
            background: var(--Featured);
            color: #333;
        }

        .badge-verified {
            background: var(--Verified);
            color: white;
        }

        .artist-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--Primary);
            margin-bottom: 1rem;
            border: 3px solid white;
        }

        .artist-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .artist-specialization {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .artist-body {
            padding: 1.5rem;
        }

        .artist-info {
            margin-bottom: 1.5rem;
        }

        .artist-info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: #666;
            font-size: 0.9rem;
        }

        .artist-info-item i {
            color: var(--Primary);
            width: 20px;
            text-align: center;
        }

        .artist-bio {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }

        .artist-bio.expanded {
            max-height: none;
        }

        .read-more {
            color: var(--Primary);
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
            margin-top: 0.5rem;
        }

        .artist-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }

        .artist-stats {
            display: flex;
            justify-content: space-around;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--Primary);
        }

        .stat-label {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1.5rem;
        }

        /* Footer */
        .main-footer {
            text-align: center;
            padding: 2rem 1rem;
            border-top: 2px solid #e0e0e0;
            color: #666;
            background: white;
            margin-top: 3rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .artists-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

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

            .filter-form {
                grid-template-columns: 1fr;
            }

            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }

            .artists-grid {
                grid-template-columns: 1fr;
            }

            .artist-stats {
                justify-content: space-around;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }
            
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .artist-name {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header - Match Dashboard Style -->
        <header class="dashboard-header">
            <div class="header-top">
                <div class="logo">ArtFlow®</div>
                <div class="header-features">
                    <ul class="nav-links">
                        <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="gallery.php"><i class="fas fa-images"></i> Gallery</a></li>
                        <li><a href="artist.php" class="active"><i class="fas fa-palette"></i> Artists</a></li>
                        <li><a href="admin.php"><i class="fas fa-cog"></i> Admin</a></li>
                        <li><a href="admin_inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
                        <li><a href="admin_upload.php"><i class="fas fa-plus"></i> Add Artwork</a></li>
                    </ul>
                    <div class="user-info">
                        <span class="user-badge">
                            <i class="fas fa-crown"></i>
                            Admin
                        </span>
                        <div>
                            <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?>
                        </div>
                        <a href="logout.php" style="color: inherit; text-decoration: none;">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-palette"></i> Artist Directory
            </h1>
            <p>Browse all artists in the ArtFlow gallery. View profiles, specialties, and artist statistics.</p>
            
            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $counts['total']; ?></div>
                    <div class="stat-label">Total Artists</div>
                </div>
                <div class="stat-card featured">
                    <div class="stat-number"><?php echo $counts['featured']; ?></div>
                    <div class="stat-label">Featured Artists</div>
                </div>
                <div class="stat-card verified">
                    <div class="stat-number"><?php echo $counts['verified']; ?></div>
                    <div class="stat-label">Verified Artists</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="search">Search Artists</label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Name, email, or specialization...">
                </div>
                
                <div class="form-group">
                    <label for="specialization">Specialization</label>
                    <select id="specialization" name="specialization">
                        <option value="">All Specializations</option>
                        <?php foreach ($specializations as $spec): ?>
                        <option value="<?php echo htmlspecialchars($spec); ?>"
                                <?php echo $specialization == $spec ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($spec); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="featured" <?php echo $status_filter == 'featured' ? 'selected' : ''; ?>>Featured Only</option>
                        <option value="verified" <?php echo $status_filter == 'verified' ? 'selected' : ''; ?>>Verified Only</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="artist.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sort Controls -->
        <div class="sort-controls">
            <span style="font-weight: 600; color: var(--Text-Headline);">Sort by:</span>
            <select onchange="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['sort' => 'display_name', 'order' => 'asc'])); ?>'.replace('sort=display_name', 'sort=' + this.value)">
                <option value="display_name" <?php echo $sort == 'display_name' ? 'selected' : ''; ?>>Name</option>
                <option value="specialization" <?php echo $sort == 'specialization' ? 'selected' : ''; ?>>Specialization</option>
                <option value="years_experience" <?php echo $sort == 'years_experience' ? 'selected' : ''; ?>>Experience</option>
                <option value="created_at" <?php echo $sort == 'created_at' ? 'selected' : ''; ?>>Date Added</option>
            </select>
            
            <select onchange="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['order' => 'asc'])); ?>'.replace('order=asc', 'order=' + this.value)">
                <option value="asc" <?php echo $order == 'asc' ? 'selected' : ''; ?>>Ascending</option>
                <option value="desc" <?php echo $order == 'desc' ? 'selected' : ''; ?>>Descending</option>
            </select>
            
            <span style="margin-left: auto; color: #666;">
                Showing <?php echo count($artists); ?> of <?php echo $counts['total']; ?> artists
            </span>
        </div>

        <!-- Artists Grid -->
        <div class="artists-grid">
            <?php if (empty($artists)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3>No Artists Found</h3>
                    <p><?php echo $search ? 'No artists match your search criteria.' : 'No artists in the directory yet.'; ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($artists as $artist): 
                    // Get artwork count for this artist
                    $artwork_stmt = $pdo->prepare("SELECT COUNT(*) FROM artworks WHERE artist_id = ?");
                    $artwork_stmt->execute([$artist['id']]);
                    $artwork_count = $artwork_stmt->fetchColumn();
                    
                    // Get total value of artist's artworks
                    $value_stmt = $pdo->prepare("SELECT SUM(price) FROM artworks WHERE artist_id = ? AND is_available = 1");
                    $value_stmt->execute([$artist['id']]);
                    $total_value = $value_stmt->fetchColumn() ?? 0;
                ?>
                    <div class="artist-card">
                        <div class="artist-header">
                            <div class="artist-badges">
                                <?php if ($artist['is_featured']): ?>
                                    <span class="badge badge-featured">
                                        <i class="fas fa-star"></i> Featured
                                    </span>
                                <?php endif; ?>
                                <?php if ($artist['is_verified']): ?>
                                    <span class="badge badge-verified">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="artist-avatar">
                                <?php 
                                $initials = substr($artist['first_name'], 0, 1) . substr($artist['last_name'], 0, 1);
                                echo strtoupper($initials);
                                ?>
                            </div>
                            
                            <div class="artist-name"><?php echo htmlspecialchars($artist['display_name']); ?></div>
                            <div class="artist-specialization"><?php echo htmlspecialchars($artist['specialization']); ?></div>
                        </div>
                        
                        <div class="artist-body">
                            <div class="artist-info">
                                <div class="artist-info-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($artist['email']); ?></span>
                                </div>
                                
                                <?php if ($artist['years_experience']): ?>
                                <div class="artist-info-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $artist['years_experience']; ?> years experience</span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($artist['website_url']): ?>
                                <div class="artist-info-item">
                                    <i class="fas fa-globe"></i>
                                    <a href="<?php echo htmlspecialchars($artist['website_url']); ?>" target="_blank" style="color: var(--Primary); text-decoration: none;">
                                        Website
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($artist['social_media_links']): 
                                    $social_links = json_decode($artist['social_media_links'], true);
                                    if (is_array($social_links)):
                                ?>
                                <div class="artist-info-item">
                                    <i class="fas fa-share-alt"></i>
                                    <div style="display: flex; gap: 10px;">
                                        <?php foreach ($social_links as $platform => $link): 
                                            $icon_map = [
                                                'instagram' => 'fab fa-instagram',
                                                'facebook' => 'fab fa-facebook',
                                                'twitter' => 'fab fa-twitter',
                                                'linkedin' => 'fab fa-linkedin',
                                                'pinterest' => 'fab fa-pinterest',
                                                'youtube' => 'fab fa-youtube',
                                                'tiktok' => 'fab fa-tiktok'
                                            ];
                                            $icon = $icon_map[$platform] ?? 'fas fa-link';
                                        ?>
                                            <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" 
                                               title="<?php echo ucfirst($platform); ?>" 
                                               style="color: var(--Primary); font-size: 1.1rem;">
                                                <i class="<?php echo $icon; ?>"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; endif; ?>
                            </div>
                            
                            <?php if ($artist['bio']): ?>
                            <div class="artist-bio" id="bio-<?php echo $artist['id']; ?>">
                                <?php echo nl2br(htmlspecialchars(substr($artist['bio'], 0, 150))); ?>
                                <?php if (strlen($artist['bio']) > 150): ?>
                                    <span class="read-more" onclick="toggleBio(<?php echo $artist['id']; ?>)">...Read more</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="artist-footer">
                            <div class="artist-stats">
                                <div class="stat">
                                    <div class="stat-number"><?php echo $artwork_count; ?></div>
                                    <div class="stat-label">Artworks</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number">$<?php echo number_format($total_value, 0); ?></div>
                                    <div class="stat-label">Value</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number"><?php echo $artist['years_experience']; ?></div>
                                    <div class="stat-label">Years</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <p>&copy; 2024 ArtFlow®. All rights reserved. | Professional Art Gallery Management System</p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem;">
            <span style="color: var(--Featured);">● Featured</span> | 
            <span style="color: var(--Verified);">● Verified</span> | 
            <span style="color: var(--Primary);">● Regular</span>
        </p>
    </footer>

    <script>
    // Toggle bio read more/less
    function toggleBio(artistId) {
        const bioElement = document.getElementById('bio-' + artistId);
        const readMoreLink = bioElement.querySelector('.read-more');
        
        if (bioElement.classList.contains('expanded')) {
            bioElement.classList.remove('expanded');
            readMoreLink.textContent = '...Read more';
        } else {
            bioElement.classList.add('expanded');
            readMoreLink.textContent = 'Read less';
        }
    }
    
    // Initialize bios that are expanded by URL hash
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#bio-')) {
            const artistId = hash.replace('#bio-', '');
            toggleBio(artistId);
        }
    });
    </script>
</body>
</html>