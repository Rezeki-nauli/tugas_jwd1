<?php
session_start();
require 'koneksi.php';

// 1. CEK LOGIN
if (!isset($_SESSION["login"])) {
    header("Location: login.php");
    exit;
}

// ==========================================
// 2. LOGIKA UTAMA (UPLOAD, EDIT, HAPUS)
// ==========================================

// A. LOGIKA UPLOAD BARU
if (isset($_POST["upload"])) {
    $kategori = $_POST["kategori"]; 
    $namaFile = $_FILES["gambar"]["name"];
    $tmpName  = $_FILES["gambar"]["tmp_name"];
    $error    = $_FILES["gambar"]["error"];

    if ($error === 4) {
        echo "<script>alert('Pilih gambar terlebih dahulu!');</script>";
    } else {
        $ekstensiValid = ['jpg', 'jpeg', 'png'];
        $ekstensi      = explode('.', $namaFile);
        $ekstensi      = strtolower(end($ekstensi));

        if (!in_array($ekstensi, $ekstensiValid)) {
            echo "<script>alert('File harus berupa gambar (JPG/PNG)!');</script>";
        } else {
            $namaFileBaru = uniqid() . '.' . $ekstensi;
            move_uploaded_file($tmpName, 'uploads/' . $namaFileBaru);

            $tabel = ($kategori == "galeri") ? "galeri" : "klien";
            mysqli_query($conn, "INSERT INTO $tabel (nama_file) VALUES ('$namaFileBaru')");

            echo "<script>alert('Berhasil upload foto!'); window.location='index.php';</script>";
        }
    }
}

// B. LOGIKA HAPUS GAMBAR
if (isset($_POST["hapus_gambar"])) {
    $id = $_POST["id_item"];
    $kategori = $_POST["kategori_item"];
    $tabel = ($kategori == "galeri") ? "galeri" : "klien";

    // 1. Ambil nama file lama untuk dihapus dari folder
    $result = mysqli_query($conn, "SELECT nama_file FROM $tabel WHERE id = '$id'");
    $data = mysqli_fetch_assoc($result);
    
    // 2. Hapus file fisik
    if (file_exists("uploads/" . $data['nama_file'])) {
        unlink("uploads/" . $data['nama_file']);
    }

    // 3. Hapus data di database
    mysqli_query($conn, "DELETE FROM $tabel WHERE id = '$id'");
    
    echo "<script>alert('Foto berhasil dihapus!'); window.location='index.php';</script>";
}

// C. LOGIKA EDIT (GANTI) GAMBAR
if (isset($_POST["edit_gambar"])) {
    $id = $_POST["edit_id"];
    $kategori = $_POST["edit_kategori"];
    $tabel = ($kategori == "galeri") ? "galeri" : "klien";

    $namaFile = $_FILES["gambar_baru"]["name"];
    $tmpName  = $_FILES["gambar_baru"]["tmp_name"];
    $error    = $_FILES["gambar_baru"]["error"];

    // Jika user upload gambar baru
    if ($error === 0) {
        $ekstensiValid = ['jpg', 'jpeg', 'png'];
        $ekstensi      = explode('.', $namaFile);
        $ekstensi      = strtolower(end($ekstensi));

        if (!in_array($ekstensi, $ekstensiValid)) {
            echo "<script>alert('File harus berupa gambar (JPG/PNG)!');</script>";
        } else {
            // 1. Hapus file lama
            $result = mysqli_query($conn, "SELECT nama_file FROM $tabel WHERE id = '$id'");
            $data = mysqli_fetch_assoc($result);
            if (file_exists("uploads/" . $data['nama_file'])) {
                unlink("uploads/" . $data['nama_file']);
            }

            // 2. Upload file baru
            $namaFileBaru = uniqid() . '.' . $ekstensi;
            move_uploaded_file($tmpName, 'uploads/' . $namaFileBaru);

            // 3. Update database
            mysqli_query($conn, "UPDATE $tabel SET nama_file = '$namaFileBaru' WHERE id = '$id'");
            echo "<script>alert('Foto berhasil diperbarui!'); window.location='index.php';</script>";
        }
    } else {
        echo "<script>alert('Anda tidak memilih foto baru!'); window.location='index.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Rezeki Site</title>
    <style>
        /* --- 1. GLOBAL RESET & BASE STYLES --- */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; color: #333; }
        
        /* --- 2. LAYOUT CONTAINER --- */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            min-height: 100vh;
            border-left: 1px solid #ddd;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
        }

        /* --- 3. HEADER & LOGO --- */
        .header {
            background-color: white;
            padding: 20px;
            border-bottom: 2px solid #333;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .logo {
            width: 80px; height: 80px;
            border: 2px solid #333;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background-color: #f0f0f0;
        }
        .logo-inner {
            width: 40px; height: 40px;
            background-color: #4a90a4;
            border-radius: 50%;
        }
        .site-title {
            font-size: 32px;
            font-weight: bold;
            color: #8b8b6b;
            letter-spacing: 10px;
            text-transform: uppercase;
        }

        /* --- 4. NAVIGATION MENU --- */
        .navigation { background-color: #f8f8f8; border-bottom: 2px solid #333; }
        .nav-menu { display: flex; list-style: none; overflow-x: auto; }
        .nav-menu li { flex: 1; text-align: center; border-right: 1px solid #ddd; min-width: 100px; }
        .nav-menu li:last-child { border-right: none; }
        .nav-menu a {
            display: block; padding: 15px 10px;
            text-decoration: none; color: #333;
            font-size: 14px; transition: background-color 0.3s;
            cursor: pointer; white-space: nowrap;
        }
        .nav-menu a:hover, .nav-menu a.active { background-color: #e0e0e0; font-weight: bold; }

        /* --- 5. MAIN WRAPPER --- */
        .main-wrapper { display: flex; flex: 1; }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: white;
            border-right: 2px solid #333;
            padding: 20px;
            flex-shrink: 0;
        }
        .sidebar-header {
            margin-bottom: 10px; font-size: 16px;
            border-bottom: 1px solid #ddd; padding-bottom: 10px;
            margin-top: 20px;
            cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
        }
        .sidebar-header:first-child { margin-top: 0; }
        .sidebar-header:hover { color: #4a90a4; }
        
        .sidebar ul { 
            list-style: none; 
            max-height: 500px;
            overflow: hidden;
            transition: max-height 0.4s ease-out;
        }
        .sidebar ul.collapsed { max-height: 0; }
        .sidebar ul li { margin-bottom: 8px; padding-left: 10px; }
        .sidebar ul li::before { content: '• '; color: #333; }
        .sidebar a {
            color: #333; text-decoration: none;
            font-size: 14px; cursor: pointer;
        }
        .sidebar a:hover { text-decoration: underline; color: #4a90a4; }

        /* Content Area */
        .content { flex: 1; padding: 30px; overflow-y: auto; }
        .content h2 { margin-bottom: 15px; color: #333; border-bottom: 2px solid #4a90a4; padding-bottom: 10px; display: inline-block; }
        .content p { line-height: 1.6; margin-bottom: 15px; text-align: justify; }
        .hidden { display: none; }

        /* --- 6. COMPONENTS --- */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; margin-top: 20px;
        }
        .product-card, .article-card {
            background: white; border: 1px solid #ddd;
            border-radius: 8px; padding: 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .article-card { text-align: left; }
        .product-card:hover, .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #4a90a4;
        }
        .product-icon { font-size: 40px; color: #4a90a4; margin-bottom: 15px; display: block; }
        .product-card h3 { margin-bottom: 10px; color: #333; }
        .product-card p { font-size: 14px; color: #666; margin-bottom: 20px; }
        .btn-order {
            display: inline-block; padding: 8px 20px;
            background-color: #4a90a4; color: white;
            text-decoration: none; border-radius: 20px;
            font-size: 14px; transition: background 0.3s;
        }
        .btn-order:hover { background-color: #357a8a; }

        /* --- 7. GALERI & UPLOAD --- */
        .upload-box {
            background-color: #e9ecef; padding: 15px;
            margin-bottom: 20px; border-radius: 5px;
            border: 1px dashed #333;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px; margin-top: 20px;
        }
        .gallery-item {
            border: 1px solid #ddd; padding: 5px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            position: relative;
        }
        .gallery-item:hover { transform: scale(1.01); }
        .gallery-item img {
            width: 100%; height: 150px;
            object-fit: cover; display: block;
        }
        
        /* ACTION BUTTONS (NEW) */
        .action-btns {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
        }
        .btn-small {
            flex: 1;
            border: none;
            padding: 5px;
            font-size: 12px;
            color: white;
            cursor: pointer;
            margin: 0 2px;
        }
        .btn-edit { background-color: #ffc107; color: #000; }
        .btn-del { background-color: #dc3545; }

        /* MODAL STYLES (NEW) */
        .modal {
            display: none; 
            position: fixed; z-index: 999; 
            left: 0; top: 0;
            width: 100%; height: 100%; 
            background-color: rgba(0,0,0,0.5); 
            align-items: center; justify-content: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%; max-width: 400px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
        }
        .close-modal {
            position: absolute; right: 15px; top: 10px;
            font-size: 24px; cursor: pointer; color: #aaa;
        }
        .close-modal:hover { color: #000; }

        /* --- 8. FOOTER --- */
        .footer {
            background-color: #f8f8f8; padding: 15px 20px;
            border-top: 2px solid #333;
            text-align: right; font-size: 12px; color: #666;
            margin-top: auto; 
        }

        @media (max-width: 768px) {
            .main-wrapper { flex-direction: column; }
            .sidebar { width: 100%; border-right: none; border-bottom: 2px solid #333; }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <div class="logo">
                <div class="logo-inner"></div>
            </div>
            <div class="site-title">rezeki site</div>
        </div>
        
        <nav class="navigation">
            <ul class="nav-menu">
                <li><a onclick="showPage('home')" class="active" id="nav-home">Home</a></li>
                <li><a onclick="showPage('profile')" id="nav-profile">Profile</a></li>
                <li><a onclick="showPage('visi-misi')" id="nav-visi-misi">Visi Misi</a></li>
                <li><a onclick="showPage('produk')" id="nav-produk">Produk</a></li>
                <li><a onclick="showPage('kontak')" id="nav-kontak">Kontak</a></li>
                <li><a onclick="showPage('about')" id="nav-about">About Us</a></li>
            </ul>
        </nav>
        
        <div class="main-wrapper">
            <aside class="sidebar">
                <h3 class="sidebar-header" onclick="toggleSidebar('menu-artikel')">
                    Artikel <span>&#9662;</span>
                </h3>
                <ul id="menu-artikel">
                    <li><a onclick="showPage('konsep')">Konsep</a></li>
                    <li><a onclick="showPage('teknologi')">Teknologi Informasi</a></li>
                </ul>
                
                <h3 class="sidebar-header" onclick="toggleSidebar('menu-event')">
                    Event <span>&#9662;</span>
                </h3>
                <ul id="menu-event">
                    <li><a onclick="showPage('galery')">Galeri Kegiatan</a></li>
                    <li><a onclick="showPage('foto-klien')">Foto Klien</a></li>
                </ul>
                
                <h3 class="sidebar-header" onclick="toggleSidebar('menu-user')">
                    admin <span>&#9662;</span>
                </h3>
                <ul id="menu-user">
                    <li>Hi, <b><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></b></li>
                    <li><a href="logout.php" style="color:red; font-weight:bold;">Logout</a></li>
                </ul>
            </aside>
            
            <main class="content" id="content-area">
                
                <div id="page-home">
                    <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</h2>
                    <p>Selamat datang di <b>Rezeki Site</b>. Ini adalah dashboard utama untuk mengelola konten website organisasi kami.</p>
                    <div style="background:#e0f7fa; padding:15px; border-left:5px solid #00bcd4;">
                        <strong>Status:</strong> Anda telah berhasil login ke sistem.
                    </div>
                </div>
                
                <div id="page-profile" class="hidden">
                    <h2>Profile Organisasi</h2>
                    <p>Rezeki Site adalah organisasi teknologi yang berfokus pada pengembangan solusi digital yang inovatif dan relevan.</p>
                </div>
                
                <div id="page-visi-misi" class="hidden">
                    <h2>Visi dan Misi</h2>
                    <div style="background: #f0f8ff; padding: 20px; border-radius: 8px; border-left: 5px solid #4a90a4; margin-bottom: 25px;">
                        <h3 style="color: #4a90a4; margin-bottom: 10px; border-bottom:none;">Visi Kami</h3>
                        <p style="font-size: 16px; font-style: italic; color: #555;">"Menjadi pengembang solusi digital terdepan."</p>
                    </div>
                </div>
                
                <div id="page-produk" class="hidden">
                    <h2>Layanan & Produk Kami</h2>
                    <div class="product-grid">
                        <div class="product-card">
                            <span class="product-icon">💻</span>
                            <h3>Web Development</h3>
                            <p>Jasa pembuatan website responsif.</p>
                        </div>
                        <div class="product-card">
                            <span class="product-icon">🎨</span>
                            <h3>UI/UX Design</h3>
                            <p>Perancangan antarmuka aplikasi.</p>
                        </div>
                    </div>
                </div>
                
                <div id="page-kontak" class="hidden">
                    <h2>Kontak Kami</h2>
                    <div style="background-color: #f9f9f9; padding: 20px; border-radius: 8px; border:1px solid #ddd;">
                        <p><strong>WhatsApp:</strong> +62853-6193-0394</p>
                        <hr style="margin: 10px 0; border: 0; border-top: 1px solid #ddd;">
                        <p><strong>Email:</strong> renaulilumbangaol@gmail.com</p>
                    </div>
                </div>
                
                <div id="page-about" class="hidden">
                    <h2>About Us</h2>
                    <table style="width:100%; margin-top:10px; border-collapse: collapse;">
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd; width: 150px;"><strong>Nama</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">: Rezeki Nauli Lumban Gaol</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>NIM</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">: 2205181002</td></tr>
                        <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Kelas</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">: TRPL 7B</td></tr>
                    </table>
                </div>

                <div id="page-konsep" class="hidden">
                    <h2>Artikel: Konsep</h2>
                    <p>Penjelasan SDLC...</p>
                </div>
                <div id="page-teknologi" class="hidden">
                    <h2>Artikel: Teknologi</h2>
                    <p>Penjelasan PHP Native dan MySQL...</p>
                </div>

                <div id="page-galery" class="hidden">
                    <h2>Galeri Kegiatan</h2>
                    <div class="upload-box">
                        <h4>+ Upload Foto Baru</h4>
                        <form action="" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="kategori" value="galeri">
                            <input type="file" name="gambar" required style="margin-top:10px;">
                            <br><br>
                            <button type="submit" name="upload" class="btn-order" style="border:none; cursor:pointer;">Upload</button>
                        </form>
                    </div>

                    <div class="gallery-grid">
                        <?php
                        $query = mysqli_query($conn, "SELECT * FROM galeri ORDER BY id DESC");
                        if(mysqli_num_rows($query) > 0){
                            while($row = mysqli_fetch_assoc($query)){
                                ?>
                                <div class="gallery-item">
                                    <img src="uploads/<?= $row['nama_file'] ?>" alt="Foto Galeri">
                                    <div class="action-btns">
                                        <button type="button" class="btn-small btn-edit" 
                                            onclick="openEditModal('<?= $row['id'] ?>', 'galeri')">Edit</button>
                                        
                                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus foto ini?');" style="flex:1;">
                                            <input type="hidden" name="id_item" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="kategori_item" value="galeri">
                                            <button type="submit" name="hapus_gambar" class="btn-small btn-del" style="width:100%;">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p style="grid-column: 1/-1; text-align:center;">Belum ada foto.</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <div id="page-foto-klien" class="hidden">
                    <h2>Foto Klien Kami</h2>
                    <div class="upload-box">
                        <h4>+ Upload Foto Klien</h4>
                        <form action="" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="kategori" value="klien">
                            <input type="file" name="gambar" required style="margin-top:10px;">
                            <br><br>
                            <button type="submit" name="upload" class="btn-order" style="border:none; cursor:pointer;">Upload</button>
                        </form>
                    </div>

                    <div class="gallery-grid">
                        <?php
                        $query = mysqli_query($conn, "SELECT * FROM klien ORDER BY id DESC");
                        if(mysqli_num_rows($query) > 0){
                            while($row = mysqli_fetch_assoc($query)){
                                ?>
                                <div class="gallery-item">
                                    <img src="uploads/<?= $row['nama_file'] ?>" alt="Foto Klien">
                                    <div class="action-btns">
                                        <button type="button" class="btn-small btn-edit" 
                                            onclick="openEditModal('<?= $row['id'] ?>', 'klien')">Edit</button>
                                        
                                        <form method="POST" onsubmit="return confirm('Yakin ingin menghapus foto ini?');" style="flex:1;">
                                            <input type="hidden" name="id_item" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="kategori_item" value="klien">
                                            <button type="submit" name="hapus_gambar" class="btn-small btn-del" style="width:100%;">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<p style="grid-column: 1/-1; text-align:center;">Belum ada foto klien.</p>';
                        }
                        ?>
                    </div>
                </div>

            </main>
        </div>
        
        <footer class="footer">
            Design by : Rezeki Nauli Lumban Gaol 
        </footer>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h3>Ganti Foto</h3>
            <p>Pilih foto baru untuk menggantikan foto lama.</p>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" id="modal_edit_id">
                <input type="hidden" name="edit_kategori" id="modal_edit_kategori">
                
                <input type="file" name="gambar_baru" required style="margin: 15px 0; width:100%;">
                
                <button type="submit" name="edit_gambar" class="btn-order" style="border:none; cursor:pointer; width:100%;">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        // 1. Fungsi Ganti Halaman
        function showPage(pageName) {
            const pages = document.querySelectorAll('[id^="page-"]');
            pages.forEach(page => page.classList.add('hidden'));
            
            const navItems = document.querySelectorAll('.nav-menu a');
            navItems.forEach(item => item.classList.remove('active'));
            
            const sidebarLinks = document.querySelectorAll('.sidebar a');
            sidebarLinks.forEach(link => link.style.fontWeight = 'normal');

            const targetPage = document.getElementById('page-' + pageName);
            if (targetPage) {
                targetPage.classList.remove('hidden');
            }
            
            const activeNav = document.getElementById('nav-' + pageName);
            if (activeNav) {
                activeNav.classList.add('active');
            }
        }

        // 2. Fungsi Dropdown Sidebar
        function toggleSidebar(menuId) {
            const menu = document.getElementById(menuId);
            if (menu.classList.contains('collapsed')) {
                menu.classList.remove('collapsed');
            } else {
                menu.classList.add('collapsed');
            }
        }

        // 3. Fungsi Modal Edit
        function openEditModal(id, kategori) {
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('modal_edit_id').value = id;
            document.getElementById('modal_edit_kategori').value = kategori;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Tutup modal jika klik di luar area konten
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>