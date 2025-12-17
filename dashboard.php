<?php
// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Regenerate session ID for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

require_once 'config/database.php';

// Get user data from session
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userEmail = $_SESSION['user_email'];

try {
    $pdo = getPDOConnection();
    
    // Get statistics
    // Total items
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM food_items WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Fresh items
    $stmt = $pdo->prepare("SELECT COUNT(*) as fresh FROM food_items WHERE user_id = ? AND status = 'fresh'");
    $stmt->execute([$userId]);
    $freshCount = $stmt->fetch(PDO::FETCH_ASSOC)['fresh'];
    
    // Expiring soon items
    $stmt = $pdo->prepare("SELECT COUNT(*) as expiring FROM food_items WHERE user_id = ? AND status = 'expiring_soon'");
    $stmt->execute([$userId]);
    $expiringCount = $stmt->fetch(PDO::FETCH_ASSOC)['expiring'];
    
    // Expired items
    $stmt = $pdo->prepare("SELECT COUNT(*) as expired FROM food_items WHERE user_id = ? AND status = 'expired'");
    $stmt->execute([$userId]);
    $expiredCount = $stmt->fetch(PDO::FETCH_ASSOC)['expired'];
    
    // Get all food items for this user
    $stmt = $pdo->prepare("
        SELECT 
            id,
            food_name,
            category,
            expiry_date,
            quantity,
            unit,
            storage_location,
            notes,
            status,
            created_at
        FROM food_items 
        WHERE user_id = ? 
        ORDER BY expiry_date ASC
    ");
    $stmt->execute([$userId]);
    $foodItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to JSON for JavaScript
    $foodItemsJson = json_encode($foodItems);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $totalCount = $freshCount = $expiringCount = $expiredCount = 0;
    $foodItemsJson = '[]';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FET | Food Expiry Tracker</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand">
                <h3>FET</h3>
            </div>
            <button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <h5 id="userName"><?= htmlspecialchars($userName) ?></h5>
                <p id="userEmail"><?= htmlspecialchars($userEmail) ?></p>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">  
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>

            <a href="recipe-finder.php" class="nav-item">  
                <i class="fas fa-utensils"></i>
                <span>Recipe Finder</span>
            </a>

            <a href="settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Top Navigation -->
        <nav class="top-navbar">
            <div class="navbar-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="page-title">Dashboard</h4>
            </div>
            <div class="navbar-right">
                <button class="btn btn-primary btn-add-food" onclick="openAddFoodModal()">
                    <i class="fas fa-plus"></i> Add Food
                </button>
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="notificationBadge"><?= $expiringCount + $expiredCount ?></span>
                </div>
                <div class="user-menu">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=10b981&color=fff" alt="User">
                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <div class="content-wrapper">
            
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card card-green">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="freshCount"><?= $freshCount ?></h3>
                            <p>Fresh Items</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card card-yellow">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="expiringCount"><?= $expiringCount ?></h3>
                            <p>Expiring Soon</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card card-red">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="expiredCount"><?= $expiredCount ?></h3>
                            <p>Expired</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card card-blue">
                        <div class="stat-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="totalCount"><?= $totalCount ?></h3>
                            <p>Total Items</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipe Suggestion Alert (Show when items expiring) -->
            <div class="alert alert-warning alert-recipe <?= $expiringCount > 0 ? '' : 'd-none' ?>" id="recipeSuggestionAlert">
                <div class="alert-content">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <h5>Smart Recipe Suggestion Available!</h5>
                        <p>You have <strong id="expiringItemsCount"><?= $expiringCount ?></strong> items expiring soon. Find recipes to use them up!</p>
                    </div>
                </div>
                <button class="btn btn-warning" onclick="openRecipeModal()">
                    <i class="fas fa-utensils"></i> Find Recipes
                </button>
            </div>

            <!-- Filter & Search Bar -->
            <div class="filter-section">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search food items..." onkeyup="filterFoods()">
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12">
                        <div class="filter-buttons">
                            <button class="filter-btn active" onclick="filterByStatus('all')">
                                <i class="fas fa-th"></i> All
                            </button>
                            <button class="filter-btn filter-green" onclick="filterByStatus('fresh')">
                                <i class="fas fa-check-circle"></i> Fresh
                            </button>
                            <button class="filter-btn filter-yellow" onclick="filterByStatus('expiring_soon')">
                                <i class="fas fa-exclamation-triangle"></i> Expiring
                            </button>
                            <button class="filter-btn filter-red" onclick="filterByStatus('expired')">
                                <i class="fas fa-times-circle"></i> Expired
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Food Items Grid -->
            <div class="food-grid" id="foodGrid">
                <!-- Food cards will be generated dynamically by JavaScript -->
            </div>

            <!-- Empty State -->
            <div class="empty-state <?= $totalCount > 0 ? 'd-none' : '' ?>" id="emptyState">
                <i class="fas fa-inbox"></i>
                <h3>No Food Items Yet</h3>
                <p>Start tracking your food by adding your first item!</p>
                <button class="btn btn-primary" onclick="openAddFoodModal()">
                    <i class="fas fa-plus"></i> Add Your First Food
                </button>
            </div>

        </div>
    </div>

    <!-- Add Food Modal -->
    <div class="modal fade" id="addFoodModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle"></i> Add New Food Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addFoodForm" onsubmit="handleAddFood(event)">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-tag"></i> Food Name
                            </label>
                            <input type="text" class="form-control" id="foodName" required placeholder="e.g., Chicken Breast">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-layer-group"></i> Category
                            </label>
                            <select class="form-select" id="foodCategory" required>
                                <option value="">Select category...</option>
                                <option value="Vegetables">🥕 Vegetables</option>
                                <option value="Fruits">🍎 Fruits</option>
                                <option value="Dairy">🧀 Dairy</option>
                                <option value="Meat">🍗 Meat</option>
                                <option value="Seafood">🐟 Seafood</option>
                                <option value="Grains">🌾 Grains</option>
                                <option value="Beverages">🥤 Beverages</option>
                                <option value="Others">📦 Others</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt"></i> Expiry Date
                            </label>
                            <input type="date" class="form-control" id="expiryDate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-image"></i> Food Image (Optional)
                            </label>
                            <input type="file" class="form-control" id="foodImage" accept="image/*">
                            <small class="text-muted">Max 2MB</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Food
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Recipe Finder Modal -->
    <div class="modal fade" id="recipeModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-utensils"></i> Recipe Suggestions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Selected Ingredients -->
                    <div class="selected-ingredients mb-4">
                        <h6>Items Expiring Soon:</h6>
                        <div class="ingredient-chips" id="ingredientChips">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div class="recipe-loading d-none" id="recipeLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Finding delicious recipes for you...</p>
                    </div>

                    <!-- Recipe Results -->
                    <div class="recipe-results" id="recipeResults">
                        <!-- Will be populated with recipes -->
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Edit Food Modal -->
    <div class="modal fade" id="editFoodModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Food Item
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editFoodForm" onsubmit="handleEditFood(event)">
                        <input type="hidden" id="editFoodId">
                        <div class="mb-3">
                            <label class="form-label">Food Name</label>
                            <input type="text" class="form-control" id="editFoodName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="editFoodCategory" required>
                                <option value="Vegetables">🥕 Vegetables</option>
                                <option value="Fruits">🍎 Fruits</option>
                                <option value="Dairy">🧀 Dairy</option>
                                <option value="Meat">🍗 Meat</option>
                                <option value="Seafood">🐟 Seafood</option>
                                <option value="Grains">🌾 Grains</option>
                                <option value="Beverages">🥤 Beverages</option>
                                <option value="Others">📦 Others</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="editExpiryDate" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Food
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteFoodName"></strong>?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pass PHP data to JavaScript -->
    <script>
        // User data
        const USER_ID = <?= $userId ?>;
        const USER_NAME = '<?= addslashes($userName) ?>';
        const USER_EMAIL = '<?= addslashes($userEmail) ?>';
        
        // Food items data from database
        const FOOD_ITEMS_FROM_DB = <?= $foodItemsJson ?>;
        
        // Load food items when page loads
        window.addEventListener('DOMContentLoaded', function() {
            if (FOOD_ITEMS_FROM_DB && FOOD_ITEMS_FROM_DB.length > 0) {
                // If you have a function to load food items in your JS
                if (typeof loadFoodItemsFromServer === 'function') {
                    loadFoodItemsFromServer(FOOD_ITEMS_FROM_DB);
                }
            }
        });
    </script>

    <!-- EmailJS SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    <script>
        (function(){
            emailjs.init("6gqqFv2Zda4jl_ta");
        })();
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS - Load in order -->
    <script src="js/utils.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/recipe-finder.js"></script>
    <script src="js/notifications.js"></script>
    <script src="js/email-notifications.js"></script>
    <script src="js/browser-notifications.js"></script>
</body>
</html>