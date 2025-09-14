<?php
require_once 'config.php'; // Include database connection

// Fetch main categories for dropdown and menu
$stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$main_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories and subcategories for menu (organized by parent)
$stmt = $pdo->query("SELECT c1.id, c1.name, c1.parent_id, c2.name AS parent_name 
                     FROM categories c1 
                     LEFT JOIN categories c2 ON c1.parent_id = c2.id 
                     ORDER BY c1.parent_id IS NULL DESC, c2.name, c1.name");
$all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize categories by parent
$categories_by_parent = [];
foreach ($all_categories as $category) {
    if ($category['parent_id'] === null) {
        // Main category
        $categories_by_parent[$category['id']] = [
            'main' => $category,
            'subcategories' => []
        ];
    } else {
        // Subcategory
        if (isset($categories_by_parent[$category['parent_id']])) {
            $categories_by_parent[$category['parent_id']]['subcategories'][] = $category;
        }
    }
}

// Fetch products with category information
$stmt = $pdo->query("SELECT p.*, c.name AS category_name, c.id AS category_id, 
                            parent_cat.name AS parent_category_name, parent_cat.id AS parent_category_id
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id
                     LEFT JOIN categories parent_cat ON c.parent_id = parent_cat.id
                     ORDER BY parent_cat.name, c.name, p.name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group products by category for default display
$products_by_category = [];
foreach ($products as $product) {
    $main_cat = $product['parent_category_name'] ?: $product['category_name'];
    if (!isset($products_by_category[$main_cat])) {
        $products_by_category[$main_cat] = [];
    }
    $products_by_category[$main_cat][] = $product;
}

// Debug information (remove in production)
// echo "<!-- Debug: Categories structure: " . print_r($categories_by_parent, true) . " -->";
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
    <link rel="stylesheet" href="productmodelstyle.css">
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
             <form class="search-container" id="searchForm">
                <select class="category-dropdown" id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($main_categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['name']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php foreach ($all_categories as $category): ?>
                        <?php if ($category['parent_id'] !== null): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                -- <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <input type="text" class="search-input" id="searchInput" placeholder="Search products...">
                <button type="submit" class="search-btn">🔍</button>
            </form>
            <a href="cart.html" class="cart-link" id="cartLink">
                <span class="cart-icon">🛒</span>
                <span>View Cart</span>
                <span class="cart-count" id="cartCount">0</span>
            </a>
        </div>
    </section>

    <!-- COMPACT Categories Menu -->
    <section class="categories-menu">
        <div class="categories-container">
            <div class="categories-nav">
                <!-- All Categories Button -->
                <div class="category-item">
                    <a href="#" class="category-btn" data-category="all">
                        All Products
                    </a>
                </div>

                <?php foreach ($categories_by_parent as $parent_id => $category_data): ?>
                <div class="category-item">
                    <a href="#" class="category-btn" data-category="<?php echo htmlspecialchars($category_data['main']['name']); ?>">
                        <?php echo htmlspecialchars($category_data['main']['name']); ?>
                        <?php if (!empty($category_data['subcategories'])): ?>
                            <span class="dropdown-arrow">▼</span>
                        <?php endif; ?>
                    </a>
                    <?php if (!empty($category_data['subcategories'])): ?>
                        <div class="dropdown-menu">
                            <a href="#" class="dropdown-item" data-category="<?php echo htmlspecialchars($category_data['main']['name']); ?>">
                                All <?php echo htmlspecialchars($category_data['main']['name']); ?>
                            </a>
                            <?php foreach ($category_data['subcategories'] as $subcategory): ?>
                                <a href="#" class="dropdown-item" data-category="<?php echo htmlspecialchars($subcategory['name']); ?>">
                                    <?php echo htmlspecialchars($subcategory['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
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
                <div class="filter-message" id="filterMessage" style="display: none;">
                    Showing products in: <span id="currentCategory">All Categories</span>
                    <button id="clearFilter" style="margin-left: 10px; background: #ff6b35; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                        Clear Filter
                    </button>
                </div>
                
                <div class="search-results-message" id="searchMessage" style="display: none;">
                    Search results for: "<span id="searchTerm"></span>"
                    <button id="clearSearch" style="margin-left: 10px; background: #ff6b35; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                        Clear Search
                    </button>
                </div>

                <div class="no-products" id="noProducts" style="display: none;">
                    No products found. Please try a different search or category.
                </div>

                <!-- Default Category-grouped Display -->
                <div class="category-grouped-products" id="categoryGroupedView">
                    <?php foreach ($products_by_category as $category_name => $category_products): ?>
                        <div class="category-section">
                            <h2 class="category-title"><?php echo htmlspecialchars($category_name ?: 'Uncategorized'); ?></h2>
                            <div class="products-grid">
                                <?php foreach ($category_products as $product): ?>
                                    <div class="product-card" 
                                         data-category="<?php echo htmlspecialchars($product['category_name'] ?: ''); ?>"
                                         data-parent-category="<?php echo htmlspecialchars($product['parent_category_name'] ?: ''); ?>"
                                         data-category-id="<?php echo $product['category_id']; ?>"
                                         data-parent-category-id="<?php echo $product['parent_category_id'] ?: ''; ?>"
                                         data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>"
                                         data-description="<?php echo htmlspecialchars(strtolower($product['description'] ?: '')); ?>">
                                        <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'images/placeholder.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="product-image">
                                        <div class="product-info">
                                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                            <p class="product-price"><?php echo number_format($product['price'], 0); ?> RWF</p>
                                            <p class="product-description"><?php echo htmlspecialchars($product['description'] ?: ''); ?></p>
                                            <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">Add to Cart</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filtered Products Grid -->
                <div class="products-grid" id="filteredProductsGrid" style="display: none;">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" 
                             data-category="<?php echo htmlspecialchars($product['category_name'] ?: ''); ?>"
                             data-parent-category="<?php echo htmlspecialchars($product['parent_category_name'] ?: ''); ?>"
                             data-category-id="<?php echo $product['category_id']; ?>"
                             data-parent-category-id="<?php echo $product['parent_category_id'] ?: ''; ?>"
                             data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>"
                             data-description="<?php echo htmlspecialchars(strtolower($product['description'] ?: '')); ?>">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'images/placeholder.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image">
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-price"><?php echo number_format($product['price'], 0); ?> RWF</p>
                                <p class="product-description"><?php echo htmlspecialchars($product['description'] ?: ''); ?></p>
                                <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">Add to Cart</button>
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

        // Global variables
        let currentView = 'grouped'; // 'grouped' or 'filtered'
        let currentFilter = '';
        let currentSearchTerm = '';

        // Enhanced category dropdown functionality for compact menu
        document.addEventListener('DOMContentLoaded', function() {
            const categoryItems = document.querySelectorAll('.category-item');
            
            // Close all dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.category-item')) {
                    categoryItems.forEach(item => {
                        item.classList.remove('active');
                    });
                }
            });
            
            // Prevent dropdown from closing when clicking inside
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
            
            // Handle category clicks
            categoryItems.forEach(item => {
                const button = item.querySelector('.category-btn');
                
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const category = button.dataset.category;
                    const hasSubcategories = item.querySelector('.dropdown-menu');
                    
                    // Toggle current dropdown
                    if (hasSubcategories) {
                        // Close other dropdowns
                        categoryItems.forEach(otherItem => {
                            if (otherItem !== item) {
                                otherItem.classList.remove('active');
                            }
                        });
                        
                        // Toggle current dropdown
                        item.classList.toggle('active');
                    } else {
                        // Direct category selection
                        if (category === 'all') {
                            showAllProducts();
                        } else {
                            filterProductsByCategory(category);
                        }
                        
                        // Close all dropdowns
                        categoryItems.forEach(otherItem => {
                            otherItem.classList.remove('active');
                        });
                    }
                });
            });
            
            // Handle dropdown item clicks
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const category = item.dataset.category;
                    filterProductsByCategory(category);
                    
                    // Close all dropdowns
                    categoryItems.forEach(categoryItem => {
                        categoryItem.classList.remove('active');
                    });
                });
            });
        });

        // Search functionality
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');

        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            performSearch();
        });

        searchInput.addEventListener('input', () => {
            if (searchInput.value.trim() === '') {
                clearSearch();
            }
        });

        categoryFilter.addEventListener('change', () => {
            const selectedCategory = categoryFilter.value;
            if (selectedCategory === '') {
                showAllProducts();
            } else {
                filterProductsByCategory(selectedCategory);
            }
        });

        // Enhanced search function with improved category matching
        function performSearch() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const selectedCategory = categoryFilter.value;
            
            if (searchTerm === '' && selectedCategory === '') {
                showAllProducts();
                return;
            }
            
            currentView = 'filtered';
            currentSearchTerm = searchTerm;
            currentFilter = selectedCategory;
            
            const categoryGroupedView = document.getElementById('categoryGroupedView');
            const filteredGrid = document.getElementById('filteredProductsGrid');
            const filterMessage = document.getElementById('filterMessage');
            const searchMessage = document.getElementById('searchMessage');
            const noProducts = document.getElementById('noProducts');
            
            // Hide grouped view, show filtered grid
            categoryGroupedView.style.display = 'none';
            filteredGrid.style.display = 'grid';
            
            const productCards = filteredGrid.querySelectorAll('.product-card');
            let visibleCount = 0;
            
            productCards.forEach(card => {
                const productName = card.dataset.name || '';
                const productDescription = card.dataset.description || '';
                const productCategory = card.dataset.category || '';
                const productParentCategory = card.dataset.parentCategory || '';
                
                let matchesSearch = true;
                let matchesCategory = true;
                
                // Check search term
                if (searchTerm !== '') {
                    matchesSearch = productName.includes(searchTerm) || productDescription.includes(searchTerm);
                }
                
                // Enhanced category matching logic
                if (selectedCategory !== '') {
                    matchesCategory = productCategory === selectedCategory || 
                                    productParentCategory === selectedCategory ||
                                    // Handle cases where products are in subcategories but we're filtering by main category
                                    (productParentCategory && productParentCategory === selectedCategory) ||
                                    // Handle direct subcategory matches
                                    (productCategory && productCategory === selectedCategory);
                }
                
                if (matchesSearch && matchesCategory) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update UI messages
            if (searchTerm !== '') {
                document.getElementById('searchTerm').textContent = searchInput.value;
                searchMessage.style.display = 'block';
            } else {
                searchMessage.style.display = 'none';
            }
            
            if (selectedCategory !== '') {
                document.getElementById('currentCategory').textContent = selectedCategory;
                filterMessage.style.display = 'block';
            } else {
                filterMessage.style.display = 'none';
            }
            
            noProducts.style.display = visibleCount === 0 ? 'block' : 'none';
            
            // Scroll to products section
            document.querySelector('.products-content').scrollIntoView({ behavior: 'smooth' });
        }

        // Enhanced category filtering with improved logic
        function filterProductsByCategory(categoryName) {
            currentView = 'filtered';
            currentFilter = categoryName;
            currentSearchTerm = '';
            
            // Clear search input
            searchInput.value = '';
            categoryFilter.value = categoryName;
            
            const categoryGroupedView = document.getElementById('categoryGroupedView');
            const filteredGrid = document.getElementById('filteredProductsGrid');
            const filterMessage = document.getElementById('filterMessage');
            const searchMessage = document.getElementById('searchMessage');
            const noProducts = document.getElementById('noProducts');
            
            // Hide grouped view, show filtered grid
            categoryGroupedView.style.display = 'none';
            filteredGrid.style.display = 'grid';
            searchMessage.style.display = 'none';
            
            const productCards = filteredGrid.querySelectorAll('.product-card');
            let visibleCount = 0;
            
            productCards.forEach(card => {
                const productCategory = card.dataset.category || '';
                const productParentCategory = card.dataset.parentCategory || '';
                let shouldShow = false;
                
                // Enhanced matching logic to handle parent-child relationships
                if (productCategory === categoryName) {
                    // Direct category match (subcategory)
                    shouldShow = true;
                } else if (productParentCategory === categoryName) {
                    // Parent category match (showing all products from subcategories)
                    shouldShow = true;
                } else if (productCategory && productParentCategory === '') {
                    // Products directly assigned to main categories
                    if (productCategory === categoryName) {
                        shouldShow = true;
                    }
                }
                
                if (shouldShow) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update filter message
            document.getElementById('currentCategory').textContent = categoryName;
            filterMessage.style.display = 'block';
            
            noProducts.style.display = visibleCount === 0 ? 'block' : 'none';
            
            // Scroll to products section
            document.querySelector('.products-content').scrollIntoView({ behavior: 'smooth' });
        }

        function showAllProducts() {
            currentView = 'grouped';
            currentFilter = '';
            currentSearchTerm = '';
            
            // Clear inputs
            searchInput.value = '';
            categoryFilter.value = '';
            
            const categoryGroupedView = document.getElementById('categoryGroupedView');
            const filteredGrid = document.getElementById('filteredProductsGrid');
            const filterMessage = document.getElementById('filterMessage');
            const searchMessage = document.getElementById('searchMessage');
            const noProducts = document.getElementById('noProducts');
            
            // Show grouped view, hide filtered grid
            categoryGroupedView.style.display = 'block';
            filteredGrid.style.display = 'none';
            filterMessage.style.display = 'none';
            searchMessage.style.display = 'none';
            noProducts.style.display = 'none';
        }

        function clearSearch() {
            showAllProducts();
        }

        function clearFilter() {
            showAllProducts();
        }

        // Clear button functionality
        document.getElementById('clearFilter').addEventListener('click', clearFilter);
        document.getElementById('clearSearch').addEventListener('click', clearSearch);

        // Cart functionality
        let cartCount = 0;
        const cartCountElement = document.getElementById('cartCount');
        
        // Event delegation for add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart-btn')) {
                e.preventDefault();
                
                // Show alert message
                alert('Thank you for your interest! To complete your order, please call us or text on WhatsApp at +250790250596.');
                
                cartCount++;
                cartCountElement.textContent = cartCount;

                // Visual feedback
                e.target.style.background = '#90c695';
                e.target.textContent = 'Added!';
                
                setTimeout(() => {
                    e.target.style.background = '#2c5530';
                    e.target.textContent = 'Add to Cart';
                }, 1500);
            }
        });

        // Initialize cart count on page load
        document.addEventListener('DOMContentLoaded', () => {
            cartCountElement.textContent = cartCount;
        });
    </script>
</body>
</html>