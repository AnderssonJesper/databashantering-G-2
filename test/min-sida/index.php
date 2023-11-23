<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GritStore</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

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

    session_start();

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    function addToCart($productId, $connection)
    {
        $sql = "UPDATE Products SET Stock = Stock - 1 WHERE ProductID = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $stmt->close();

        if (array_key_exists($productId, $_SESSION['cart'])) {
            $_SESSION['cart'][$productId]++;
        } else {
            $_SESSION['cart'][$productId] = 1;
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addToCart'])) {
        $productId = $_POST['productId'];
        addToCart($productId, $connection);
        echo "<p class='centered-text'>Produkt tillagd i varukorgen!</p>";
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['placeOrder'])) {
        if (empty($_SESSION['cart'])) {
            echo "<p class='centered-text error-message'>Varukorgen är tom! Lägg till produkter!</p>";
        } else {
            $insufficientStock = false;

            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $sql = "SELECT Stock FROM Products WHERE ProductID = ?";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $currentStock = $row['Stock'];

                    if ($currentStock >= $quantity) {
                        $newStock = max(0, $currentStock - $quantity);

                        $sql = "UPDATE Products SET Stock = ? WHERE ProductID = ?";
                        $stmt = $connection->prepare($sql);
                        $stmt->bind_param("ii", $newStock, $productId);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        $insufficientStock = true;
                        echo "<p class='centered-text'>Otillräckligt lagersaldo för produkten med ID $productId.</p>";
                    }
                }
            }

            if (!$insufficientStock) {
                $firstName = $_POST['firstName'];
                $lastName = $_POST['lastName'];
                $personalNumber = $_POST['personalNumber'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                $postalCode = $_POST['postalCode'];
                $city = $_POST['city'];
                $email = $_POST['email'];

                $checkCustomerSql = "SELECT CustomerID FROM Customers WHERE 
                FirstName = ? AND LastName = ? AND PersonalNumber = ? AND
                Phone = ? AND Address = ? AND PostalCode = ? AND City = ?
                AND Email = ?";

                $stmt = $connection->prepare($checkCustomerSql);
                $stmt->bind_param("ssssssss", $firstName, $lastName, $personalNumber, $phone, $address, $postalCode, $city, $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $customerID = $row['CustomerID'];
                } else {
                    $addCustomerSql = "INSERT INTO Customers (FirstName, LastName, PersonalNumber, Phone, Address, PostalCode, City, Email)
                        VALUES (?,?,?,?,?,?,?,?)";

                    $stmt = $connection->prepare($addCustomerSql);
                    $stmt->bind_param("ssssssss", $firstName, $lastName, $personalNumber, $phone, $address, $postalCode, $city, $email);
                    $stmt->execute();

                    $customerID = $connection->insert_id;
                    $stmt->close();
                }

                $createOrderSql = "INSERT INTO Orders (CustomerID, Status, OrderDate, TotalAmount)
                VALUES (?, 'Processing', NOW(), 0.00)";

                $stmt = $connection->prepare($createOrderSql);
                $stmt->bind_param("i", $customerID);
                $stmt->execute();
                $stmt->close();

                unset($_SESSION['cart']);
                echo "<p class='centered-text'>Order lagd! Varukorgen har rensats.</p>";
            }
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clearCart'])) {
        unset($_SESSION['cart']);
        echo "<p class='centered-text'>Varukorgen har tömts!</p>";
    }

    $sql = "SELECT ProductID, ProductName, Description, Price, Stock FROM Products";
    $result = $connection->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
    ?>
            <div class="product">
                <img src="media/<?php echo $row['ProductID']; ?>.jpg" alt="<?php echo $row['ProductName']; ?>">
                <h3><?php echo $row['ProductName']; ?></h3>
                <p><?php echo $row['Description']; ?></p>
                <p>Pris: <?php echo $row['Price']; ?> SEK</p>
                <p>Lagersaldo: <?php echo $row['Stock']; ?></p>
                <form method="post" action="">
                    <input type="hidden" name="productId" value="<?php echo $row['ProductID']; ?>">
                    <button type="submit" name="addToCart">Lägg till i varukorgen</button>
                </form>
            </div>

    <?php
        }
    } else {
        echo "<p class='centered-text'>Inga produkter tillgängliga.</p>";
    }

    $cartCount = !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
    ?>
    <div class="cart">
        <?php echo $cartCount > 0 ? $cartCount : ''; ?>
    </div>
    <div class="button-container">
        <form method="POST" action="">
            <button type="submit" name="clearCart" class="clear-cart-button">Töm varukorgen</button>
        </form>
    </div>

    <form method="POST" action="">
        <label>Förnamn:</label>
        <input type="text" name="firstName" required>
        <label>Efternamn:</label>
        <input type="text" name="lastName" required>
        <label>Personnummer:</label>
        <input type="text" name="personalNumber" required>
        <label>Telefon:</label>
        <input type="text" name="phone" required>
        <label>Adress:</label>
        <input type="text" name="address" required>
        <label>Postnummer:</label>
        <input type="text" name="postalCode" required>
        <label>Stad:</label>
        <input type="text" name="city" required>
        <label>E-post:</label>
        <input type="text" name="email" required>
        <button type="submit" name="placeOrder">Slutför Köp</button>
    </form>
    <?php
    $connection->close();
    ?>

    <form method="get" action="admin.php">
        <button type="submit">Gå till admin-sidan</button>
    </form>

</body>

</html>