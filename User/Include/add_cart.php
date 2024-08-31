<?php
session_start();
require 'db.handler.inc.php';

// Check if user is signed in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../SignIn.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : null;

    if (!$product_id || !$quantity || ($quantity <= 0)) {
        echo "Invalid request.";
        exit();
    }

    try {
        // Fetch the current inventory
        $sql = "SELECT Quantity FROM tbl_inventory WHERE ProductID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
        $available_quantity = $stmt->fetchColumn();
        $stmt->closeCursor();

        // Check if the requested quantity is available
        if ($quantity > $available_quantity) {
            $_SESSION['alert'] = "Oops";
            $_SESSION['alert_message'] = "Sorry, only $available_quantity items are available in stock.";
            header("Location: ../product_detail.php?id=$product_id");
            exit();
        }

        // Check if the product is already in the cart
        $sql = "SELECT cart_id, quantity FROM tbl_cart WHERE user_id = ? AND product_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $product_id]);
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart_item) {
            // If product is already in the cart, update the quantity
            $existing_quantity = $cart_item['quantity'];
            $new_quantity = $existing_quantity + $quantity;

            // Check if the new quantity exceeds available stock
            if ($new_quantity > $available_quantity) {
                $_SESSION['alert'] = "Oops";
                $_SESSION['cart_message'] = "Sorry, only $available_quantity items are available in stock.";
            } else {
                // Update the quantity of the existing cart item
                $updateSql = "UPDATE tbl_cart SET quantity = ? WHERE cart_id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$new_quantity, $cart_item['cart_id']]);
                $_SESSION['alert'] = "Success";
                $_SESSION['cart_message'] = "Cart updated successfully!";
            }
        } else {
            // Insert the new item into the cart
            if ($quantity > $available_quantity) {
                $_SESSION['alert'] = "Oops";
                $_SESSION['cart_message'] = "Sorry, only $available_quantity items are available in stock.";
            } else {
                $insertSql = "INSERT INTO tbl_cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([$user_id, $product_id, $quantity]);
                $_SESSION['alert'] = "Success";
                $_SESSION['cart_message'] = "Item added to cart successfully!";
            }
        }

        // Redirect back to the shopping cart page without query parameters
        header("Location: ../shopping cart.php");
        exit();

    } catch (Exception $e) {
        echo "An error occurred: " . $e->getMessage();
        exit();
    }
}

$pdo = null; // Close the PDO connection
?>
