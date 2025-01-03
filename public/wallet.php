<?php
session_start();

// Connect to SQLite database
$db = new PDO('sqlite:database.sqlite');

// Fetch the logged-in user's ID (replace with actual login logic)
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch user details
$userQuery = $db->prepare("SELECT * FROM users WHERE user_id = :user_id");
$userQuery->execute([':user_id' => $user_id]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);

// Redirect if user is not found
if (!$user) {
    header("Location: login.php");
    exit;
}

// Fetch or initialize e-wallet balance
$walletQuery = $db->prepare("SELECT * FROM e_wallet WHERE user_id = :user_id");
$walletQuery->execute([':user_id' => $user_id]);
$eWallet = $walletQuery->fetch(PDO::FETCH_ASSOC);

// Calculate daily interest
if ($eWallet) {
    $now = new DateTime();
    $lastUpdated = new DateTime($eWallet['last_updated_at']);
    $daysElapsed = $now->diff($lastUpdated)->days;

    if ($daysElapsed > 0) {
        $newBalance = $eWallet['e_wallet_balance'] * pow(1.005, $daysElapsed);
        $updateWallet = $db->prepare("UPDATE e_wallet SET e_wallet_balance = :balance, last_updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id");
        $updateWallet->execute([':balance' => $newBalance, ':user_id' => $user_id]);
        $eWallet['e_wallet_balance'] = $newBalance;
    }
}

// Handle form submission for creating or adding to e-wallet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_amount'])) {
        $amount = floatval($_POST['add_amount']);

        if ($amount > 0 && $amount <= $user['balance']) {
            $db->beginTransaction();

            // Deduct from main balance
            $updateBalance = $db->prepare("UPDATE users SET balance = balance - :amount WHERE user_id = :user_id");
            $updateBalance->execute([':amount' => $amount, ':user_id' => $user_id]);

            if ($eWallet) {
                // Add to existing e-wallet
                $updateWallet = $db->prepare("UPDATE e_wallet SET e_wallet_balance = e_wallet_balance + :amount WHERE user_id = :user_id");
                $updateWallet->execute([':amount' => $amount, ':user_id' => $user_id]);
            } else {
                // Create new e-wallet
                $createWallet = $db->prepare("INSERT INTO e_wallet (user_id, e_wallet_balance) VALUES (:user_id, :amount)");
                $createWallet->execute([':user_id' => $user_id, ':amount' => $amount]);
            }

            $db->commit();
            header("Location: wallet.php");
            exit;
        } else {
            $error = "Invalid amount.";
        }
    } elseif (isset($_POST['withdraw'])) {
        if ($eWallet) {
            $createdAt = new DateTime($eWallet['created_at']);
            $now = new DateTime();
            $monthsElapsed = $now->diff($createdAt)->m;

            if ($monthsElapsed >= 1) {
                $db->beginTransaction();

                // Add e-wallet balance to main balance
                $updateBalance = $db->prepare("UPDATE users SET balance = balance + :amount WHERE user_id = :user_id");
                $updateBalance->execute([':amount' => $eWallet['e_wallet_balance'], ':user_id' => $user_id]);

                // Reset e-wallet balance
                $resetWallet = $db->prepare("UPDATE e_wallet SET e_wallet_balance = 0 WHERE user_id = :user_id");
                $resetWallet->execute([':user_id' => $user_id]);

                $db->commit();
                header("Location: wallet.php");
                exit;
            } else {
                $error = "You can only withdraw after 1 month.";
            }
        } else {
            $error = "No e-wallet balance to withdraw.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Wallet</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f8ff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .wallet-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 90%;
            text-align: center;
        }
        .wallet-container h2 {
            margin-bottom: 20px;
            color: #007bff;
        }
        .balance-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .balance-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            width: 48%;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .balance-box h3 {
            font-size: 18px;
            color: #007bff;
            margin-bottom: 10px;
        }
        .balance-box p {
            font-size: 22px;
            font-weight: bold;
            color: #28a745;
        }
        .balance-box:hover {
            transform: scale(1.05);
        }
        .form-container {
            margin-top: 20px;
        }
        .form-container input[type="number"] {
            padding: 12px;
            width: 100%;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container button {
            padding: 12px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .withdraw-button {
            background-color: #ff5722;
        }
        .form-container button:hover {
            background-color: #218838;
        }
        .withdraw-button:hover {
            background-color: #e64a19;
        }
        .form-container input[type="number"]:focus {
            border-color: #007bff;
        }
        .balance-container {
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <div class="wallet-container">
        <h2>Welcome, <?= htmlspecialchars($user['username']) ?></h2>
        <div class="balance-container">
            <div class="balance-box">
                <i class="fas fa-wallet" style="font-size: 30px;"></i>
                <h3>Main Balance</h3>
                <p>$<?= number_format($user['balance'], 2) ?></p>
            </div>
            <div class="balance-box">
                <i class="fas fa-credit-card" style="font-size: 30px;"></i>
                <h3>E-Wallet Balance</h3>
                <p>$<?= number_format($eWallet['e_wallet_balance'] ?? 0, 2) ?></p>
            </div>
        </div>

        <p class="balance">Earns 0.5% daily interest!</p>

        <div class="form-container">
            <form method="POST">
                <input type="number" name="add_amount" placeholder="Enter amount to add to E-Wallet" min="0" step="0.01" required>
                <button type="submit">Add to E-Wallet</button>
            </form>
        </div>

        <div class="form-container">
            <form method="POST">
                <button class="withdraw-button" name="withdraw">Withdraw to Main Balance</button>
            </form>
        </div>
        
        <?php if (isset($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
