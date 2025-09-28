<?php
// ambil data laporan dari database
$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM laporan WHERE id=$id");
$data = mysqli_fetch_assoc($result);
?>

<h2><?= $data['judul']; ?></h2>
<p>Tanggal: <?= $data['tanggal']; ?></p>

<!-- Preview PDF -->
<iframe src="<?= $data['file_path']; ?>" width="100%" height="600px"></iframe>

<!-- Tombol Download -->
<a href="<?= $data['file_path']; ?>" download class="btn btn-primary">Download PDF</a>
