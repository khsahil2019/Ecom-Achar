/**
 * Achar Heritage - Master JavaScript
 * Handles AJAX interactions, Cart, Wishlist, Search, Notifications
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Toast Notification Utility
    window.showToast = function (message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const bgClass = type === 'success' ? 'bg-success text-white' : 
                        type === 'error' ? 'bg-danger text-white' : 
                        type === 'warning' ? 'bg-warning text-dark' : 'bg-primary text-white';

        const iconClass = type === 'success' ? 'bi-check-circle-fill' : 
                          type === 'error' ? 'bi-exclamation-octagon-fill' : 
                          type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill';

        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center ${bgClass} border-0 shadow-lg mb-2`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi ${iconClass} fs-5"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(toastEl);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
        bsToast.show();

        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    };

    // 2. Live AJAX Search Suggestions
    const searchInputs = document.querySelectorAll('.live-search-input');
    searchInputs.forEach(input => {
        let debounceTimer;
        const wrapper = input.closest('.live-search-wrapper');
        const dropdown = wrapper ? wrapper.querySelector('.search-results-dropdown') : null;

        if (!dropdown) return;

        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query.length < 2) {
                dropdown.innerHTML = '';
                dropdown.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                const catSelect = wrapper ? wrapper.querySelector('.amz-search-cat-select') : null;
                const catVal = catSelect ? catSelect.value : '';
                fetch(`${window.BASE_URL || ''}/ajax/search.php?q=${encodeURIComponent(query)}&category=${encodeURIComponent(catVal)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.results && data.results.length > 0) {
                            let html = '';
                            data.results.forEach(item => {
                                html += `
                                    <a href="${item.url}" class="search-result-item text-decoration-none">
                                        <img src="${item.image}" alt="${item.name}" class="search-result-img">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-dark small">${item.name}</div>
                                            <div class="text-muted" style="font-size:0.75rem">${item.category} • ${item.weight}</div>
                                            <div class="text-danger fw-bold small">${item.formatted_price}</div>
                                        </div>
                                    </a>
                                `;
                            });
                            dropdown.innerHTML = html;
                            dropdown.style.display = 'block';
                        } else {
                            dropdown.innerHTML = '<div class="p-3 text-muted text-center small">No authentic achar found matching your search.</div>';
                            dropdown.style.display = 'block';
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                    });
            }, 250);
        });

        // Close search dropdown on click outside
        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    });

    // 3. AJAX Add To Cart (Catalog & Featured Products)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-add-to-cart');
        if (!btn) return;
        e.preventDefault();

        const productId = btn.dataset.productId;
        const variantId = btn.dataset.variantId || '';
        const quantity = btn.dataset.quantity || 1;
        const originalHtml = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Adding...';

        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('variant_id', variantId);
        formData.append('quantity', quantity);
        if (window.CSRF_TOKEN) {
            formData.append('csrf_token', window.CSRF_TOKEN);
        }

        fetch(`${window.BASE_URL || ''}/ajax/cart.php?action=add`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;

            if (data.success) {
                showToast(data.message || 'Jar added to your cart!', 'success');
                // Update badge counter
                const badges = document.querySelectorAll('.cart-count-badge');
                badges.forEach(b => b.textContent = data.cart_count);
            } else {
                showToast(data.message || 'Unable to add item.', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            showToast('Network error while adding to cart.', 'error');
        });
    });

    // 4. AJAX Wishlist Toggle
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-toggle-wishlist');
        if (!btn) return;
        e.preventDefault();

        const productId = btn.dataset.productId;
        const icon = btn.querySelector('i');

        const formData = new FormData();
        formData.append('product_id', productId);
        if (window.CSRF_TOKEN) {
            formData.append('csrf_token', window.CSRF_TOKEN);
        }

        fetch(`${window.BASE_URL || ''}/ajax/wishlist.php?action=toggle`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.require_login) {
                showToast('Please login to save to your wishlist.', 'warning');
                setTimeout(() => {
                    window.location.href = `${window.BASE_URL || ''}/auth/login.php`;
                }, 1200);
                return;
            }

            if (data.success) {
                if (data.action === 'added') {
                    btn.classList.add('active');
                    if (icon) {
                        icon.className = 'bi bi-heart-fill text-danger';
                    }
                    showToast('Saved to your Wishlist!', 'success');
                } else {
                    btn.classList.remove('active');
                    if (icon) {
                        icon.className = 'bi bi-heart';
                    }
                    showToast('Removed from your Wishlist.', 'info');
                }
                const badges = document.querySelectorAll('.wishlist-count-badge');
                badges.forEach(b => b.textContent = data.wishlist_count);
            } else {
                showToast(data.message || 'Could not update wishlist.', 'error');
            }
        })
        .catch(() => {
            showToast('Error updating wishlist.', 'error');
        });
    });

    // 5. Quantity selector (+ / -)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.qty-btn');
        if (!btn) return;

        const input = btn.closest('.quantity-selector')?.querySelector('.qty-input');
        if (!input) return;

        let val = parseInt(input.value) || 1;
        if (btn.classList.contains('qty-plus')) {
            val = Math.min(val + 1, 99);
        } else if (btn.classList.contains('qty-minus')) {
            val = Math.max(val - 1, 1);
        }
        input.value = val;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // 6. Product Detail Variant Switcher
    const variantBtns = document.querySelectorAll('.variant-btn');
    variantBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            variantBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const price = this.dataset.price;
            const mrp = this.dataset.mrp;
            const discount = this.dataset.discount;
            const variantId = this.dataset.variantId;
            const stock = parseInt(this.dataset.stock) || 0;

            const priceDisplay = document.getElementById('detailCurrentPrice');
            const mrpDisplay = document.getElementById('detailMrp');
            const discountBadge = document.getElementById('detailDiscountBadge');
            const variantInput = document.getElementById('selectedVariantId');
            const stockDisplay = document.getElementById('detailStockBadge');

            if (priceDisplay) priceDisplay.textContent = `₹${parseFloat(price).toFixed(2)}`;
            if (mrpDisplay) mrpDisplay.textContent = `₹${parseFloat(mrp).toFixed(2)}`;
            if (discountBadge) discountBadge.textContent = `${discount}% OFF`;
            if (variantInput) variantInput.value = variantId;

            if (stockDisplay) {
                if (stock > 0) {
                    stockDisplay.className = 'badge bg-success-subtle text-success border border-success';
                    stockDisplay.textContent = `In Stock (${stock} jars available)`;
                } else {
                    stockDisplay.className = 'badge bg-danger-subtle text-danger border border-danger';
                    stockDisplay.textContent = 'Out of Stock';
                }
            }
        });
    });
});
