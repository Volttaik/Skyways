<?php
require 'config.php'; // Include configuration file
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$transaction_id = htmlspecialchars($_GET['transaction_id'] ?? '');
$sender = htmlspecialchars($_GET['sender'] ?? '');
$recipient = htmlspecialchars($_GET['recipient'] ?? '');
$amount = htmlspecialchars($_GET['amount'] ?? '');
$bank = htmlspecialchars($_GET['bank'] ?? BANK_NAME); // Default to Skyways Bank
$time = date('Y-m-d H:i:s'); // Current server time
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Successful</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e3f2fd; /* Light blue background */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 90%;
            max-width: 600px;
            background-color: #ffffff; /* White card */
            border-radius: 10px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
            padding: 30px 20px;
        }
        .checkmark {
            font-size: 80px;
            color: #28a745; /* Green color for success */
        }
        h1 {
            color: #0d47a1; /* Dark blue heading */
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        p {
            color: #333333;
            font-size: 1.1rem;
        }
        .details {
            text-align: left;
            margin-top: 20px;
        }
        .details p {
            margin: 8px 0;
            font-size: 1.05rem;
            color: #555555;
        }
        .back a {
            color: #0d47a1;
            font-size: 1.1rem;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 20px;
        }
        .back a:hover {
            text-decoration: underline;
        }
        .footer {
            margin-top: 15px;
            font-size: 0.9rem;
            color: #757575;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkmark">✅</div>
        <h1>Transaction Successful</h1>
        <p>Your transaction was completed successfully.</p>
        <div class="details">
            <p><strong>Transaction ID:</strong> <?php echo $transaction_id; ?></p>
            <p><strong>Sender:</strong> <?php echo $sender; ?></p>
            <p><strong>Recipient:</strong> <?php echo $recipient; ?></p>
            <p><strong>Amount:</strong> $<?php echo number_format($amount, 2); ?></p>
            <p><strong>Bank:</strong> <?php echo $bank; ?></p>
            <p><strong>Time:</strong> <?php echo $time; ?></p>
        </div>
        <div class="back">
            <p><a href="dashboard.php">Back to Dashboard</a></p>
        </div>
        <div class="footer">
            <p>Powered by Skyways Bank</p>
        </div>
    </div>
</body>
</html>
