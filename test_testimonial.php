<?php
// Test file untuk memverifikasi database dan tabel testimonials
header('Content-Type: text/html; charset=utf-8');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "projek1_db";

echo "<h2>Test Database Testimonials</h2>";

// Test koneksi database
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Koneksi database gagal: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ Koneksi database berhasil</p>";
}

// Test apakah tabel testimonials ada
$result = $conn->query("SHOW TABLES LIKE 'testimonials'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Tabel 'testimonials' ditemukan</p>";
    
    // Test struktur tabel
    $result = $conn->query("DESCRIBE testimonials");
    echo "<h3>Struktur Tabel testimonials:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test insert data
    echo "<h3>Test Insert Data:</h3>";
    $testName = "Test User";
    $testProfession = "Test Profession";
    $testMessage = "Test message untuk verifikasi";
    
    $stmt = $conn->prepare("INSERT INTO testimonials (name, profession, message) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $testName, $testProfession, $testMessage);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Insert data berhasil</p>";
            
            // Test select data
            $result = $conn->query("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 5");
            echo "<h3>Data Testimonials Terbaru:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Profession</th><th>Message</th><th>Created At</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['profession']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($row['message'], 0, 50)) . "...</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ Insert data gagal: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>❌ Prepare statement gagal: " . $conn->error . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Tabel 'testimonials' tidak ditemukan</p>";
    echo "<p>Mungkin perlu import database dari file projek1_db.sql</p>";
}

$conn->close();

echo "<hr>";
echo "<h3>Langkah Troubleshooting:</h3>";
echo "<ol>";
echo "<li>Pastikan Laragon/XAMPP sudah running</li>";
echo "<li>Pastikan database 'projek1_db' sudah dibuat</li>";
echo "<li>Pastikan tabel 'testimonials' sudah dibuat (import dari projek1_db.sql)</li>";
echo "<li>Cek kredensial database di save_testimonial.php</li>";
echo "</ol>";
?>
