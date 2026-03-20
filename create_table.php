<?php
// create_table.php - Simple table creation script

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Create OTP Table</title>";
echo "<style>body{font-family:Arial;margin:20px;background:#f5f5f5;} .container{background:white;padding:20px;border-radius:8px;max-width:600px;} .success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;border-radius:4px;margin:10px 0;} .error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;border-radius:4px;margin:10px 0;} button{background:#2d7a3e;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;font-size:16px;} button:hover{background:#1a4d2e;} code{background:#f0f0f0;padding:2px 5px;}</style></head><body>";

echo "<div class='container'>";
echo "<h1>Create OTP Codes Table</h1>";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include "db.php";
    
    if ($conn->connect_error) {
        echo "<div class='error'>Database connection failed: " . $conn->connect_error . "</div>";
    } else {
        // Create table
        $sql = "CREATE TABLE IF NOT EXISTS otp_codes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            code VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(email),
            INDEX(expires_at)
        )";
        
        if ($conn->query($sql) === TRUE) {
            echo "<div class='success'>";
            echo "<h2>✓ Success!</h2>";
            echo "<p>The <code>otp_codes</code> table has been created successfully.</p>";
            echo "<p>Your OTP system is now ready to use!</p>";
            echo "</div>";
            
            // Show table info
            $tableInfo = $conn->query("DESCRIBE otp_codes");
            echo "<h3>Table Structure:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
            while ($row = $tableInfo->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } else {
            echo "<div class='error'>";
            echo "Error creating table: " . $conn->error;
            echo "</div>";
        }
        
        $conn->close();
    }
} else {
    echo "<p>Click the button below to create the <code>otp_codes</code> table:</p>";
    echo "<form method='POST'>";
    echo "<button type='submit'>Create Table</button>";
    echo "</form>";
    echo "<p style='color:#666;font-size:14px;'>This table stores OTP codes for password resets with automatic expiry after 10 minutes.</p>";
}

echo "</div></body></html>";
?>
