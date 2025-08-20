<?php
require_once 'config.php'; // Include database connection

// Fetch main categories for dropdown and menu
$stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL");
$main_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories and subcategories for menu
$stmt = $pdo->query("SELECT c1.id, c1.name, c1.parent_id, c2.name AS parent_name 
                     FROM categories c1 
                     LEFT JOIN categories c2 ON c1.parent_id = c2.id 
                     ORDER BY c1.parent_id IS NULL DESC, c1.name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch products
$stmt = $pdo->query("SELECT p.*, c.name AS category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Le Paon Supermarket</title>
     <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">   
    <link rel="stylesheet" href="productstyle.css">
   
</head>
<body>
    <!-- Header with Navigation -->
    <header>
        <nav>
            <div class="logo-container">
                <span class="logo">🦚</span>
                <span class="store-name">Le Paon Supermarket</span>
            </div>
            <button class="menu-toggle" id="menuToggle">☰</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.html">Home</a></li>
                <li><a href="about.html">About Us</a></li>
                <li><a href="products.php" class="active">Products</a></li>
                <li><a href="contact.html">Contact Us</a></li>
                <li><a href="login.html">Login</a></li>
            </ul>
        </nav>
    </header>

    <!-- Search and Cart Section -->
    <section class="search-cart-section">
        <div class="search-cart-container">
             <form class="search-container">
                <select class="category-dropdown">
                    <option value="">All Categories</option>
                    <?php foreach ($main_categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" class="search-input" placeholder="Search...">
                <button type="submit" class="search-btn">🔍</button>
            </form>
            <a href="cart.html" class="cart-link" id="cartLink">
                <span class="cart-icon">🛒</span>
                <span>View Cart</span>
                <span class="cart-count" id="cartCount">0</span>
            </a>
        </div>
    </section>

    <section class="categories-menu">
        <div class="categories-container">
            <div class="categories-nav">
                <?php
                $current_parent = null;
                foreach ($categories as $category):
                    if ($category['parent_id'] === null):
                        if ($current_parent !== null):
                            // Close previous dropdown
                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="category-item">
                        <a href="#" class="category-btn">
                            <?php echo htmlspecialchars($category['name']); ?>
                            <span class="dropdown-arrow">▼</span>
                        </a>
                        <div class="dropdown-menu">
                <?php
                        $current_parent = $category['id'];
                    else:
                ?>
                            <a href="#" class="dropdown-item">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($current_parent !== null): ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
   
    <main>
        <!-- Products Hero Section -->
        <section class="products-hero">
            <div class="container">
                <h1>Our Products</h1>
                <p>Discover our wide selection of homemade delicacies, daily essentials, and quality groceries crafted with care</p>
            </div>
        </section>

        <!-- Products Content -->
        <section class="products-content">
            <div class="container">
                <div class="products-grid" id="productsGrid">
                    <!-- Sample Products -->
             
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'images/placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-price"><?php echo number_format($product['price'], 2); ?> RWF</p>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <button class="add-to-cart-btn">Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 Le Paon Supermarket. All rights reserved. | Experience the difference with us.</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const navLinks = document.getElementById('navLinks');

        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        // Category dropdown functionality
        const categoryItems = document.querySelectorAll('.category-item');

        categoryItems.forEach(item => {
            const button = item.querySelector('.category-btn');
            
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                
                // Close other dropdowns
                categoryItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                    }
                });
                
                // Toggle current dropdown
                item.classList.toggle('active');
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            categoryItems.forEach(item => {
                item.classList.remove('active');
            });
        });

        // Prevent dropdown from closing when clicking inside
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });

        // Search functionality
        const searchBtn = document.getElementById('searchBtn');
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');

        searchBtn.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        function performSearch() {
            const query = searchInput.value.trim();
            const category = categoryFilter.value;
            
            if (query || category !== 'all') {
                console.log('Searching for:', query, 'in category:', category);
                // Implement search logic here
                // This could filter the products grid or redirect to a search results page
            }
        }

        // Cart functionality
        let cartCount = 0;
        const cartCountElement = document.getElementById('cartCount');
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');

        addToCartButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                cartCount++;
                cartCountElement.textContent = cartCount;

      // Save to localStorage
        localStorage.setItem('lepacon-cart-count', cartCount);
                
                // Visual feedback
                button.style.background = '#90c695';
                button.textContent = 'Added!';
                
                setTimeout(() => {
                    button.style.background = '#2c5530';
                    button.textContent = 'Add to Cart';
                }, 1500);
            });
        });

        // Category filtering
        categoryItems.forEach(item => {
            const button = item.querySelector('.category-btn');
            const dropdownItems = item.querySelectorAll('.dropdown-item');
            
            dropdownItems.forEach(dropdownItem => {
                dropdownItem.addEventListener('click', (e) => {
                    e.preventDefault();
                    const category = button.dataset.category;
                    const subcategory = dropdownItem.textContent;
                    
                    console.log('Filtering by:', category, '-', subcategory);
                    // Implement filtering logic here
                    
                    // Close dropdown
                    item.classList.remove('active');
                });
            });
        });

        // Smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';

        // Initialize cart count from storage (if available)
        const savedCartCount = parseInt(localStorage.getItem('lepacon-cart-count') || '0');
        cartCount = savedCartCount;
        cartCountElement.textContent = cartCount;
    </script>
</body>
</html>