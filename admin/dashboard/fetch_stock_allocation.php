<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

include('../../connection.php');

$branchStocks = [];
$riceVarieties = [];
$branches = ["Calero", "Bauan", "San Pascual"];

function fetchStockData($mysqli, &$branchStocks, &$riceVarieties, $branches) {
    $sql = "
        SELECT 
            products.prod_name AS rice_type,
            branches.branch_name,
            stocks.branch_id,
            products.prod_id,
            SUM(stock_quantity) AS total_stock_quantity
        FROM 
            stocks
        LEFT JOIN products ON products.prod_id = stocks.prod_id
        LEFT JOIN branches ON stocks.branch_id = branches.branch_id
        GROUP BY 
            stocks.branch_id, products.prod_id
        ORDER BY 
            stocks.branch_id, products.prod_id
    ";

    $result = $mysqli->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $branch = $row['branch_name'];
            $riceType = $row['rice_type'];
            $stockQuantity = $row['total_stock_quantity'];

            if (!isset($branchStocks[$riceType])) {
                $branchStocks[$riceType] = array_fill_keys($branches, 0);
            }
            $branchStocks[$riceType][$branch] = (int)$stockQuantity;

            if (!in_array($riceType, $riceVarieties)) {
                $riceVarieties[] = $riceType;
            }
        }
    } else {
        error_log("Query returned no results or failed: " . $mysqli->error);
    }
}

fetchStockData($mysqli, $branchStocks, $riceVarieties, $branches);

$maxStocks = [];
foreach ($branchStocks as $riceType => $stocks) {
    $maxStocks[$riceType] = max($stocks);
}

if (empty($riceVarieties) || empty($branchStocks)) {
    error_log("riceVarieties or branchStocks are empty.");
}

$output = [
    'riceVarieties' => $riceVarieties,
    'branchStocks' => $branchStocks,
    'maxStocks' => $maxStocks
];

header('Content-Type: application/json');
echo json_encode($output);

// Properly close the database connection
$mysqli->close();
?>
