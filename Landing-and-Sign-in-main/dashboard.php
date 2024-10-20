<?php
session_start();

// Check if the user is logged in, if not try using the cookie to log them in, otherwise redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    if (isset($_COOKIE["user"])) {
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $_COOKIE["user"];
    } else {
        header("location: index.php");
        exit;
    }
}

// Renew the session cookie for 30 more days
setcookie("user", $_SESSION["username"], time() + (86400 * 30), "/");

require 'db_config.php'; // Database connection logic

// Fetch products from the database
function fetchProducts($conn) {
    $products = [];
    $sql = "SELECT product_id, productname, product_code, productcategory, description, quantity, price, availability FROM products";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

$products = fetchProducts($conn);

// Handle form submission for adding new products
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $productname = trim($_POST['productname']);
    $product_code = trim($_POST['product_code']);
    $productcategory = trim($_POST['productcategory']);
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $availability = isset($_POST['availability']) ? 1 : 0; // Boolean value for availability

    // Insert product into database
    $sql = "INSERT INTO products (productname, product_code, productcategory, description, quantity, price, availability) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssssiid", $productname, $product_code, $productcategory, $description, $quantity, $price, $availability);
        if ($stmt->execute()) {
            // Fetch updated product list
            $products = fetchProducts($conn);
        } else {
            // Handle duplicate entry error
            if ($stmt->errno === 1062) { // Duplicate entry
                echo "<script>alert('Error: Duplicate product code \"$product_code\" detected!');</script>";
            } else {
                echo "<script>alert('Error adding product: " . $stmt->error . "');</script>";
            }
        }
        $stmt->close();
    } else {
        echo "<script>alert('Error preparing statement: " . $conn->error . "');</script>";
    }
}

// Handle form submission for updating products
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $product_id = (int)$_POST['product_id'];
    $productname = trim($_POST['productname']);
    $product_code = trim($_POST['product_code']);
    $productcategory = trim($_POST['productcategory']);
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $availability = isset($_POST['availability']) ? 1 : 0; // Boolean value for availability

    $sql = "UPDATE products SET productname=?, product_code=?, productcategory=?, description=?, quantity=?, price=?, availability=? WHERE product_id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssiiidi", $productname, $product_code, $productcategory, $description, $quantity, $price, $availability, $product_id);
        if ($stmt->execute()) {
            // Fetch updated product list
            $products = fetchProducts($conn);
        } else {
            echo "<script>alert('Error updating product: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}

// Handle deletion of products
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $sql = "DELETE FROM products WHERE product_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        // Fetch updated product list
        $products = fetchProducts($conn);
    } else {
        echo "<script>alert('Error deleting product: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoe Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <a href="index.php"><img style="width: 100px; cursor: pointer;" src="Images/logo.jpg" class="logo"></a>
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a style="color: #CE1126;" class="nav-link active" aria-current="page" href="dashboard.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a style="color: #CE1126;" class="nav-link" href="#">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a style="color: #CE1126;" class="nav-link" href="#">Contact Us</a>
                    </li>
                </ul>
                <span class="navbar-text" style="margin-right: 20px;">
                    <a href="profile.php" style="color: #CE1126; text-decoration: none;">
                        <?php echo htmlspecialchars($_SESSION["username"]); ?>
                    </a>
                </span>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>DASHBOARD</h2><br>

        <!-- Form for adding new products -->
        <form method="POST" class="mb-4">
            <h4>Add New Product</h4>
            <div class="mb-3">
                <label for="productname" class="form-label">Product Name</label>
                <input type="text" class="form-control" name="productname" id="productname" required>
            </div>
            <div class="mb-3">
                <label for="product_code" class="form-label">Product Code</label>
                <input type="text" class="form-control" name="product_code" id="product_code" required>
            </div>
            <div class="mb-3">
                <label for="productcategory" class="form-label">Product Category</label>
                <input type="text" class="form-control" name="productcategory" id="productcategory" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Product Description</label>
                <textarea class="form-control" name="description" required></textarea>
            </div>
            <div class="mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" name="quantity" id="quantity" required>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" step="0.01" class="form-control" name="price" id="price" required>
            </div>
            <div class="mb-3">
                <label for="availability" class="form-label">Available</label>
                <input type="checkbox" name="availability" id="availability">
            </div>
            <button type="submit" name="add_product" class="btn btn-success">Add Product</button>
        </form>

        <!-- Displaying the Product List -->
        <br><hr><br><br>
        <h4>Product List</h4>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach ($products as $product): ?>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['productname']); ?></h5>
                        <p class="card-text"><strong>Product Code:</strong>                         <?php echo htmlspecialchars($product['product_code']); ?></p>
                        <p class="card-text"><strong>Category:</strong> <?php echo htmlspecialchars($product['productcategory']); ?></p>
                        <p class="card-text"><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="card-text"><strong>Quantity:</strong> <?php echo htmlspecialchars($product['quantity']); ?></p>
                        <p class="card-text"><strong>Price:</strong> ₱<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></p>
                        <p class="card-text"><strong>Available:</strong> <?php echo $product['availability'] ? 'Yes' : 'No'; ?></p>
                    </div>
                    <div class="card-footer">
                        <a href="dashboard.php?delete=<?php echo $product['product_id']; ?>" class="btn btn-danger">Delete</a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $product['product_id']; ?>"> Edit</button>
                    </div>
                </div>
            </div>

            <!-- For updating the product  -->
            <div class="modal fade" id="updateModal<?php echo $product['product_id']; ?>" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="updateModalLabel">Update Product</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <div class="mb-3">
                                    <label for="productname" class="form-label">Product Name</label>
                                    <input type="text" class="form-control" name="productname" value="<?php echo htmlspecialchars($product['productname']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="product_code" class="form-label">Product Code</label>
                                    <input type="text" class="form-control" name="product_code" value="<?php echo htmlspecialchars($product['product_code']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="productcategory" class="form-label">Product Category</label>
                                    <input type="text" class="form-control" name="productcategory" value="<?php echo htmlspecialchars($product['productcategory']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Product Description</label>
                                    <textarea class="form-control" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" name="quantity" value="<?php echo htmlspecialchars($product['quantity']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="availability" class="form-label">Available</label>
                                    <input type="checkbox" name="availability" <?php echo $product['availability'] ? 'checked' : ''; ?>>
                                </div>
                                <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

