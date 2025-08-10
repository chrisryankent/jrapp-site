<?php
include_once "../config/config.php";

// Database connection

// Example: Select or Read data
if (isset($_GET['action']) && $_GET['action'] == 'select') {
    $query = "SELECT * FROM your_table_name"; // Replace with your table name
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['id'] . " - Name: " . $row['name'] . "<br>"; // Replace with your columns
        }
    } else {
        echo "No records found.";
    }
}

// Example: Insert data
if (isset($_POST['action']) && $_POST['action'] == 'insert') {
    $name = mysqli_real_escape_string($conn, $_POST['name']); // Replace with your input fields
    $email = mysqli_real_escape_string($conn, $_POST['email']); // Replace with your input fields
    $query = "INSERT INTO your_table_name (name, email) VALUES ('$name', '$email')"; // Replace with your table and columns
    if ($conn->query($query)) {
        echo "Record inserted successfully.";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Example: Update data
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = mysqli_real_escape_string($conn, $_POST['id']); // Replace with your input fields
    $name = mysqli_real_escape_string($conn, $_POST['name']); // Replace with your input fields
    $query = "UPDATE your_table_name SET name = '$name' WHERE id = '$id'"; // Replace with your table and columns
    if ($conn->query($query)) {
        echo "Record updated successfully.";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Example: Delete data
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = mysqli_real_escape_string($conn, $_POST['id']); // Replace with your input fields
    $query = "DELETE FROM your_table_name WHERE id = '$id'"; // Replace with your table and columns
    if ($conn->query($query)) {
        echo "Record deleted successfully.";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Close the database connection
$conn->close();
?>