<?php
$page_title = 'Crypto Catalog';
include 'header.php';
include '../config/db.php';

// Fetch user's name from the database
$user_name = 'Guest'; // Default value in case no name is found
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $user_name = $user['name'];
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $user_name = 'Guest';
}

// Search and Filter Logic
$search_query = $_GET['search'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Construct the SQL query with dynamic filters
$sql = "SELECT * FROM cryptos WHERE 1=1";
$params = [];

if (!empty($search_query)) {
    $sql .= " AND (name LIKE :search OR symbol LIKE :search)";
    $params['search'] = "%{$search_query}%";
}

if (!empty($min_price)) {
    $sql .= " AND current_marketprice >= :min_price";
    $params['min_price'] = $min_price;
}

if (!empty($max_price)) {
    $sql .= " AND current_marketprice <= :max_price";
    $params['max_price'] = $max_price;
}

// Fetch filtered cryptos
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cryptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching cryptos: " . $e->getMessage());
    $cryptos = [];
}
?>

<style>
    /* Global Styling Enhancements */
body {
    background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
    font-family: 'Inter', 'Arial', sans-serif;
}

.content-container {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 10px 20px rgba(0, 0, 0, 0.03);
    max-width: 1500px;
    margin: 2rem auto;
    padding: 2 15px;
}

/* Welcome Alert Styling */
.alert-warning {
    background-color: #fff3cd;
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.alert-warning i {
    color: #ffc107;
    margin-right: 1rem;
    font-size: 1.75rem;
}

/* Search and Filter Section */
.input-group {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
}

.input-group-text {
    background-color: #f8f9fa;
    border: none;
    color: #6c757d;
}

.form-control {
    border: none;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.form-control:focus {
    background-color: #ffffff;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.btn-custom {
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-custom:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Table Styling */
.table {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.table thead {
    background-color: #343a40;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transition: background-color 0.3s ease;
}

.table-striped tbody tr:nth-of-type(even) {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Crypto Symbol Badge */
.badge {
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
    padding: 0.4rem 0.6rem;
}

/* Trade Button */
.btn-outline-primary {
    border-width: 2px;
    border-radius: 6px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.btn-outline-primary:hover {
    background-color: #007bff;
    color: white;
    transform: scale(1.05);
}

/* Empty State Styling */
.alert-info {
    background-color: #e7f1ff;
    border: none;
    border-radius: 8px;
    color: #0056b3;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-container {
        padding: 1rem;
    }

    .table-responsive {
        font-size: 0.9rem;
    }
}

/* Subtle Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.table tbody tr {
    animation: fadeIn 0.5s ease;
}
</style>

<div class="content-container">


    <!-- Welcome Message -->
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-person-circle me-3" style="font-size: 1.5rem;"></i>
        <div>
            Welcome, <strong><?php echo htmlspecialchars($user_name); ?></strong>! Explore our Crypto Catalog.
        </div>
    </div>
    
    <!-- Search and Filter Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <form method="GET" action="" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search cryptos..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="number" name="min_price" class="form-control" placeholder="Min Price" 
                           value="<?php echo htmlspecialchars($min_price); ?>" min="0" step="0.01">
                </div>
                <div class="col-md-2">
                    <input type="number" name="max_price" class="form-control" placeholder="Max Price" 
                           value="<?php echo htmlspecialchars($max_price); ?>" min="0" step="0.01">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-custom w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <h2 class="mb-4">Crypto Catalog 
        <?php if (!empty($search_query) || !empty($min_price) || !empty($max_price)): ?>
            <small class="text-muted">
                (<?php echo count($cryptos); ?> results found)
            </small>
        <?php endif; ?>
    </h2>

    <div class="table-responsive">
        <table class="table table-hover table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th class="text-center">Symbol</th>
                    <th>Name</th>
                    <th class="text-end">Market Price</th>
                    <th class="text-end">Available Supply</th>
                    <th class="text-end">Highest Market Price</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cryptos)): ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-search me-2"></i>No cryptos found matching your search criteria.
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cryptos as $crypto): ?>
                        <tr>
                            <td class="text-center fw-bold">
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($crypto['symbol']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($crypto['name']); ?></td>
                            <td class="text-end text-success fw-bold">
                                $<?php echo number_format($crypto['current_marketprice'], 2); ?>
                            </td>
                            <td class="text-end">
                                <?php echo $crypto['available_supply'] ? number_format($crypto['available_supply'], 0) : 'N/A'; ?>
                            </td>
                            <td class="text-end">
                                <?php echo $crypto['highest_marketprice'] ? '$' . number_format($crypto['highest_marketprice'], 2) : 'N/A'; ?>
                            </td>
                            <td class="text-center">
                                <a href="trade_crypto.php?id=<?php echo urlencode($crypto['id']); ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-graph-up"></i> Trade
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>