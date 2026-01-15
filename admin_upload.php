<?php
// admin_upload.php - 
session_start();
require_once 'config.php';

// Check if user is logged in (all users are admins in ArtFlow)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// Initialize variables
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $artist_id = !empty($_POST['artist_id']) ? $_POST['artist_id'] : NULL;
    $category = $_POST['category'] ?? '';
    $medium = $_POST['medium'] ?? '';
    $dimensions = $_POST['dimensions'] ?? '';
    $price = $_POST['price'] ?? 0;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $stock_quantity = $_POST['stock_quantity'] ?? 1;
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'images/artworks/';
        
        // Create subfolder if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Check file type
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($imageFileType, $allowed_types)) {
            // Check file size (5MB max)
            if ($_FILES['image']['size'] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                } else {
                    $error = "Sorry, there was an error uploading your file.";
                }
            } else {
                $error = "File is too large. Maximum size is 5MB.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
        }
    } else {
        $error = "Please upload an image file.";
    }
    
    // Insert into BOTH databases if no error
    if (empty($error) && !empty($image_url)) {
        try {
            // Start transaction to ensure both inserts work
            $pdo->beginTransaction();
            
           
            // 1. INSERT INTO ARTWORKS TABLE 
    
            $stmt = $pdo->prepare("INSERT INTO artworks (title, description, artist_id, category, medium, dimensions, price, image_url, is_available, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $artist_id, $category, $medium, $dimensions, $price, $image_url, $is_available, $stock_quantity]);
            
            $artwork_id = $pdo->lastInsertId(); // Get the new ID
            
            
            // 2. ALSO INSERT INTO GALLERY TABLE 
        
            // Convert is_available to gallery status
            $gallery_status = $is_available ? 'available' : 'sold';
            
            // For gallery table, we need to handle the user_id differently
            // If artist_id exists in artists table, we might need to get corresponding user_id
            $gallery_artist_id = NULL;
            if ($artist_id) {
                // Try to get user_id from artists table if it exists
                $artist_stmt = $pdo->prepare("SELECT user_id FROM artists WHERE id = ?");
                $artist_stmt->execute([$artist_id]);
                $artist_data = $artist_stmt->fetch(PDO::FETCH_ASSOC);
                $gallery_artist_id = $artist_data['user_id'] ?? NULL;
            }
            
            // Insert into gallery table
            $gallery_stmt = $pdo->prepare("
                INSERT INTO gallery 
                (title, description, artist_id, category, price, image_path, status, featured, upload_date, last_updated) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
            ");
            
            $gallery_stmt->execute([
                $title, 
                $description, 
                $gallery_artist_id, // Use user_id if available, otherwise NULL
                $category, 
                $price, 
                $image_url, // Use same image path
                $gallery_status
            ]);
            
            $gallery_id = $pdo->lastInsertId();
            
            // Commit transaction
            $pdo->commit();
            
            $success = "Artwork added successfully! It will appear in both the gallery and inventory.";
            
            // Clear form
            $_POST = array();
            
        } catch(PDOException $e) {
            // Rollback transaction if any error occurs
            $pdo->rollBack();
            $error = "Error adding artwork: " . $e->getMessage();
        }
    }
}

// Get all artists for dropdown
$artists = [];
try {
    $stmt = $pdo->query("SELECT id, display_name FROM artists ORDER BY display_name");
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading artists: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Artwork - ArtFlow</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f9f9f9;
        }
        
        input:focus, textarea:focus, select:focus {
            border-color: #6a11cb;
            background: white;
            box-shadow: 0 0 0 3px rgba(106, 17, 203, 0.1);
            outline: none;
        }
        
        .form-row {
            display: flex;
            gap: 25px;
            margin-bottom: 10px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 30px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(106, 17, 203, 0.3);
        }
        
        .btn-submit:active {
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            border: none;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #4cd964 0%, #5ac8fa 100%);
            color: white;
            border: none;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 25px;
            color: #6a11cb;
            text-decoration: none;
            font-weight: 500;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: rgba(106, 17, 203, 0.1);
            transform: translateX(-5px);
        }
        
        small {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }
        
        .preview-container {
            margin-top: 15px;
            text-align: center;
        }
        
        .image-preview {
            max-width: 300px;
            max-height: 200px;
            border-radius: 10px;
            border: 2px dashed #ddd;
            padding: 10px;
            margin: 0 auto;
        }
        
        .info-box {
            background: linear-gradient(135deg, rgba(106, 17, 203, 0.1), rgba(37, 117, 252, 0.1));
            border-left: 4px solid #6a11cb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #555;
        }
        
        .info-box i {
            color: #6a11cb;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .upload-container {
                padding: 20px;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Include your existing header/navbar
    if (file_exists('navbar.php')) {
        include 'navbar.php';
    } else if (file_exists('header.php')) {
        include 'header.php';
    } else {
        echo '<header style="background: linear-gradient(45deg, #6a11cb, #2575fc); padding: 20px; color: white;">
                <div style="max-width: 1200px; margin: 0 auto;">
                    <a href="dashboard.php" style="color: white; text-decoration: none; font-size: 24px; font-weight: bold;">
                        <i class="fas fa-palette"></i> ArtFlow
                    </a>
                </div>
              </header>';
    }
    ?>
    
    <div class="upload-container">
        <a href="admin_inventory.php?tab=artworks" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Inventory
        </a>
        
        <h1 style="color: #333; margin-bottom: 10px;">
            <i class="fas fa-plus-circle" style="color: #6a11cb;"></i> Upload New Artwork
        </h1>
        <p style="color: #666; margin-bottom: 30px; font-size: 16px;">
            Add a new artwork to the gallery. Images will be saved in <code>images/artworks/</code> folder.
        </p>
        
        <!-- Info Box -->
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>New Feature:</strong> Artworks are now saved to BOTH the gallery and inventory systems. 
            Your artwork will appear on the gallery page immediately after upload!
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> 
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="artworkForm">
            <div class="form-group">
                <label for="title"><i class="fas fa-heading"></i> Artwork Title *</label>
                <input type="text" id="title" name="title" required 
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                       placeholder="Enter artwork title (e.g., 'Desert Bloom', 'Urban Pulse')">
            </div>
            
            <div class="form-group">
                <label for="description"><i class="fas fa-align-left"></i> Description</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Describe the artwork, inspiration, techniques used, story behind it..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="artist_id"><i class="fas fa-user"></i> Artist</label>
                    <select id="artist_id" name="artist_id">
                        <option value="">-- Select Artist (Optional) --</option>
                        <?php foreach ($artists as $artist): ?>
                            <option value="<?php echo $artist['id']; ?>"
                                <?php echo (($_POST['artist_id'] ?? '') == $artist['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($artist['display_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Artist will be linked to both gallery and inventory</small>
                </div>
                
                <div class="form-group">
                    <label for="category"><i class="fas fa-tags"></i> Category *</label>
                    <select id="category" name="category" required>
                        <option value="">-- Select Category --</option>
                        <option value="Painting" <?php echo (($_POST['category'] ?? '') == 'Painting') ? 'selected' : ''; ?>>Painting</option>
                        <option value="Photography" <?php echo (($_POST['category'] ?? '') == 'Photography') ? 'selected' : ''; ?>>Photography</option>
                        <option value="Sculpture" <?php echo (($_POST['category'] ?? '') == 'Sculpture') ? 'selected' : ''; ?>>Sculpture</option>
                        <option value="Digital Art" <?php echo (($_POST['category'] ?? '') == 'Digital Art') ? 'selected' : ''; ?>>Digital Art</option>
                        <option value="Mixed Media" <?php echo (($_POST['category'] ?? '') == 'Mixed Media') ? 'selected' : ''; ?>>Mixed Media</option>
                        <option value="Other" <?php echo (($_POST['category'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="medium"><i class="fas fa-paint-brush"></i> Medium</label>
                    <input type="text" id="medium" name="medium" 
                           value="<?php echo htmlspecialchars($_POST['medium'] ?? ''); ?>"
                           placeholder="e.g., Oil on canvas, Acrylic, Digital print, Bronze">
                </div>
                
                <div class="form-group">
                    <label for="dimensions"><i class="fas fa-expand-alt"></i> Dimensions</label>
                    <input type="text" id="dimensions" name="dimensions" 
                           value="<?php echo htmlspecialchars($_POST['dimensions'] ?? ''); ?>"
                           placeholder="e.g., 24×36 inches, 50×70 cm, Height: 15″">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="price"><i class="fas fa-tag"></i> Price ($) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required 
                           value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                           placeholder="0.00">
                    <small>Enter 0 for "Price on Request" or non-sale items</small>
                </div>
                
                <div class="form-group">
                    <label for="stock_quantity"><i class="fas fa-box"></i> Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="1" value="1">
                    <small>Number of available copies (usually 1 for original artwork)</small>
                </div>
            </div>
            
            <div class="form-group" style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f9f9f9; border-radius: 10px;">
                <input type="checkbox" id="is_available" name="is_available" value="1" checked 
                       style="width: auto; transform: scale(1.3);">
                <label for="is_available" style="margin-bottom: 0; font-weight: 600; color: #333;">
                    <i class="fas fa-check-circle" style="color: #4CAF50;"></i> 
                    <span style="font-size: 16px;">Available for Sale</span>
                </label>
                <small style="margin-left: auto; color: #666;">
                    Uncheck if sold or not for sale
                </small>
            </div>
            
            <div class="form-group">
                <label for="image"><i class="fas fa-image"></i> Artwork Image *</label>
                <input type="file" id="image" name="image" accept="image/*" required 
                       onchange="previewImage(this)">
                <small>Upload high-quality image (JPG, PNG, GIF, WEBP, max 5MB)</small>
                
                <div class="preview-container">
                    <img id="imagePreview" class="image-preview" src="" alt="Image preview" style="display: none;">
                </div>
            </div>
            
            <div class="info-box" style="background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(106, 17, 203, 0.1)); border-left-color: #4CAF50;">
                <i class="fas fa-sync-alt"></i>
                <strong>Dual System:</strong> This artwork will be saved to both:
                <ul style="margin: 10px 0 0 20px; color: #555;">
                    <li><strong>Gallery System</strong> - Appears on gallery.php immediately</li>
                    <li><strong>Inventory System</strong> - Available in admin inventory management</li>
                </ul>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Upload Artwork to Gallery & Inventory
            </button>
        </form>
    </div>
    
    <script>
        // Image preview functionality
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        }
        
        // Form validation
        document.getElementById('artworkForm').addEventListener('submit', function(e) {
            const price = document.getElementById('price').value;
            if (price < 0) {
                e.preventDefault();
                alert('Price cannot be negative.');
                return false;
            }
            
            const fileInput = document.getElementById('image');
            if (fileInput.files.length > 0) {
                const fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 5) {
                    e.preventDefault();
                    alert('File size must be less than 5MB.');
                    return false;
                }
            }
            
            // Confirm dual save
            if (!confirm('This artwork will be saved to BOTH the gallery and inventory systems. Continue?')) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
    
    <?php 
    // Include your existing footer
    if (file_exists('footer.php')) {
        include 'footer.php';
    }
    ?>
</body>
</html>