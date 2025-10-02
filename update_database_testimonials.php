<?php
/**
 * Script untuk menambahkan tabel testimoni ke database yang sudah ada
 * Jalankan script ini sekali saja untuk menambahkan tabel testimoni
 */

require_once 'backend/config.php';

try {
    // Buat tabel testimoni
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS testimoni (
        testimoni_id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        caption TEXT NOT NULL,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        approved_at TIMESTAMP NULL,
        approved_by INT NULL,
        FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
    )";
    
    $pdo->exec($createTableSQL);
    echo "✅ Tabel testimoni berhasil dibuat!\n";
    
    // Insert data contoh testimoni
    $insertSQL = "
    INSERT IGNORE INTO testimoni (nama, rating, caption, status, approved_at) VALUES
    ('Have Tomven', 5, 'Sangat membantu perkembangan motorik anak saya. Terapisnya profesional dan sabar dalam menangani anak.', 'approved', NOW()),
    ('Arna Wati', 5, 'Terapisnya handal dan komunikatif, kebersihan terjaga, adminnya ramah.', 'approved', NOW()),
    ('Wahyu Titis Kholifah', 5, 'Pelayanannya memuaskan, terapis profesional. Recommended!', 'approved', NOW()),
    ('Ibu Sarah', 4, 'Fasilitas lengkap dan terapis berpengalaman. Anak saya menunjukkan kemajuan yang signifikan.', 'approved', NOW()),
    ('Pak Andi', 5, 'Layanan terbaik di Kendari untuk terapi anak. Sangat recommended untuk orang tua yang mencari solusi untuk anak berkebutuhan khusus.', 'approved', NOW())
    ";
    
    $pdo->exec($insertSQL);
    echo "✅ Data contoh testimoni berhasil ditambahkan!\n";
    
    echo "\n🎉 Database berhasil diupdate! Testimoni sudah siap digunakan.\n";
    echo "\n📋 Cara menggunakan:\n";
    echo "1. Buka halaman website Anda\n";
    echo "2. Scroll ke bagian testimoni (di atas footer)\n";
    echo "3. User bisa mengisi form testimoni\n";
    echo "4. Testimoni akan masuk ke database dengan status 'pending'\n";
    echo "5. Admin bisa approve/reject testimoni melalui API\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
