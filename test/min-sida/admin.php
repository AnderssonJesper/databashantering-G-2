<?php
$host = "localhost";
$port = 3306;
$database = "test";
$username = "root";
$password = "";

$connection = new mysqli($host, $username, $password, $database, $port);

if ($connection->connect_error) {
    die("Anslutningen misslyckades:" . $connection->connect_error);
}

$sql = "SELECT Orders.OrderID, Orders.OrderDate, Orders.Status, Customers.FirstName, Customers.LastName
        FROM Orders
        JOIN Customers ON Orders.CustomerID = Customers.CustomerID
        ORDER BY Orders.OrderDate DESC";

$result = $connection->query($sql);

if ($result->num_rows > 0) {
    echo "<h2> Lista alla ordrar: </h2>";
    echo "<table border='1'>
    <tr>
        <th>Order ID</th>
        <th>Order Datum</th>
        <th>Status</th>
        <th>Kund</th>
        </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
              <td>{$row['OrderID']}</td>
                <td>{$row['OrderDate']}</td>
                <td>{$row['Status']}</td>
                <td>{$row['FirstName']} {$row['LastName']}</td>
            </tr>";
    }
    echo "</table>";
} else {
    echo "<p> Inga ordrar hittades. </p>";
}

if (isset($_POST['deleteOrder'])) {
    $orderIdToDelete = $_POST['orderIdToDelete'];
    $deleteOrderSql = "DELETE FROM Orders WHERE OrderID = $orderIdToDelete";
    $connection->query($deleteOrderSql);
    echo "<p>Order raderad! </p>";
}

if (isset($_POST['updateStatus'])) {
    $orderIdToUpdate = $_POST['orderIdToUpdate'];
    $newStatus = $_POST['newStatus'];
    $updateStatusSql = "UPDATE Orders SET Status = '$newStatus' WHERE OrderID = $orderIdToUpdate";
    $connection->query($updateStatusSql);
    echo "<p>Status uppdaterad!</p>";
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
</head>

<body>

    <h1>Admin</h1>

    <form method="post" action="">
        <h2>Ta bort order</h2>
        Order ID: <input type="text" name="orderIdToDelete" required>
        <button type="submit" name="deleteOrder">Ta bort order</button>
    </form>

    <form method="post" action="">
        <h2>Uppdatera orderstatus</h2>
        Order ID: <input type="text" name="orderIdToUpdate" required>
        Ny status: <input type="text" name="newStatus" required>
        <button type="submit" name="updateStatus">Uppdatera status</button>
    </form>

    <form method="get" action="index.php">
        <button type="submit">Gå till produkt-sidan</button>
    </form>

</body>

</html>