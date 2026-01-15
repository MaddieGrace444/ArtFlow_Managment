<?php
// admin_inventory.php - Gallery & Artworks Inventory Management
// Features: gallery and artworks, UPDATE and DELETE
include 'config.php';
requireAdmin();

$success = '';
$error = '';
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'gallery'; // 'gallery' or 'artworks'


// HANDLE FORM SUBMISSIONS FOR BOTH TABLES


// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $artwork_id = intval($_POST['artwork_id']);
    $new_status = $_POST['new_status'];
    $table = $_POST['table'] ?? 'gallery';
    
    try {
        if ($table == 'gallery') {
            $stmt = $pdo->prepare("UPDATE gallery SET status = ?, last_updated = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $artwork_id]);
        } else {
            // For artworks table, convert status to is_available
            $is_available = ($new_status == 'available') ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE artworks SET is_available = ? WHERE id = ?");
            $stmt->execute([$is_available, $artwork_id]);
        }
        $success = "Artwork status updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Handle Artwork Deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_artwork'])) {
    $artwork_id = intval($_POST['artwork_id']);
    $table = $_POST['table'] ?? 'gallery';
    
    try {
        if ($table == 'gallery') {
            $stmt = $pdo->prepare("SELECT title FROM gallery WHERE id = ?");
            $stmt->execute([$artwork_id]);
            $artwork_title = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("SELECT title FROM artworks WHERE id = ?");
            $stmt->execute([$artwork_id]);
            $artwork_title = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("DELETE FROM artworks WHERE id = ?");
        }
        
        if ($stmt->execute([$artwork_id])) {
            $success = "Artwork '{$artwork_title}' has been deleted.";
        }
    } catch (PDOException $e) {
        $error = "Error deleting artwork: " . $e->getMessage();
    }
}

// Handle Bulk Actions for GALLERY table only 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action']) && $current_tab == 'gallery') {
    if (isset($_POST['selected_artworks']) && !empty($_POST['selected_artworks'])) {
        $selected_ids = $_POST['selected_artworks'];
        $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
        
        switch ($_POST['bulk_action']) {
            case 'mark_available':
                $stmt = $pdo->prepare("UPDATE gallery SET status = 'available', last_updated = NOW() WHERE id IN ($placeholders)");
                $stmt->execute($selected_ids);
                $success = count($selected_ids) . " artwork(s) marked as Available";
                break;
                
            case 'mark_sold':
                $stmt = $pdo->prepare("UPDATE gallery SET status = 'sold', last_updated = NOW() WHERE id IN ($placeholders)");
                $stmt->execute($selected_ids);
                $success = count($selected_ids) . " artwork(s) marked as Sold";
                break;
                
            case 'mark_reserved':
                $stmt = $pdo->prepare("UPDATE gallery SET status = 'reserved', last_updated = NOW() WHERE id IN ($placeholders)");
                $stmt->execute($selected_ids);
                $success = count($selected_ids) . " artwork(s) marked as Reserved";
                break;
                
            case 'delete_selected':
                $stmt = $pdo->prepare("DELETE FROM gallery WHERE id IN ($placeholders)");
                $stmt->execute($selected_ids);
                $success = count($selected_ids) . " artwork(s) permanently deleted";
                break;
        }
    } else {
        $error = "Please select at least one artwork.";
    }
}


// GET INVENTORY DATA FOR SELECTED TAB

try {
    if ($current_tab == 'artworks') {
        // GET DATA FROM ARTWORKS TABLE
        $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
        
        $query = "SELECT a.*, ar.display_name as artist_name
                  FROM artworks a 
                  LEFT JOIN artists ar ON a.artist_id = ar.id 
                  WHERE 1=1";
        
        $params = [];
        
        if ($category_filter) {
            $query .= " AND a.category = ?";
            $params[] = $category_filter;
        }
        
        if ($search) {
            $query .= " AND (a.title LIKE ? OR a.description LIKE ? OR ar.display_name LIKE ?)";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $query .= " ORDER BY a.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get categories for filter
        $cat_stmt = $pdo->query("SELECT DISTINCT category FROM artworks WHERE category IS NOT NULL ORDER BY category");
        $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Counts for artworks table
        $total_items = count($items);
        $available_count = 0;
        $total_value = 0;
        foreach ($items as $item) {
            if ($item['is_available'] == 1) $available_count++;
            $total_value += $item['price'];
        }
        $unavailable_count = $total_items - $available_count;
        
    } else {
        // GET DATA FROM GALLERY TABLE (YOUR ORIGINAL CODE)
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
        $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
        $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        
        $query = "SELECT g.*, 
                         u.first_name, u.last_name, u.username
                  FROM gallery g 
                  LEFT JOIN users u ON g.artist_id = u.id 
                  WHERE 1=1";
        
        $params = [];
        
        if ($status_filter) {
            $query .= " AND g.status = ?";
            $params[] = $status_filter;
        }
        
        if ($category_filter) {
            $query .= " AND g.category = ?";
            $params[] = $category_filter;
        }
        
        if ($search) {
            $query .= " AND (g.title LIKE ? OR g.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $query .= " ORDER BY 
                    CASE g.status 
                        WHEN 'available' THEN 1
                        WHEN 'reserved' THEN 2
                        WHEN 'sold' THEN 3
                        WHEN 'archived' THEN 4
                        ELSE 5
                    END,
                    g.featured DESC,
                    g.upload_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get status counts for gallery
        $status_stmt = $pdo->query("SELECT status, COUNT(*) as count FROM gallery GROUP BY status ORDER BY status");
        $status_counts = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get categories for gallery
        $cat_stmt = $pdo->query("SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL ORDER BY category");
        $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Counts for gallery
        $total_items = count($items);
        $available_count = 0;
        $sold_count = 0;
        $reserved_count = 0;
        $archived_count = 0;
        $total_value = 0;
        
        foreach ($status_counts as $stat) {
            switch ($stat['status']) {
                case 'available': $available_count = $stat['count']; break;
                case 'sold': $sold_count = $stat['count']; break;
                case 'reserved': $reserved_count = $stat['count']; break;
                case 'archived': $archived_count = $stat['count']; break;
            }
        }
        
        foreach ($items as $item) {
            $total_value += $item['price'];
        }
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $items = [];
    $categories = [];
    $total_items = 0;
    $available_count = 0;
    $unavailable_count = 0;
    $sold_count = 0;
    $reserved_count = 0;
    $archived_count = 0;
    $total_value = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - ArtFlow®</title>
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
            --Available: #4CAF50;
            --Sold: #F44336;
            --Reserved: #FF9800;
            --Archived: #9E9E9E;
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

        /* Header - Match Gallery Style */
        .inventory-header {
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
            border: 1px solid rgba(255, 107, 107, 0.2);
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

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            gap: 10px;
            background: white;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .tab-link {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            color: #666;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .tab-link:hover {
            background: #f0f0f0;
            color: var(--Primary);
        }
        
        .tab-link.active {
            background: linear-gradient(135deg, var(--Primary), var(--Secondary));
            color: white;
            box-shadow: 0 4px 12px rgba(106, 17, 203, 0.2);
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

        .stat-card.available { border-left-color: var(--Available); }
        .stat-card.sold { border-left-color: var(--Sold); }
        .stat-card.reserved { border-left-color: var(--Reserved); }
        .stat-card.archived { border-left-color: var(--Archived); }
        .stat-card.total { border-left-color: var(--Primary); }
        .stat-card.value { border-left-color: var(--Secondary); }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card.available .stat-number { color: var(--Available); }
        .stat-card.sold .stat-number { color: var(--Sold); }
        .stat-card.reserved .stat-number { color: var(--Reserved); }
        .stat-card.archived .stat-number { color: var(--Archived); }
        .stat-card.total .stat-number { color: var(--Primary); }
        .stat-card.value .stat-number { color: var(--Secondary); }

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

        .btn-danger {
            background: var(--Sold);
            color: white;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        /* Bulk Actions - Only show for gallery tab */
        .bulk-actions {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: <?php echo $current_tab == 'gallery' ? 'flex' : 'none'; ?>;
            gap: 1rem;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .select-all {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Inventory Table */
        .inventory-table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
        }

        .inventory-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--Text-Headline);
            border-bottom: 2px solid #e0e0e0;
        }

        .inventory-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .inventory-table tr:hover {
            background: #fafafa;
        }

        .inventory-table tr:last-child td {
            border-bottom: none;
        }

        /* Checkbox Column */
        .select-checkbox {
            width: 40px;
            text-align: center;
        }

        .select-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* Artwork Info */
        .artwork-info-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .artwork-thumbnail {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #e0e0e0;
        }

        .artwork-details {
            flex: 1;
        }

        .artwork-title {
            font-weight: 600;
            color: var(--Text-Headline);
            margin-bottom: 0.25rem;
        }

        .artwork-artist {
            color: #666;
            font-size: 0.9rem;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-available {
            background: rgba(76, 175, 80, 0.1);
            color: var(--Available);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .status-sold {
            background: rgba(244, 67, 54, 0.1);
            color: var(--Sold);
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .status-reserved {
            background: rgba(255, 152, 0, 0.1);
            color: var(--Reserved);
            border: 1px solid rgba(255, 152, 0, 0.3);
        }

        .status-archived {
            background: rgba(158, 158, 158, 0.1);
            color: var(--Archived);
            border: 1px solid rgba(158, 158, 158, 0.3);
        }

        /* Status Select */
        .status-select {
            padding: 0.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.9rem;
            cursor: pointer;
            transition: border-color 0.3s ease;
            min-width: 120px;
        }

        .status-select:focus {
            border-color: var(--Primary);
            outline: none;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            background: var(--Primary);
            color: white;
        }

        .btn-edit {
            background: var(--Secondary);
            color: white;
        }

        .btn-delete {
            background: var(--Sold);
            color: white;
            border: none;
            cursor: pointer;
        }

        /* Messages */
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .success {
            background: rgba(76, 175, 80, 0.1);
            color: var(--Available);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .error {
            background: rgba(244, 67, 54, 0.1);
            color: var(--Sold);
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
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

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            color: var(--Text-Headline);
        }

        .modal-body {
            margin-bottom: 1.5rem;
            color: #666;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .inventory-table {
                display: block;
                overflow-x: auto;
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

            .tab-navigation {
                flex-direction: column;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }

            .artwork-info-cell {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .artwork-thumbnail {
                width: 100%;
                height: 120px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .bulk-actions {
                flex-direction: column;
                align-items: stretch;
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
            
            .tab-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="inventory-header">
            <div class="header-top">
                <div class="logo">ArtFlow®</div>
                <div class="header-features">
                    <ul class="nav-links">
                        <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="gallery.php"><i class="fas fa-images"></i> Gallery</a></li>
                        <li><a href="admin.php"><i class="fas fa-cog"></i> Admin</a></li>
                        <li><a href="admin_inventory.php?tab=<?php echo $current_tab; ?>" class="active"><i class="fas fa-boxes"></i> Inventory</a></li>
                        <li><a href="artists.php"><i class="fas fa-palette"></i> Artists</a></li>
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

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <a href="?tab=gallery" class="tab-link <?php echo $current_tab == 'gallery' ? 'active' : ''; ?>">
                <i class="fas fa-images"></i> Gallery Items
            </a>
            <a href="?tab=artworks" class="tab-link <?php echo $current_tab == 'artworks' ? 'active' : ''; ?>">
                <i class="fas fa-palette"></i> Uploaded Artworks
            </a>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success; ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <?php if ($current_tab == 'gallery'): ?>
                    <i class="fas fa-images"></i> Gallery Inventory
                <?php else: ?>
                    <i class="fas fa-palette"></i> Artworks Inventory
                <?php endif; ?>
            </h1>
            <p>
                <?php if ($current_tab == 'gallery'): ?>
                    Manage artwork status, update availability, and remove artworks from the gallery
                <?php else: ?>
                    View and manage all uploaded artworks from the upload form
                <?php endif; ?>
            </p>
            
            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $total_items; ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
                
                <?php if ($current_tab == 'gallery'): ?>
                    <div class="stat-card available">
                        <div class="stat-number"><?php echo $available_count; ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-card sold">
                        <div class="stat-number"><?php echo $sold_count; ?></div>
                        <div class="stat-label">Sold</div>
                    </div>
                    <div class="stat-card reserved">
                        <div class="stat-number"><?php echo $reserved_count; ?></div>
                        <div class="stat-label">Reserved</div>
                    </div>
                    <div class="stat-card archived">
                        <div class="stat-number"><?php echo $archived_count; ?></div>
                        <div class="stat-label">Archived</div>
                    </div>
                <?php else: ?>
                    <div class="stat-card available">
                        <div class="stat-number"><?php echo $available_count; ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                    <div class="stat-card sold">
                        <div class="stat-number"><?php echo $unavailable_count; ?></div>
                        <div class="stat-label">Not Available</div>
                    </div>
                    <div class="stat-card value">
                        <div class="stat-number">$<?php echo number_format($total_value, 0); ?></div>
                        <div class="stat-label">Total Value</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <input type="hidden" name="tab" value="<?php echo $current_tab; ?>">
                
                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search ?? ''); ?>"
                           placeholder="<?php echo $current_tab == 'gallery' ? 'Title, artist, or description...' : 'Artwork title or artist...'; ?>">
                </div>
                
                <?php if ($current_tab == 'gallery'): ?>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="available" <?php echo ($status_filter ?? '') == 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="sold" <?php echo ($status_filter ?? '') == 'sold' ? 'selected' : ''; ?>>Sold</option>
                        <option value="reserved" <?php echo ($status_filter ?? '') == 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                        <option value="archived" <?php echo ($status_filter ?? '') == 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"
                                <?php echo ($category_filter ?? '') == $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="admin_inventory.php?tab=<?php echo $current_tab; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Actions Form (Gallery only) -->
        <?php if ($current_tab == 'gallery'): ?>
        <form method="POST" id="bulkForm">
            <input type="hidden" name="tab" value="<?php echo $current_tab; ?>">
            <div class="bulk-actions">
                <div class="select-all">
                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                    <label for="selectAll">Select All</label>
                </div>
                
                <select name="bulk_action" class="status-select" style="flex: 1;">
                    <option value="">Bulk Actions</option>
                    <option value="mark_available">Mark as Available</option>
                    <option value="mark_sold">Mark as Sold</option>
                    <option value="mark_reserved">Mark as Reserved</option>
                    <option value="delete_selected">Delete Selected</option>
                </select>
                
                <button type="button" class="btn btn-danger" onclick="confirmBulkAction()">
                    <i class="fas fa-play"></i> Apply
                </button>
            </div>
        <?php endif; ?>

            <!-- Inventory Table -->
            <div class="inventory-table-container">
                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No Items Found</h3>
                        <p><?php echo isset($search) && $search ? 'No items match your search criteria.' : 'No items in inventory.'; ?></p>
                        <?php if ($current_tab == 'artworks'): ?>
                            <a href="admin_upload.php" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-plus"></i> Upload New Artwork
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <table class="inventory-table">
                        <thead>
                            <tr>
                                <?php if ($current_tab == 'gallery'): ?>
                                <th class="select-checkbox"></th>
                                <?php endif; ?>
                                <th>Artwork</th>
                                <th>Category</th>
                                <th>Artist</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Update Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <?php if ($current_tab == 'gallery'): ?>
                                <td class="select-checkbox">
                                    <input type="checkbox" name="selected_artworks[]" 
                                           value="<?php echo $item['id']; ?>"
                                           class="artwork-checkbox">
                                </td>
                                <?php endif; ?>
                                <td>
                                    <div class="artwork-info-cell">
                                        <?php if ($current_tab == 'gallery'): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_path'] ?? 'https://via.placeholder.com/60x60/cccccc/969696?text=Art'); ?>" 
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://via.placeholder.com/60x60/cccccc/969696?text=Art'); ?>" 
                                        <?php endif; ?>
                                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                                             class="artwork-thumbnail"
                                             onerror="this.src='https://via.placeholder.com/60x60/cccccc/969696?text=Art'">
                                        <div class="artwork-details">
                                            <div class="artwork-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                            <div class="artwork-artist">
                                                ID: <?php echo $item['id']; ?> • 
                                                <?php if ($current_tab == 'gallery'): ?>
                                                    Added: <?php echo date('M d, Y', strtotime($item['upload_date'] ?? $item['created_at'])); ?>
                                                <?php else: ?>
                                                    Added: <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($item['category'] ?? 'Uncategorized'); ?></td>
                                <td>
                                    <?php if ($current_tab == 'gallery' && !empty($item['first_name'])): ?>
                                        <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                    <?php elseif ($current_tab == 'artworks' && !empty($item['artist_name'])): ?>
                                        <?php echo htmlspecialchars($item['artist_name']); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Unknown</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo number_format($item['price'] ?? 0, 2); ?></td>
                                <td>
                                    <?php if ($current_tab == 'gallery'): ?>
                                        <span class="status-badge status-<?php echo $item['status']; ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge <?php echo $item['is_available'] ? 'status-available' : 'status-sold'; ?>">
                                            <?php echo $item['is_available'] ? 'Available' : 'Not Available'; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="artwork_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="table" value="<?php echo $current_tab == 'gallery' ? 'gallery' : 'artworks'; ?>">
                                        
                                        <?php if ($current_tab == 'gallery'): ?>
                                            <select name="new_status" class="status-select" onchange="this.form.submit()">
                                                <option value="available" <?php echo ($item['status'] ?? '') == 'available' ? 'selected' : ''; ?>>Available</option>
                                                <option value="sold" <?php echo ($item['status'] ?? '') == 'sold' ? 'selected' : ''; ?>>Sold</option>
                                                <option value="reserved" <?php echo ($item['status'] ?? '') == 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                                                <option value="archived" <?php echo ($item['status'] ?? '') == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                            </select>
                                        <?php else: ?>
                                            <select name="new_status" class="status-select" onchange="this.form.submit()">
                                                <option value="available" <?php echo ($item['is_available'] ?? 0) == 1 ? 'selected' : ''; ?>>Available</option>
                                                <option value="not_available" <?php echo ($item['is_available'] ?? 0) == 0 ? 'selected' : ''; ?>>Not Available</option>
                                            </select>
                                        <?php endif; ?>
                                        
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($current_tab == 'gallery'): ?>
                                            <a href="gallery.php?view=<?php echo $item['id']; ?>" 
                                               class="btn-small btn-view" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="admin_edit.php?id=<?php echo $item['id']; ?>" 
                                               class="btn-small btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="admin_upload.php?edit=<?php echo $item['id']; ?>" 
                                               class="btn-small btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="btn-small btn-delete" 
                                                onclick="confirmSingleDelete(<?php echo $item['id']; ?>, '<?php echo addslashes($item['title']); ?>', '<?php echo $current_tab == 'gallery' ? 'gallery' : 'artworks'; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php if ($current_tab == 'gallery'): ?>
        </form>
        <?php endif; ?>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-exclamation-triangle" style="color: var(--Sold);"></i> Confirm Deletion</h3>
                </div>
                <div class="modal-body" id="modalMessage">
                    Are you sure you want to delete this artwork?
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="artwork_id" id="deleteArtworkId">
                        <input type="hidden" name="table" id="deleteTable">
                        <input type="hidden" name="delete_artwork" value="1">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <p>&copy; 2024 ArtFlow®. All rights reserved. | Professional Art Gallery Management System</p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem;">
            <span style="color: var(--Available);">● Available</span> | 
            <span style="color: var(--Sold);">● <?php echo $current_tab == 'gallery' ? 'Sold' : 'Not Available'; ?></span> | 
            <?php if ($current_tab == 'gallery'): ?>
            <span style="color: var(--Reserved);">● Reserved</span> | 
            <span style="color: var(--Archived);">● Archived</span>
            <?php endif; ?>
        </p>
    </footer>

    <script>
    // Modal functions
    function showModal() {
        document.getElementById('deleteModal').style.display = 'flex';
    }
    
    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    // Single artwork deletion
    function confirmSingleDelete(artworkId, artworkTitle, tableName) {
        document.getElementById('modalMessage').innerHTML = 
            `Are you sure you want to permanently delete "<strong>${artworkTitle}</strong>"?<br><br>
             <small style="color: var(--Sold);">This action cannot be undone. The artwork will be removed from the ${tableName} permanently.</small>`;
        document.getElementById('deleteArtworkId').value = artworkId;
        document.getElementById('deleteTable').value = tableName;
        showModal();
    }
    
    // Bulk deletion confirmation (gallery only)
    function confirmBulkAction() {
        const selectedCount = document.querySelectorAll('.artwork-checkbox:checked').length;
        const bulkAction = document.querySelector('select[name="bulk_action"]').value;
        
        if (selectedCount === 0) {
            alert('Please select at least one artwork.');
            return;
        }
        
        if (!bulkAction) {
            alert('Please select a bulk action.');
            return;
        }
        
        if (bulkAction === 'delete_selected') {
            if (confirm(`Are you sure you want to permanently delete ${selectedCount} selected artwork(s)?\n\nThis action cannot be undone.`)) {
                document.getElementById('bulkForm').submit();
            }
        } else {
            const actionText = bulkAction.replace('mark_', '').replace('_', ' ');
            if (confirm(`Apply "${actionText}" status to ${selectedCount} selected artwork(s)?`)) {
                document.getElementById('bulkForm').submit();
            }
        }
    }
    
    // Select all checkboxes (gallery only)
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.artwork-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    }
    
    // Close modal on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target === modal) {
            closeModal();
        }
    }
    
    // Close modal on ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
    
    // Update select all checkbox state (gallery only)
    <?php if ($current_tab == 'gallery'): ?>
    document.querySelectorAll('.artwork-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const totalCheckboxes = document.querySelectorAll('.artwork-checkbox').length;
            const checkedCheckboxes = document.querySelectorAll('.artwork-checkbox:checked').length;
            document.getElementById('selectAll').checked = checkedCheckboxes === totalCheckboxes;
        });
    });
    <?php endif; ?>
    </script>
</body>
</html>