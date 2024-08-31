//Shopping cart function


function toggleCustomQuantity(value, cartId) {
    const quantityContainer = document.getElementById(`quantityContainer-${cartId}`);

    if (value === '4+') {
        quantityContainer.innerHTML = `
            <input type="number" id="customQuantity-${cartId}" class="form-control" style="width: 100px;" min="5" placeholder="Enter Quantity">
            <button class="btn btn-primary mt-2" onclick="updateCustomQuantity(${cartId})">Update</button>
        `;
    } else {
        quantityContainer.innerHTML = `
            <select style="width: 100px;" class="form-select" onchange="toggleCustomQuantity(this.value, ${cartId})">
                ${[1, 2, 3, 4].map(i => `<option value="${i}" ${i == value ? 'selected' : ''}>${i}</option>`).join('')}
                <option value="4+">4+</option>
            </select>
        `;
        updateCart(cartId, value); // Call the function to update the cart
    }
}

function updateCustomQuantity(cartId) {
    const customQuantity = document.getElementById(`customQuantity-${cartId}`).value;

    if (customQuantity && customQuantity > 0) {
        fetch('./Include/update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `cart_id=${cartId}&quantity=${customQuantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateUI(cartId, data);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    } else {
        alert("Please enter a valid quantity.");
    }
}

function updateCart(cartId, quantity) {
    fetch('./Include/update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `cart_id=${cartId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            updateUI(cartId, data);
        } else {
            alert(data.message || 'An error occurred.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updateUI(cartId, data) {
    // Update subtotal for all items in the cart
    const subtotalElement = document.getElementById(`subtotal-${cartId}`);
    if (subtotalElement) {
        subtotalElement.innerText = `₱${data.subtotal}`;
    } else {
        console.warn(`Element with ID subtotal-${cartId} not found.`);
    }

    // Update the quantity display
    const quantityDisplay = document.getElementById(`quantityDisplay-${cartId}`);
    if (quantityDisplay) {
        quantityDisplay.innerText = `Quantity: ${data.quantity}`;
    } else {
        console.warn(`Element with ID quantityDisplay-${cartId} not found.`);
    }

    // Update the quantity select or input field
    const quantityContainer = document.getElementById(`quantityContainer-${cartId}`);
    if (quantityContainer) {
        if (data.quantity > 4) {
            quantityContainer.innerHTML = `
                <input type="number" id="customQuantity-${cartId}" value="${data.quantity}" class="form-control mt-2" style="width: 100px;" min="5">
                <button class="btn btn-primary mt-2" onclick="updateCustomQuantity(${cartId})">Update</button>
            `;
        } else {
            quantityContainer.innerHTML = `
                <select style="width: 100px;" class="form-select" onchange="toggleCustomQuantity(this.value, ${cartId})">
                    ${[1, 2, 3, 4].map(i => `<option value="${i}" ${i == data.quantity ? 'selected' : ''}>${i}</option>`).join('')}
                    <option value="4+">4+</option>
                </select>
            `;
        }
    } else {
    console.warn(`Element with ID quantityContainer-${cartId} not found.`);
    }
    const orderSubtotalElement = document.getElementById('orderSubtotal');
    if (orderSubtotalElement) {
        const subtotal = parseFloat(data.subtotal.replace(/[^0-9.-]+/g, '')) || 0;
        orderSubtotalElement.innerText = `₱${subtotal.toFixed(2)}`;
    } else {
        console.warn('Element with ID orderSubtotal not found.');
    }

    const taxElement = document.getElementById('orderTax');
    if (taxElement) {
        const subtotal = parseFloat(data.subtotal.replace(/[^0-9.-]+/g, '')) || 0;
        const tax = subtotal * 0.05; // 5% tax
        taxElement.innerText = `₱${tax.toFixed(2)}`;
    } else {
        console.warn('Element with ID orderTax not found.');
    }

    const orderTotalPriceElement = document.getElementById('orderTotalPrice');
    if (orderTotalPriceElement) {
        const subtotal = parseFloat(data.subtotal.replace(/[^0-9.-]+/g, '')) || 0;
        const totalPrice = subtotal;
        orderTotalPriceElement.innerText = `₱${totalPrice.toFixed(2)}`;
    } else {
        console.warn('Element with ID orderTotalPrice not found.');
    }
}

function removeCartItem(cartId) {
    fetch('./Include/remove.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            'action': 'remove',
            'cart_id': cartId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Remove item from DOM without refreshing the page
            const itemRow = document.querySelector(`#removeForm-${cartId}`).closest('.row.gy-3.mb-4.align-items-center');
            itemRow.remove();

            // Check if the cart is empty and update the UI
            checkCartEmpty(data);
        } else {
            console.error('Error removing item:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function checkCartEmpty(data) {
    //variables for the document selector
    const remainingItems = document.querySelectorAll('#cartContent .row.gy-3.mb-4.align-items-center').length;
    const cartContent = document.querySelector('#cartContent');
    const orderSubtotal = document.getElementById('orderSubtotal');
    const orderTax = document.getElementById('orderTax');
    const orderTotalPrice = document.getElementById('orderTotalPrice');
    const orderSummary = document.querySelector('.col-lg-3');

    //Test
    const paymentMethodTab = document.getElementById('pills-payment-method-tab');
    const shippingMethodTab = document.getElementById('pills-shipping-method-tab');
    const checkoutTab = document.getElementById('pills-checkout-tab');

    if (remainingItems === 0) {
        // Cart is empty, show empty cart message
        cartContent.innerHTML = `
            <div class="d-flex flex-column justify-content-center align-items-center min-vh-50 text-center">
                <h2 class="mb-4">Your Cart is Empty</h2>
                <p class="lead mb-4">It looks like you have no items in your cart. Don\'t miss out on our great deals!</p>
                <a href="product.php" class="btn btn-primary btn-lg">Shop More</a>
            </div>`;
        
        // Clear summary values
        orderSubtotal.textContent = '₱ 0.00';
        orderTax.textContent = '₱ 0.00';
        orderTotalPrice.textContent = '₱ 0.00';
        
        // Hide the summary section and adjust cart content width
        if (orderSummary) {
            orderSummary.style.display = 'none';
            cartContent.classList.remove('col-lg-9');
            cartContent.classList.add('col-lg-12');
            paymentMethodTab.style.pointerEvents = 'none';
            shippingMethodTab.style.pointerEvents = 'none';
            checkoutTab.style.pointerEvents = 'none';
        }
        
    } else if (data.items) {
        // Update the cart summary based on the new data
        let totalPrice = 0;

        data.items.forEach(item => {
            const subtotal = item.price * item.quantity;
            totalPrice += subtotal;
        });

        const tax = totalPrice * 0.05;
        const orderTotal = totalPrice;

        // Update the summary in the UI
        orderSubtotal.textContent = `₱${totalPrice.toFixed(2)}`;
        orderTax.textContent = `₱${tax.toFixed(2)}`;
        orderTotalPrice.textContent = `₱${orderTotal.toFixed(2)}`;

        // Ensure the summary section is visible and reset cart content width
        if (orderSummary) {
            orderSummary.style.display = '';
            cartContent.classList.remove('col-lg-12');
            cartContent.classList.add('col-lg-9');
        }
    }
}


