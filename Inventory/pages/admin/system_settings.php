<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/navbar.php';

if (!Auth::hasAnyRole(['admin', 'owner'])) {
    header('Location: ../../login.php?error=Unauthorized access');
    exit();
}

ensureSystemSettingsTable($conn);

$defaults = [
    'app_name' => APP_NAME,
    'company_name' => 'MangaringMed Pharmacy',
    'currency_code' => 'PHP',
    'currency_symbol' => '₱',
    'tax_rate' => '0',
    'low_stock_threshold' => '10',
    'session_timeout' => (string) SESSION_TIMEOUT,
    'allow_registration' => '1',
    'maintenance_mode' => '0'
];

$settings = [];
foreach ($defaults as $key => $default) {
    $settings[$key] = getSystemSetting($conn, $key, $default);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['app_name'] = trim($_POST['app_name'] ?? APP_NAME);
    $settings['company_name'] = trim($_POST['company_name'] ?? 'MangaringMed Pharmacy');
    $settings['currency_code'] = strtoupper(trim($_POST['currency_code'] ?? 'PHP'));
    $settings['currency_symbol'] = trim($_POST['currency_symbol'] ?? '₱');
    $settings['tax_rate'] = max(0, min(100, floatval($_POST['tax_rate'] ?? 0)));
    $settings['low_stock_threshold'] = max(0, intval($_POST['low_stock_threshold'] ?? 10));
    $settings['session_timeout'] = max(300, intval($_POST['session_timeout'] ?? SESSION_TIMEOUT));
    $settings['allow_registration'] = isset($_POST['allow_registration']) ? '1' : '0';
    $settings['maintenance_mode'] = isset($_POST['maintenance_mode']) ? '1' : '0';

    foreach ($settings as $key => $value) {
        setSystemSetting($conn, $key, (string) $value);
    }

    $notice = 'System settings saved successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php renderSidebar('settings'); ?>
    <div class="main-wrapper">
        <?php renderTopNavbar(); ?>
        <div class="main-content">
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-cog"></i> System Settings</h1>
                    <p>Configure site settings and system preferences.</p>
                </div>
            </div>

            <?php if (!empty($notice)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($notice); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="app_name" class="form-label">Application Name</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" value="<?php echo htmlspecialchars($settings['app_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($settings['company_name']); ?>" required>
                            </div>

                            <div class="col-md-3">
                                <label for="currency_code" class="form-label">Currency Code</label>
                                <select class="form-select" id="currency_code" name="currency_code">
                                    <option value="PHP" <?php echo ($settings['currency_code'] === 'PHP') ? 'selected' : ''; ?>>PHP</option>
                                    <option value="USD" <?php echo ($settings['currency_code'] === 'USD') ? 'selected' : ''; ?>>USD</option>
                                    <option value="EUR" <?php echo ($settings['currency_code'] === 'EUR') ? 'selected' : ''; ?>>EUR</option>
                                    <option value="AUD" <?php echo ($settings['currency_code'] === 'AUD') ? 'selected' : ''; ?>>AUD</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="currency_symbol" class="form-label">Currency Symbol</label>
                                <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>" maxlength="5">
                            </div>
                            <div class="col-md-3">
                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($settings['tax_rate']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" min="0" value="<?php echo htmlspecialchars($settings['low_stock_threshold']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="session_timeout" class="form-label">Session Timeout (seconds)</label>
                                <input type="number" class="form-control" id="session_timeout" name="session_timeout" min="300" step="60" value="<?php echo htmlspecialchars($settings['session_timeout']); ?>">
                            </div>

                            <div class="col-md-6 d-flex align-items-end">
                                <div class="w-100">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" role="switch" id="allow_registration" name="allow_registration" <?php echo ($settings['allow_registration'] == '1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="allow_registration">Allow new user registration</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="maintenance_mode" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] == '1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="maintenance_mode">Enable maintenance mode</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="reset" class="btn btn-outline-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/script.js"></script>
</body>
</html>
