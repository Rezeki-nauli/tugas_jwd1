<?php
session_start();

// PERBAIKAN 1: Gunakan '../' untuk memanggil file di folder utama (luar folder user)
require '../admin/koneksi.php';

// 2. LOGIKA UPLOAD GAMBAR
if (isset($_POST["upload"])) {
    $kategori = $_POST["kategori"]; // 'galeri' atau 'klien'
    
    // Ambil data file
    $namaFile = $_FILES["gambar"]["name"];
    $tmpName  = $_FILES["gambar"]["tmp_name"];
    $error    = $_FILES["gambar"]["error"];

    // Cek error upload
    if ($error === 4) {
        echo "<script>alert('Pilih gambar terlebih dahulu!');</script>";
    } else {
        // Cek ekstensi file
        $ekstensiValid = ['jpg', 'jpeg', 'png'];
        $ekstensi      = explode('.', $namaFile);
        $ekstensi      = strtolower(end($ekstensi));

        if (!in_array($ekstensi, $ekstensiValid)) {
            echo "<script>alert('File harus berupa gambar (JPG/PNG)!');</script>";
        } else {
            // Generate nama unik
            $namaFileBaru = uniqid() . '.' . $ekstensi;

            // PERBAIKAN 2: Simpan gambar ke folder '../uploads/' (folder uploads di root project)
            // Agar gambar tidak tersimpan di dalam folder 'user'
            move_uploaded_file($tmpName, '../uploads/' . $namaFileBaru);

            // Simpan ke Database sesuai kategori
            if ($kategori == "galeri") {
                mysqli_query($conn, "INSERT INTO galeri (nama_file) VALUES ('$namaFileBaru')");
            } else if ($kategori == "klien") {
                mysqli_query($conn, "INSERT INTO klien (nama_file) VALUES ('$namaFileBaru')");
            }

            // PERBAIKAN 3: Redirect kembali ke halaman ini (dashboard.php) bukan index.php
            echo "<script>alert('Berhasil upload foto!'); window.location='dashboard.php';</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Rezeki Site</title>
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

        /* --- 5. MAIN WRAPPER (SIDEBAR + CONTENT) --- */
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
        
        /* Dropdown/Accordion Animation */
        .sidebar ul { 
            list-style: none; 
            max-height: 500px; /* Max height for transition */
            overflow: hidden;
            transition: max-height 0.4s ease-out;
        }
        .sidebar ul.collapsed {
            max-height: 0;
        }

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

        /* --- 6. PAGE SPECIFIC: PRODUK & ARTIKEL --- */
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

        /* --- 7. PAGE SPECIFIC: GALERI & UPLOAD --- */
        .upload-box {
            background-color: #e9ecef; padding: 15px;
            margin-bottom: 20px; border-radius: 5px;
            border: 1px dashed #333;
        }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px; margin-top: 20px;
        }
        .gallery-item {
            border: 1px solid #ddd; padding: 5px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            cursor: pointer; /* Menunjukkan bisa diklik */
        }
        .gallery-item:hover { transform: scale(1.02); }
        .gallery-item img {
            width: 100%; height: 150px;
            object-fit: cover; display: block;
        }

        /* --- STYLE BARU: MODAL (LIGHTBOX) --- */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 9999; /* Sit on top */
            padding-top: 50px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.9); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            object-fit: contain;
            border: 2px solid white;
            border-radius: 5px;
            animation-name: zoom;
            animation-duration: 0.6s;
        }

        @keyframes zoom {
            from {transform:scale(0)} 
            to {transform:scale(1)}
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        /* --- 8. FOOTER & RESPONSIVE --- */
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
                
               
            </aside>
            
            <main class="content" id="content-area">
                
                <div id="page-home">
                    <h2>Selamat Datang di Rezeki Site</h2>
                    <p>Terima kasih telah mengunjungi website resmi kami. <b>Rezeki Site</b> hadir sebagai platform digital yang berdedikasi untuk memberikan informasi dan solusi teknologi terkini.</p>
                    <p>Silakan jelajahi berbagai layanan, profil, dan dokumentasi kegiatan kami melalui menu navigasi di atas atau sidebar di samping kiri.</p>
                    <div style="background:#e0f7fa; padding:15px; border-left:5px solid #00bcd4; margin-top:20px;">
                        <p style="margin-bottom:0;"><strong>Info Terbaru:</strong> Lihat dokumentasi kegiatan terbaru kami di menu Galeri.</p>
                    </div>
                </div>
                
                <div id="page-profile" class="hidden">
                    <h2>Profile Organisasi</h2>
                    <p>Rezeki Site adalah organisasi teknologi yang berfokus pada pengembangan solusi digital yang inovatif dan relevan.</p>
                    <p>Berdiri sejak tahun 2024, kami telah membantu berbagai UMKM untuk melakukan transformasi digital melalui website yang handal dan desain yang menarik.</p>
                </div>
                
                <div id="page-visi-misi" class="hidden">
                    <h2>Visi dan Misi</h2>
                    <div style="background: #f0f8ff; padding: 20px; border-radius: 8px; border-left: 5px solid #4a90a4; margin-bottom: 25px;">
                        <h3 style="color: #4a90a4; margin-bottom: 10px; border-bottom:none;">Visi Kami</h3>
                        <p style="font-size: 16px; font-style: italic; color: #555;">
                            "Menjadi pengembang solusi digital terdepan yang mampu menjembatani kebutuhan teknologi masyarakat dengan inovasi yang kreatif, efektif, dan berkelanjutan."
                        </p>
                    </div>
                    <div>
                        <h3 style="color: #333; margin-bottom: 15px;">Misi Kami</h3>
                        <ul style="list-style: none; margin-left: 0; padding-left: 0;">
                            <li style="margin-bottom: 15px; display: flex; align-items: start;">
                                <span style="color: #4a90a4; font-weight: bold; margin-right: 10px;">1.</span>
                                <span><strong>Inovasi Tanpa Henti:</strong> Mengembangkan produk teknologi yang selalu relevan.</span>
                            </li>
                            <li style="margin-bottom: 15px; display: flex; align-items: start;">
                                <span style="color: #4a90a4; font-weight: bold; margin-right: 10px;">2.</span>
                                <span><strong>Kepuasan Klien:</strong> Memberikan pelayanan terbaik dan solusi tepat sasaran.</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div id="page-produk" class="hidden">
                    <h2>Layanan & Produk Kami</h2>
                    <p>Kami menawarkan solusi digital terbaik untuk membantu bisnis Anda berkembang.</p>
                    <div class="product-grid">
                        <div class="product-card">
                            <span class="product-icon">💻</span>
                            <h3>Web Development</h3>
                            <p>Jasa pembuatan website responsif, cepat, dan SEO friendly untuk bisnis maupun personal.</p>
                            <a onclick="showPage('kontak')" class="btn-order">Pesan Sekarang</a>
                        </div>
                        <div class="product-card">
                            <span class="product-icon">🎨</span>
                            <h3>UI/UX Design</h3>
                            <p>Perancangan antarmuka aplikasi yang user-friendly dan estetis untuk meningkatkan pengalaman pengguna.</p>
                            <a onclick="showPage('kontak')" class="btn-order">Pesan Sekarang</a>
                        </div>
                        <div class="product-card">
                            <span class="product-icon">📱</span>
                            <h3>Mobile Apps</h3>
                            <p>Pengembangan aplikasi berbasis Android dan iOS yang stabil dan berkinerja tinggi.</p>
                            <a onclick="showPage('kontak')" class="btn-order">Pesan Sekarang</a>
                        </div>
                    </div>
                </div>
                
                <div id="page-kontak" class="hidden">
                    <h2>Kontak Kami</h2>
                    <div style="background-color: #f9f9f9; padding: 20px; border-radius: 8px; border:1px solid #ddd;">
                        <p><strong>WhatsApp:</strong> <br> +62853-6193-0394</p>
                        <hr style="margin: 10px 0; border: 0; border-top: 1px solid #ddd;">
                        <p><strong>Email:</strong> <br> renaulilumbangaol@gmail.com</p>
                        <hr style="margin: 10px 0; border: 0; border-top: 1px solid #ddd;">
                        <p><strong>Alamat:</strong> <br> Gg. Saudara No 29 (Moan Kost), Medan</p>
                    </div>
                </div>
                
                <div id="page-about" class="hidden">
                    <h2>About Us</h2>
                    <p>Selamat datang di <strong>Rezeki Site</strong>. Website ini merupakan platform digital yang dirancang sebagai bagian dari proyek <em>Junior Web Developer</em> (JWD).</p>
                    
                    <h3 style="margin-top:20px; font-size:18px;">Pengembang</h3>
                    <table style="width:100%; margin-top:10px; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd; width: 150px;"><strong>Nama</strong></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">: Rezeki Nauli Lumban Gaol</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>NIM</strong></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">: 2205181002</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Kelas</strong></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">: TRPL 7B</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Kampus</strong></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">: Politeknik Negeri Medan</td>
                        </tr>
                    </table>
                </div>

                <div id="page-konsep" class="hidden">
                    <h2>Artikel: Konsep Pengembangan</h2>
                    <div class="article-card" style="text-align: left;">
                        <h3>Siklus Hidup Pengembangan Sistem (SDLC)</h3>
                        <p style="margin-top:10px;">
                            Dalam mengembangkan <b>Rezeki Site</b>, kami menerapkan konsep SDLC (Software Development Life Cycle). 
                            Tahapan ini sangat penting untuk memastikan sistem yang dibangun sesuai dengan kebutuhan pengguna.
                        </p>
                        <ul>
                            <li><b>Planning:</b> Merencanakan fitur dan struktur database.</li>
                            <li><b>Analysis:</b> Menganalisis kebutuhan user interface dan user experience.</li>
                            <li><b>Design:</b> Membuat mockup layout dan skema warna.</li>
                            <li><b>Implementation:</b> Coding menggunakan PHP Native dan MySQL.</li>
                            <li><b>Maintenance:</b> Pemeliharaan dan update konten.</li>
                        </ul>
                    </div>
                </div>

                <div id="page-teknologi" class="hidden">
                    <h2>Artikel: Teknologi Informasi</h2>
                    <p>Teknologi yang digunakan dalam pembangunan website ini meliputi:</p>
                    <div class="product-grid">
                        <div class="article-card">
                            <h3>PHP Native</h3>
                            <p>Bahasa pemrograman server-side yang digunakan untuk menangani logika backend tanpa framework tambahan.</p>
                        </div>
                        <div class="article-card">
                            <h3>MySQL</h3>
                            <p>Sistem manajemen basis data relasional (RDBMS) untuk menyimpan data user, galeri, dan konten lainnya.</p>
                        </div>
                        <div class="article-card">
                            <h3>HTML5 & CSS3</h3>
                            <p>Digunakan untuk membangun struktur halaman dan memberikan styling agar tampilan menarik dan responsif.</p>
                        </div>
                    </div>
                </div>

                <div id="page-galery" class="hidden">
                    <h2>Galery Kegiatan</h2>
                    <p>Dokumentasi kegiatan organisasi dan event yang telah berlangsung (Klik gambar untuk memperbesar).</p>
                    
                    <div class="gallery-grid">
                        <?php
                        $query = mysqli_query($conn, "SELECT * FROM galeri ORDER BY id DESC");
                        if(mysqli_num_rows($query) > 0){
                            while($row = mysqli_fetch_assoc($query)){
                                echo '<div class="gallery-item">';
                                // PERBAIKAN 4: Tambahkan '../' pada src agar mengakses folder uploads di root
                                echo '<img src="../uploads/'.$row['nama_file'].'" alt="Foto Galeri" onclick="viewImage(this.src)">';
                                echo '</div>';
                            }
                        } else {
                            echo '<p style="grid-column: 1/-1; text-align:center;">Belum ada foto.</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <div id="page-foto-klien" class="hidden">
                    <h2>Foto Klien Kami</h2>
                    <p>Klien yang telah mempercayakan proyek digital mereka kepada kami (Klik gambar untuk memperbesar).</p>

                    <div class="gallery-grid">
                        <?php
                        $query = mysqli_query($conn, "SELECT * FROM klien ORDER BY id DESC");
                        if(mysqli_num_rows($query) > 0){
                            while($row = mysqli_fetch_assoc($query)){
                                echo '<div class="gallery-item">';
                                // PERBAIKAN 5: Tambahkan '../' pada src agar mengakses folder uploads di root
                                echo '<img src="../uploads/'.$row['nama_file'].'" alt="Foto Klien" onclick="viewImage(this.src)">';
                                echo '</div>';
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

    <div id="imageModal" class="modal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="img-full">
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

        // 3. FUNGSI UNTUK MEMBUKA MODAL GAMBAR
        function viewImage(src) {
            var modal = document.getElementById("imageModal");
            var modalImg = document.getElementById("img-full");
            modal.style.display = "flex"; // Gunakan flex agar gambar di tengah
            modalImg.src = src;
        }

        // 4. FUNGSI UNTUK MENUTUP MODAL
        function closeModal() {
            var modal = document.getElementById("imageModal");
            modal.style.display = "none";
        }

        // Tutup modal jika user klik di luar gambar (di area hitam)
        window.onclick = function(event) {
            var modal = document.getElementById("imageModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>