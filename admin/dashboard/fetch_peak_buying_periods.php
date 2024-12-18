<?php
session_start();

include('../../connection.php');

// Get the timeframe from the request
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'weekly';

// Function to generate all dates between a start and end date
function generateDateRange($startDate, $endDate, $interval = 'P1D', $format = 'Y-m-d')
{
    $period = new DatePeriod(
        new DateTime($startDate),
        new DateInterval($interval),
        (new DateTime($endDate))->modify('+1 day')
    );

    $dates = [];
    foreach ($period as $date) {
        $dates[] = $date->format($format);
    }

    return $dates;
}

// Define the start and end date
if ($timeframe === 'monthly') {
    $startDate = date('Y-m-01', strtotime('-11 months'));
    $endDate = date('Y-m-t'); // End of the current month
    $interval = 'P1M'; // Monthly interval
    $format = 'Y-m'; // Format for month
} else {
    $startDate = date('Y-m-d', strtotime('-6 days')); // 6 days ago
    $endDate = date('Y-m-d'); // Current date
    $interval = 'P1D'; // Daily interval
    $format = 'Y-m-d'; // Format for day
}

// Generate all dates (weekly or monthly)
$allDates = generateDateRange($startDate, $endDate, $interval, $format);

// SQL query for fetching peak buying periods based on timeframe
if ($timeframe === 'monthly') {
    $sql = "
        SELECT 
            DISTINCT DATE_FORMAT(order_date, '%Y-%m') AS period,
            SUM(total_amount) AS total_sales
        FROM 
            orders
        WHERE 
            order_date >= '$startDate'
            AND order_date <= '$endDate'
            AND order_source = 'in-store' 
        GROUP BY 
            period
        ORDER BY 
            period ASC
    ";
} else {
    $sql = "
    SELECT 
        DATE(order_date) AS period,
        SUM(total_amount) AS total_sales
    FROM 
        orders
    WHERE 
        order_date >= CURDATE() - INTERVAL 6 DAY 
        AND order_date <= '$endDate 23:59:59' 
        AND order_source = 'in-store' 
    GROUP BY 
        DATE(order_date)
    ORDER BY 
        DATE(order_date) ASC
";
}

$result = $mysqli->query($sql);

// Initialize an array with all dates and set default sales to 0
$data = ['periods' => [], 'total_sales' => []];
$salesByDate = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $salesByDate[$row['period']] = $row['total_sales']; 
    }
}

// Populate the data array with all dates, filling missing days with 0 sales
foreach ($allDates as $date) {
    if ($timeframe === 'monthly') {
        $data['periods'][] = date('M Y', strtotime($date)); // Format as 'Mon YYYY'
    } else {
        $data['periods'][] = date('D, M d', strtotime($date)); // Weekly format
    }
    $data['total_sales'][] = isset($salesByDate[$date]) ? $salesByDate[$date] : 0; // Sales or 0
}

echo json_encode($data);
$mysqli->close();
