// Wait for the page to load completely
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
            // Close mobile menu if open
            const navLinks = document.querySelector('.nav-links');
            if (navLinks && navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
            }
        });
    });

    // Mobile menu toggle functionality
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        // Close menu when a link is clicked
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!navLinks.contains(e.target) && !menuToggle.contains(e.target)) {
                navLinks.classList.remove('active');
            }
        });
    }

    // Handle contact form submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const message = document.getElementById('message').value;
            const successMessage = document.getElementById('contactSuccessMessage');

            if (name && email && message) {
                if (successMessage) {
                    successMessage.style.display = 'block';
                    successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 5000);
                }
                this.reset();
            } else {
                alert('Please fill in all required fields.');
            }
        });
    }

    // Handle star rating for review form
    const starRating = document.getElementById('starRating');
    const ratingInput = document.getElementById('reviewRating');
    if (starRating && ratingInput) {
        const stars = starRating.querySelectorAll('.star');

        stars.forEach((star, index) => {
            star.addEventListener('mouseenter', () => highlightStars(index + 1));
            star.addEventListener('click', () => {
                const rating = index + 1;
                ratingInput.value = rating;
                highlightStars(rating);
                stars.forEach(s => s.classList.remove('hover', 'active'));
                for (let i = 0; i < rating; i++) {
                    stars[i].classList.add('active');
                }
            });
        });

        starRating.addEventListener('mouseleave', () => {
            const currentRating = parseInt(ratingInput.value) || 0;
            highlightStars(currentRating);
        });

        function highlightStars(rating) {
            stars.forEach((star, index) => {
                star.style.color = index < rating ? '#ff6b35' : '#ddd';
            });
        }
    }

    // Handle review form submission
    const reviewForm = document.getElementById('reviewForm');
    const reviewSuccessMessage = document.getElementById('reviewSuccessMessage');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('reviewName').value;
            const rating = document.getElementById('reviewRating').value;
            const reviewText = document.getElementById('reviewText').value;

            if (!name.trim()) {
                alert('Please enter your name.');
                return;
            }
            if (!rating || rating === '0') {
                alert('Please select a rating.');
                return;
            }
            if (!reviewText.trim()) {
                alert('Please write your review.');
                return;
            }

            if (reviewSuccessMessage) {
                reviewSuccessMessage.classList.add('show');
                reviewSuccessMessage.style.display = 'block';
                setTimeout(() => {
                    reviewSuccessMessage.classList.remove('show');
                    reviewSuccessMessage.style.display = 'none';
                }, 5000);
            }

            addNewReviewToPage(name, rating, reviewText);
            this.reset();
            if (ratingInput) ratingInput.value = '0';
            if (starRating) {
                const stars = starRating.querySelectorAll('.star');
                stars.forEach(star => {
                    star.style.color = '#ddd';
                    star.classList.remove('active');
                });
            }
            reviewSuccessMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    // Function to add new review to the page
    function addNewReviewToPage(name, rating, reviewText) {
        const reviewsGrid = document.querySelector('.reviews-grid');
        if (reviewsGrid) {
            const starsDisplay = '★'.repeat(parseInt(rating)) + '☆'.repeat(5 - parseInt(rating));
            const initials = name.split(' ').map(word => word.charAt(0)).join('').toUpperCase();
            const newReviewCard = document.createElement('div');
            newReviewCard.className = 'review-card new-review';
            newReviewCard.innerHTML = `
                <div class="review-header">
                    <div class="customer-photo">${initials}</div>
                    <div class="customer-info">
                        <h4>${name}</h4>
                        <div class="rating">${starsDisplay}</div>
                    </div>
                </div>
                <p class="review-text">"${reviewText}"</p>
            `;
            reviewsGrid.insertBefore(newReviewCard, reviewsGrid.firstChild);
            setTimeout(() => newReviewCard.classList.remove('new-review'), 800);
        }
    }

    // Add scroll effect to header
    window.addEventListener('scroll', function() {
        const header = document.querySelector('header');
        if (window.scrollY > 100) {
            header.style.backgroundColor = 'rgba(44, 85, 48, 0.95)';
        } else {
            header.style.backgroundColor = '#2c5530';
        }
    });

    // Simple animation for product cards when they come into view
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    document.querySelectorAll('.product-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });

    // FAQ toggle functionality
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', () => {
            const faqItem = question.parentElement;
            faqItem.classList.toggle('active');
            const toggle = question.querySelector('.faq-toggle');
            if (faqItem.classList.contains('active')) {
                toggle.textContent = '-';
            } else {
                toggle.textContent = '+';
            }
        });
    });

    // Fade in elements observer
    const fadeElements = document.querySelectorAll('.fade-in');
    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });
    fadeElements.forEach(el => fadeObserver.observe(el));
});