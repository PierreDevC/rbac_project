<?php 
include 'header.php';
authenticate();

// Check if user has permission to view dashboard
if (!has_permission('view_dashboard')) {
    header('Location: home.php');
    exit();
}

// Get user information
$stmt = $conn->prepare("
    SELECT u.*, r.role_name 
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = :user_id
");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user permissions
$stmt = $conn->prepare("
    SELECT p.permission_name 
    FROM permissions p
    JOIN role_permissions rp ON p.id = rp.permission_id
    WHERE rp.role_id = :role_id
");
$stmt->bindParam(':role_id', $user['role_id']);
$stmt->execute();
$permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container">
    <div class="dashboard-container">
        <div class="profile-header">
            <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" class="profile-picture" alt="Profile Picture">
            <div>
                <h1 class="mb-1">Welcome, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h1>
                <p class="text-muted mb-0">
                    <span class="badge bg-primary"><?php echo htmlspecialchars($user['role_name']); ?></span>
                    <span class="ms-2"><?php echo htmlspecialchars($user['email']); ?></span>
                </p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Account Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role_name']); ?></p>
                        <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        <a href="update_profile.php" class="btn btn-primary">Edit Profile</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Your Permissions</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php foreach ($permissions as $permission): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $permission))); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (has_permission('manage_users')): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Admin Panel</h5>
            </div>
            <div class="card-body">
                <p>As an administrator, you have access to additional features:</p>
                <div class="d-grid gap-2">
                    <a href="manage_users.php" class="btn btn-outline-primary">Manage Users</a>
                    <?php if (has_permission('manage_roles')): ?>
                        <a href="manage_roles.php" class="btn btn-outline-primary">Manage Roles</a>
                    <?php endif; ?>
                    <?php if (has_permission('manage_permissions')): ?>
                        <a href="manage_permissions.php" class="btn btn-outline-primary">Manage Permissions</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Work in progress</h5>
            </div>
            <div class="card-body">
                <!-- Empty box content -->
            </div>
        </div>
        <div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Portfolio Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Total Portfolio Value</h6>
                                <h3>$25,450.00</h3>
                                <span class="badge bg-success">+5.2% Today</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6>Daily Gain/Loss</h6>
                                <h4 class="text-success">+$320.50</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6>Weekly Gain/Loss</h6>
                                <h4 class="text-success">+$1,250.75</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6>Monthly Gain/Loss</h6>
                                <h4 class="text-danger">-$450.25</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Trading Chart</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 id="currentPrice">$45,250.00</h3>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cryptoModal">Select Crypto</button>
                    </div>
                    <canvas id="tradingChart"></canvas>
                    <div class="btn-group mt-3" role="group" aria-label="Time Range">
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart('1d')">1D</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart('5d')">5D</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart('1m')">1M</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart('6m')">6M</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart('ytd')">YTD</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart('1y')">1Y</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart('5y')">5Y</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart('max')">Max</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Crypto Selection Modal -->
    <div class="modal fade" id="cryptoModal" tabindex="-1" aria-labelledby="cryptoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cryptoModalLabel">Select Cryptocurrency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control mb-3" id="cryptoSearch" placeholder="Search...">
                    <ul class="list-group" id="cryptoList">
                        <li class="list-group-item" onclick="selectCrypto('BTC')">Bitcoin (BTC)</li>
                        <li class="list-group-item" onclick="selectCrypto('ETH')">Ethereum (ETH)</li>
                        <li class="list-group-item" onclick="selectCrypto('DOGE')">Dogecoin (DOGE)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Crypto Selection Modal -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let tradingChart;
        const sampleData = {
            '1d': generateSampleData(24, 45000, 46000),
            '5d': generateSampleData(5 * 24, 44000, 46000),
            '1m': generateSampleData(30, 43000, 46000),
            '6m': generateSampleData(6 * 30, 40000, 46000),
            'ytd': generateSampleData(365, 35000, 46000),
            '1y': generateSampleData(365, 35000, 46000),
            '5y': generateSampleData(5 * 365, 20000, 46000),
            'max': generateSampleData(10 * 365, 10000, 46000)
        };

        function generateSampleData(points, min, max) {
            const data = [];
            for (let i = 0; i < points; i++) {
                data.push({
                    x: new Date(Date.now() - (points - i) * 3600000),
                    y: Math.random() * (max - min) + min
                });
            }
            return data;
        }

        function updateChart(range) {
            const data = sampleData[range];
            tradingChart.data.datasets[0].data = data;
            tradingChart.update();
        }

        function selectCrypto(crypto) {
            document.getElementById('cryptoModal').querySelector('.btn-close').click();
            // Update chart with selected crypto data
        }

        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('tradingChart').getContext('2d');
            tradingChart = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Price',
                        data: sampleData['1d'],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        fill: false
                    }]
                },
                options: {
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'hour'
                            }
                        },
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        });
    </script>

    <div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Trading Section</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5>Bitcoin (BTC)</h5>
                                <p class="h3">$45,250.00</p>
                                <span class="badge bg-success">+2.5%</span>
                                <div class="mt-3">
                                    <form action="process_trade.php" method="POST">
                                        <input type="hidden" name="crypto" value="BTC">
                                        <div class="mb-3">
                                            <input type="number" class="form-control" name="amount" placeholder="Amount">
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="action" value="buy" class="btn btn-success">Buy</button>
                                            <button type="submit" name="action" value="sell" class="btn btn-danger">Sell</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Repeat similar cards for ETH and DOGE -->
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Crypto</th>
                                <th>Amount</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>2025-03-10</td>
                                <td><span class="badge bg-success">Buy</span></td>
                                <td>BTC</td>
                                <td>0.05</td>
                                <td>$45,250.00</td>
                                <td>$2,262.50</td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                            <!-- Add more sample transactions -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
    </div> <!-- end of dashboard-container -->
</div>

<?php include 'footer.php'; ?>