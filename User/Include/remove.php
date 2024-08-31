<?php
session_start();
require 'db.handler.inc.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : null;

    if (!$cart_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid cart ID.']);
        exit();
    }

    try {
        // Delete the cart item from the database
        $stmt = $pdo->prepare("DELETE FROM tbl_cart WHERE cart_id = ?");
        $stmt->execute([$cart_id]);

        // Calculate the new cart summary (subtotal, total, etc.)
        if (isset($_SESSION['user_id'])) {
            // Fetch updated cart items and summary
            $stmt = $pdo->prepare("SELECT c.cart_id, c.quantity, p.name, p.price, p.product_image 
                                   FROM tbl_cart c 
                                   JOIN tbl_product p ON c.product_id = p.ProductID 
                                   WHERE c.user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate subtotal
            $subtotal = array_reduce($items, function($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);

            // Check if the cart is empty
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $isEmpty = $stmt->fetchColumn() == 0;

            echo json_encode([
                'status' => 'success',
                'items' => $items,
                'total_price' => number_format($subtotal, 2),
                'subtotal' => number_format($subtotal, 2),
                'cartEmpty' => $isEmpty
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not authenticated.']);
        }

    } catch (Exception $e) {
        error_log("Error in remove.php: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => "An error occurred."]);
    }

    exit();
}