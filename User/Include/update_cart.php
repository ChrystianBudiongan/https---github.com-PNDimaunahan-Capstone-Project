<?php
session_start();
require 'db.handler.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : null;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : null;

    if (!$cart_id || !$quantity || ($quantity <= 0)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
        exit();
    }

    try {
        // Fetch the current product_id from the cart
        $sql = "SELECT product_id FROM tbl_cart WHERE cart_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cart_id]);
        $product_id = $stmt->fetchColumn();

        if (!$product_id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid cart item.']);
            exit();
        }

        // Fetch the current inventory
        $sql = "SELECT Quantity FROM tbl_inventory WHERE ProductID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id]);
        $available_quantity = $stmt->fetchColumn();
        $stmt->closeCursor();

        // Check if the requested quantity is available
        if ($quantity > $available_quantity) {
            echo json_encode(['status' => 'error', 'message' => "Sorry, only $available_quantity items are available in stock."]);
            exit();
        } else {
            // Update the cart item
            $updateSql = "UPDATE tbl_cart SET quantity = ? WHERE cart_id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$quantity, $cart_id]);

            // Fetch the price of the product
            $sql = "SELECT price FROM tbl_product WHERE ProductID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$product_id]);
            $price = $stmt->fetchColumn();
            $stmt->closeCursor();

            // Calculate the new total price for the specific cart item
            $total_price = $price * $quantity;

            // Calculate the new subtotal for all items in the cart
            if (isset($_SESSION['user_id'])) {
                $sql = "SELECT SUM(c.quantity * p.price) AS subtotal FROM tbl_cart c JOIN tbl_product p ON c.product_id = p.ProductID WHERE c.user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id']]); // Assuming user_id is stored in session
                $subtotal = $stmt->fetchColumn();
                $stmt->closeCursor();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'User not authenticated.']);
                exit();
            }

            echo json_encode([
                'status' => 'success',
                'total_price' => number_format($total_price, 2),
                'subtotal' => number_format($subtotal, 2),
                'quantity' => $quantity
            ]);
        }

    } catch (Exception $e) {
        // Log error for debugging (if you have a logging system)
        error_log("Error in update_cart.php: " . $e->getMessage());

        echo json_encode(['status' => 'error', 'message' => "An error occurred."]);
        exit();
    }
}
?>
