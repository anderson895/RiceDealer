<?php
session_start();

include('../../connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_id = $_POST['prod_id'];
    $stock_quantity = $_POST['stock_quantity'];

    // Validate inputs
    if (empty($prod_id) || empty($stock_quantity) || !is_numeric($stock_quantity) || $stock_quantity < 0) {
        $_SESSION['errorMessage'] = "Invalid input.";
        header("Location: stocks.php");
        exit();
    }


    $branch = 1;    
    // Prepare the SQL statement
    $sql = "INSERT INTO stocks (prod_id, stock_quantity, branch_id ) 
            VALUES (?, ?,?) 
            ON DUPLICATE KEY UPDATE stock_quantity = stock_quantity + VALUES(stock_quantity)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iii", $prod_id, $stock_quantity,$branch);

    if ($stmt->execute()) {
        $_SESSION['successMessage'] = "Stock added successfully!";
    } else {
        $_SESSION['errorMessage'] = "Error: " . $stmt->error;
    }

    $stmt->close();
}

$mysqli->close();

header("Location: stocks.php");
exit();
