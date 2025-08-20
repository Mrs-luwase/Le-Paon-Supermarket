<?php
session_start();
require_once 'config.php'; 

$login_error = '';

// Handle logout first
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Process login form
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch admin from DB
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        // Correct password → log admin in
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Invalid credentials.';
    }
}

// Check if admin is logged in AFTER processing login
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    // Admin is logged in, proceed with admin functionalities
    
    // Fetch categories for dropdowns
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY parent_id IS NULL DESC, name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add Category
    $category_message = '';
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        $parent_id = $_POST['parent_id'] === 'none' ? null : (int)$_POST['parent_id'];
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
            if ($stmt->execute([$name, $parent_id])) {
                $category_message = 'Category added successfully.';
                header('Location: admin.php');
                exit;
            } else {
                $category_message = 'Error adding category.';
            }
        } else {
            $category_message = 'Name is required.';
        }
    }

    // Add Product
    $product_message = '';
    if (isset($_POST['add_product'])) {
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $category_id = (int)$_POST['category_id'];
        $image_url = '';

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = 'images/';
            $image_name = basename($_FILES['image']['name']);
            $target_path = $upload_dir . $image_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_url = $target_path;
            } else {
                $product_message = 'Error uploading image.';
            }
        }

        if (!empty($name) && $price > 0 && $category_id > 0) {
            $stmt = $pdo->prepare("INSERT INTO products (name, price, description, image_url, category_id) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $price, $description, $image_url, $category_id])) {
                $product_message = 'Product added successfully.';
            } else {
                $product_message = 'Error adding product.';
            }
        } else {
            $product_message = 'Required fields missing.';
        }
    }
     // Update Product
    if (isset($_POST['update_product'])) {
        $product_id = (int)$_POST['product_id'];
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $category_id = (int)$_POST['category_id'];
        $image_url = $_POST['current_image'] ?? '';

        // Handle image upload if a new image is provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = 'images/';
            $image_name = basename($_FILES['image']['name']);
            $target_path = $upload_dir . $image_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_url = $target_path;
                // Optionally delete the old image file
            } else {
                $product_message = 'Error uploading image.';
            }
        }

        if (!empty($name) && $price > 0 && $category_id > 0) {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, description = ?, image_url = ?, category_id = ? WHERE id = ?");
            if ($stmt->execute([$name, $price, $description, $image_url, $category_id, $product_id])) {
                $product_message = 'Product updated successfully.';
                header('Location: admin.php');
                exit;
            } else {
                $product_message = 'Error updating product.';
            }
        } else {
            $product_message = 'Required fields missing.';
        }
    }
    
    // Delete Product
    if (isset($_GET['delete_product'])) {
        $product_id = (int)$_GET['delete_product'];
        
        // First, get the image path to potentially delete the file
        $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$product_id])) {
            // Optionally delete the image file
            if (!empty($product['image_url']) && file_exists($product['image_url'])) {
                unlink($product['image_url']);
            }
            $product_message = 'Product deleted successfully.';
            header('Location: admin.php');
            exit;
        } else {
            $product_message = 'Error deleting product.';
        }
    }
    
    // Fetch all products for management
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if we're editing a specific product
    $editing_product = null;
    if (isset($_GET['edit_product'])) {
        $product_id = (int)$_GET['edit_product'];
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $editing_product = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Show admin panel
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Panel | Le Paon Supermarket</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="admin.css">
        
         <script>
            function confirmDelete(productId, productName) {
                return confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`);
            }
        </script>
    </head>
    <body>
        <header>
            <nav>
                <div class="logo-container">
                    <span class="logo">🦚</span>
                    <span class="store-name">Le Paon Supermarket</span>
                </div>
                <ul class="nav-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.html">Contact Us</a></li>
                    <li><a href="admin.php?logout=1" class="logout-btn">Logout</a></li>
                </ul>
            </nav>
        </header>
        
        <div class="admin-panel-container">
            <div class="admin-header">
                <h1>Admin Panel</h1>
                <a href="admin.php?logout=1" class="logout-btn">Logout</a>
            </div>

            <div class="admin-section">
                <h2>Add Category</h2>
                <?php if ($category_message): ?>
                    <div class="<?php echo strpos($category_message, 'success') !== false ? 'success-message' : 'error-message'; ?>">
                        <?php echo htmlspecialchars($category_message); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="admin.php" class="admin-form">
                    <label for="category_name">Category Name:</label>
                    <input type="text" id="category_name" name="name" required>
                    
                    <label for="parent_id">Parent Category:</label>
                    <select id="parent_id" name="parent_id">
                        <option value="none">None (Main Category)</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" name="add_category" class="admin-btn">Add Category</button>
                </form>
            </div>

            <div class="admin-section">
                <h2>Add Product</h2>
                <?php if ($product_message): ?>
                    <div class="<?php echo strpos($product_message, 'success') !== false ? 'success-message' : 'error-message'; ?>">
                        <?php echo htmlspecialchars($product_message); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="admin.php" enctype="multipart/form-data" class="admin-form">
                    <label for="product_name">Product Name:</label>
                    <input type="text" id="product_name" name="name" required>
                    
                    <label for="price">Price (RWF):</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                    
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required rows="4"></textarea>
                    
                    <label for="category_id">Category:</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="image">Image:</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    
                    <?php if ($editing_product && !empty($editing_product['image_url'])): ?>
                        <div class="current-image">
                            <p>Current Image:</p>
                            <img src="<?php echo htmlspecialchars($editing_product['image_url']); ?>" alt="Current product image" style="max-width: 200px; max-height: 200px;">
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($editing_product): ?>
                        <button type="submit" name="update_product" class="admin-btn">Update Product</button>
                        <a href="admin.php" class="admin-btn cancel-btn">Cancel</a>
                    <?php else: ?>
                    <button type="submit" name="add_product" class="admin-btn">Add Product</button>
                </form>
                <div class="admin-section">
                    <h2>Manage Products</h2>
                    
                    <?php if ($product_message): ?>
                        <div class="<?php echo strpos($product_message, 'success') !== false ? 'success-message' : 'error-message'; ?>">
                            <?php echo htmlspecialchars($product_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-controls">
                        <div class="table-info">
                            Showing <?php echo count($products); ?> product(s)
                        </div>
                        <div class="table-actions">
                            <input type="text" id="productSearch" placeholder="Search products..." class="search-input">
                            <button class="admin-btn small-btn" onclick="refreshProducts()">Refresh</button>
                        </div>
                    </div>
                    
                    <div class="products-table-container">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Price (RWF)</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="center-align"><?php echo $product['id']; ?></td>
                            <td class="center-align">
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="Product image" 
                                         class="product-thumbnail">
                                <?php else: ?>
                                    <div class="no-image">No image</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td class="center-align"><?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                            <td class="description-cell">
                                <?php 
                                    $description = htmlspecialchars($product['description']);
                                    if (strlen($description) > 100) {
                                        echo substr($description, 0, 100) . '...';
                                    } else {
                                        echo $description;
                                    }
                                ?>
                            </td>
                            <td class="center-align action-buttons">
                                <a href="admin.php?edit_product=<?php echo $product['id']; ?>" 
                                   class="action-btn edit-btn" title="Edit Product">
                                   <i class="fas fa-edit"></i>
                                </a>
                                <a href="admin.php?delete_product=<?php echo $product['id']; ?>" 
                                   class="action-btn delete-btn" 
                                   onclick="return confirmDelete(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')"
                                   title="Delete Product">
                                   <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="center-align no-products">
                            No products found. <a href="#add-product">Add your first product</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
    </body>
    </html>
    <?php
    exit;
                    endif; // End of product form
                
    exit;
} else {

    // Admin is not logged in, show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login | Le Paon Supermarket</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="admin.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
       
    </head>
    <body>
        <header>
            <nav>
                <div class="logo-container">
                    <span class="logo">🦚</span>
                    <span class="store-name">Le Paon Supermarket</span>
                </div>
                <ul class="nav-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.html">Contact Us</a></li>
                    <li><a href="login.html">Login</a></li>
                </ul>
            </nav>
        </header>
        
        <div class="admin-login-container">
            <form class="admin-login-form" method="POST" action="admin.php">
                <h1>Admin Login</h1>
                
                <?php if ($login_error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($login_error); ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required placeholder="Enter admin username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required placeholder="Enter admin password">
                </div>
                
                <button type="submit" name="login" class="login-button">Login to Admin Panel</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>