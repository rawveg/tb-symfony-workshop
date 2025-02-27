document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const apiKeyInput = document.getElementById('api-key');
    const loadProductsBtn = document.getElementById('load-products');
    const productsContainer = document.getElementById('products-container');
    const productForm = document.getElementById('product-form');
    const productTypeSelect = document.getElementById('product-type');
    const physicalFields = document.querySelector('.physical-fields');
    const digitalFields = document.querySelector('.digital-fields');
    const productDetailsSection = document.querySelector('.product-details-section');
    const productDetailsContainer = document.getElementById('product-details');
    const editBtn = document.getElementById('edit-product');
    const deleteBtn = document.getElementById('delete-product');
    const backToListBtn = document.getElementById('back-to-list');
    const editSection = document.querySelector('.edit-product-section');
    const editForm = document.getElementById('edit-form');
    const cancelEditBtn = document.getElementById('cancel-edit');
    const notification = document.getElementById('notification');

    // Current product ID being viewed/edited
    let currentProductId = null;

    // Show the appropriate fields based on product type
    productTypeSelect.addEventListener('change', function() {
        if (this.value === 'physical') {
            physicalFields.style.display = 'block';
            digitalFields.style.display = 'none';
        } else {
            physicalFields.style.display = 'none';
            digitalFields.style.display = 'block';
        }
    });

    // Load products from API
    loadProductsBtn.addEventListener('click', function() {
        fetchProducts();
    });

    // Add new product
    productForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const productData = getProductFormData();
        createProduct(productData);
    });

    // Edit product
    editBtn.addEventListener('click', function() {
        productDetailsSection.style.display = 'none';
        editSection.style.display = 'block';
    });

    // Delete product
    deleteBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete this product?')) {
            deleteProduct(currentProductId);
        }
    });

    // Back to list
    backToListBtn.addEventListener('click', function() {
        productDetailsSection.style.display = 'none';
        document.querySelector('.products-section').style.display = 'block';
    });

    // Submit edit form
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const productData = getEditFormData();
        updateProduct(currentProductId, productData);
    });

    // Cancel edit
    cancelEditBtn.addEventListener('click', function() {
        editSection.style.display = 'none';
        productDetailsSection.style.display = 'block';
    });

    // Helper function to get API key
    function getApiKey() {
        return apiKeyInput.value.trim();
    }

    // Helper function to create API headers
    function getHeaders() {
        return {
            'Content-Type': 'application/json',
            'X-API-Key': getApiKey()
        };
    }

    // Helper function to show notifications
    function showNotification(message, isError = false) {
        notification.textContent = message;
        notification.className = 'notification' + (isError ? ' error' : '');
        notification.style.display = 'block';
        
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    // Fetch all products
    function fetchProducts() {
        fetch('/products', {
            headers: getHeaders()
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch products. Status: ' + response.status);
            }
            return response.json();
        })
        .then(products => {
            displayProducts(products);
        })
        .catch(error => {
            showNotification(error.message, true);
        });
    }

    // Display products in the container
    function displayProducts(products) {
        productsContainer.innerHTML = '';
        
        if (products.length === 0) {
            productsContainer.innerHTML = '<p>No products found.</p>';
            return;
        }
        
        products.forEach(product => {
            const productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.innerHTML = `
                <div class="product-type">${product.type}</div>
                <div class="product-name">${product.name}</div>
                <div class="product-price">$${product.price.toFixed(2)}</div>
            `;
            
            productCard.addEventListener('click', () => {
                fetchProductDetails(product.id);
            });
            
            productsContainer.appendChild(productCard);
        });
    }

    // Fetch details for a specific product
    function fetchProductDetails(productId) {
        fetch(`/products/${productId}`, {
            headers: getHeaders()
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch product details. Status: ' + response.status);
            }
            return response.json();
        })
        .then(product => {
            displayProductDetails(product);
            currentProductId = product.id;
            document.querySelector('.products-section').style.display = 'none';
            productDetailsSection.style.display = 'block';
            populateEditForm(product);
        })
        .catch(error => {
            showNotification(error.message, true);
        });
    }

    // Display product details
    function displayProductDetails(product) {
        let detailsHtml = `
            <div class="detail-row">
                <div class="detail-label">ID:</div>
                <div class="detail-value">${product.id}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Type:</div>
                <div class="detail-value">${product.type}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Name:</div>
                <div class="detail-value">${product.name}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Description:</div>
                <div class="detail-value">${product.description}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Price:</div>
                <div class="detail-value">$${product.price.toFixed(2)}</div>
            </div>
        `;
        
        if (product.type === 'physical') {
            detailsHtml += `
                <div class="detail-row">
                    <div class="detail-label">SKU:</div>
                    <div class="detail-value">${product.sku || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Weight:</div>
                    <div class="detail-value">${product.weight ? product.weight + ' kg' : 'N/A'}</div>
                </div>
            `;
        } else if (product.type === 'digital') {
            detailsHtml += `
                <div class="detail-row">
                    <div class="detail-label">Download URL:</div>
                    <div class="detail-value">${product.download_url || 'N/A'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">File Size:</div>
                    <div class="detail-value">${product.file_size ? formatFileSize(product.file_size) : 'N/A'}</div>
                </div>
            `;
        }
        
        productDetailsContainer.innerHTML = detailsHtml;
    }

    // Format file size for display
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' bytes';
        else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
        else if (bytes < 1073741824) return (bytes / 1048576).toFixed(2) + ' MB';
        else return (bytes / 1073741824).toFixed(2) + ' GB';
    }

    // Get data from the add product form
    function getProductFormData() {
        const type = productTypeSelect.value;
        const data = {
            type: type,
            name: document.getElementById('product-name').value,
            description: document.getElementById('product-description').value,
            price: parseFloat(document.getElementById('product-price').value)
        };
        
        if (type === 'physical') {
            const sku = document.getElementById('product-sku').value;
            const weight = document.getElementById('product-weight').value;
            
            if (sku) data.sku = sku;
            if (weight) data.weight = parseFloat(weight);
        } else {
            const downloadUrl = document.getElementById('product-download-url').value;
            const fileSize = document.getElementById('product-file-size').value;
            
            if (downloadUrl) data.download_url = downloadUrl;
            if (fileSize) data.file_size = parseInt(fileSize);
        }
        
        return data;
    }

    // Create a new product
    function createProduct(productData) {
        fetch('/products', {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify(productData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to create product. Status: ' + response.status);
            }
            return response.json();
        })
        .then(product => {
            showNotification('Product created successfully!');
            productForm.reset();
            fetchProducts();
        })
        .catch(error => {
            showNotification(error.message, true);
        });
    }

    // Populate the edit form with product data
    function populateEditForm(product) {
        document.getElementById('edit-product-id').value = product.id;
        document.getElementById('edit-product-type').value = product.type;
        document.getElementById('edit-product-name').value = product.name;
        document.getElementById('edit-product-description').value = product.description;
        document.getElementById('edit-product-price').value = product.price;
        
        const physicalFields = document.querySelector('.edit-physical-fields');
        const digitalFields = document.querySelector('.edit-digital-fields');
        
        if (product.type === 'physical') {
            physicalFields.style.display = 'block';
            digitalFields.style.display = 'none';
            document.getElementById('edit-product-sku').value = product.sku || '';
            document.getElementById('edit-product-weight').value = product.weight || '';
        } else {
            physicalFields.style.display = 'none';
            digitalFields.style.display = 'block';
            document.getElementById('edit-product-download-url').value = product.download_url || '';
            document.getElementById('edit-product-file-size').value = product.file_size || '';
        }
    }

    // Get data from the edit form
    function getEditFormData() {
        const type = document.getElementById('edit-product-type').value;
        const data = {
            name: document.getElementById('edit-product-name').value,
            description: document.getElementById('edit-product-description').value,
            price: parseFloat(document.getElementById('edit-product-price').value)
        };
        
        if (type === 'physical') {
            const sku = document.getElementById('edit-product-sku').value;
            const weight = document.getElementById('edit-product-weight').value;
            
            if (sku) data.sku = sku;
            if (weight) data.weight = parseFloat(weight);
        } else {
            const downloadUrl = document.getElementById('edit-product-download-url').value;
            const fileSize = document.getElementById('edit-product-file-size').value;
            
            if (downloadUrl) data.download_url = downloadUrl;
            if (fileSize) data.file_size = parseInt(fileSize);
        }
        
        return data;
    }

    // Update a product
    function updateProduct(productId, productData) {
        fetch(`/products/${productId}`, {
            method: 'PUT',
            headers: getHeaders(),
            body: JSON.stringify(productData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to update product. Status: ' + response.status);
            }
            return response.json();
        })
        .then(product => {
            showNotification('Product updated successfully!');
            editSection.style.display = 'none';
            fetchProductDetails(productId);
        })
        .catch(error => {
            showNotification(error.message, true);
        });
    }

    // Delete a product
    function deleteProduct(productId) {
        fetch(`/products/${productId}`, {
            method: 'DELETE',
            headers: getHeaders()
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to delete product. Status: ' + response.status);
            }
            showNotification('Product deleted successfully!');
            productDetailsSection.style.display = 'none';
            document.querySelector('.products-section').style.display = 'block';
            fetchProducts();
        })
        .catch(error => {
            showNotification(error.message, true);
        });
    }
});