<?php
// Start session for potential CSRF or user tracking (optional)
session_start();

// Initialize variables
$errors = [];
$success = false;

// Function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review = isset($_POST['review']) ? sanitize_input($_POST['review']) : '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required.';
    } elseif (strlen($name) > 50) {
        $errors[] = 'Name must be 50 characters or less.';
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating must be between 1 and 5 stars.';
    }

    if (empty($review)) {
        $errors[] = 'Review text is required.';
    } elseif (strlen($review) > 500) {
        $errors[] = 'Review must be 500 characters or less.';
    }

    // If no errors, process the review
    if (empty($errors)) {
        // Prepare review data
        $initials = strtoupper(substr($name, 0, 1));
        if (strpos($name, ' ') !== false) {
            $name_parts = explode(' ', $name);
            $initials .= strtoupper(substr(end($name_parts), 0, 1));
        }
        $review_data = [
            'initials' => $initials,
            'name' => $name,
            'rating' => $rating,
            'review' => $review,
            'date' => date('Y-m-d H:i:s')
        ];

        // Store review in a text file (reviews.txt)
        $review_line = json_encode($review_data) . PHP_EOL;
        $file = 'reviews.txt';
        if (file_put_contents($file, $review_line, FILE_APPEND | LOCK_EX) !== false) {
            $success = true;
        } else {
            $errors[] = 'Failed to save review. Please try again later.';
        }

        // Optional: Database storage (commented out)
        /*
        $db = new PDO('mysql:host=localhost;dbname=le_paon', 'username', 'password');
        $stmt = $db->prepare('INSERT INTO reviews (initials, name, rating, review, date) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$initials, $name, $rating, $review, $review_data['date']]);
        */
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Review - Le Paon Supermarket</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">🦚 Le Paon Supermarket</div>
            <ul class="nav-links">
                <li><a href="index.html">Home</a></li>
                <li><a href="index.html#products">Products</a></li>
                <li><a href="index.html#about">About</a></li>
                <li><a href="index.html#contact">Contact Us</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="container">
            <section class="reviews">
                <h2 class="section-title">Submit Your Review</h2>
                <?php if ($success): ?>
                    <p class="success-message show">Thank you for your review! It has been submitted successfully.</p>
                    <p><a href="index.html#reviews" class="cta-button">Back to Reviews</a></p>
                <?php elseif (!empty($errors)): ?>
                    <div class="error-messages">
                        <?php foreach ($errors as $error): ?>
                            <p class="error"><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                    <p><a href="index.html#reviews" class="cta-button">Try Again</a></p>
                <?php else: ?>
                    <p>An unexpected error occurred. Please try again.</p>
                    <p><a href="index.html#reviews" class="cta-button">Back to Reviews</a></p>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <footer>
        <p>© 2025 Le Paon Supermarket. All rights reserved. | Experience the difference with us.</p>
    </footer>
</body>
</html>