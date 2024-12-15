<?php
// upload.php

session_start();
require 'db.php';  // Sertakan koneksi database Anda

// Aktifkan pelaporan kesalahan untuk debugging (nonaktifkan di produksi)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cek otentikasi
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil username dari database menggunakan user_id yang disimpan dalam sesi
$user_id = $_SESSION['user_id'];
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Error: Pengguna tidak ditemukan.");
}

$username = $user['username'];  // Username dari pengunggah

// Tangani pengunggahan file untuk permintaan POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files'])) {
    $initial_directory = 'Uploads/DPX_IMAGE/';

    // Ambil tanggal, nama, dan tenant yang dikirimkan
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $tenant = isset($_POST['tenant']) ? trim($_POST['tenant']) : '';  // Pilihan tenant tunggal

    // Debugging: Log nilai tanggal, nama, tenant, dan username
    error_log("DEBUG: Tanggal = $date, Nama = $name, Tenant = $tenant, Username = $username");

    $response = array('success' => false, 'messages' => array());

    if (empty($date) || empty($name) || empty($tenant)) {
        $response['messages'][] = "Kesalahan: Tanggal, Nama, atau Tenant belum diisi.";
    } else {
        // Cek apakah setidaknya 4 file diunggah
        if (count($_FILES['files']['name']) < 4) {
            $response['messages'][] = "Kesalahan: Anda harus mengunggah setidaknya 4 gambar.";
        } else {
            // Parsing tanggal untuk menentukan folder yang benar
            $date_components = explode('-', $date);
            if (count($date_components) != 3) {
                $response['messages'][] = "Kesalahan: Format tanggal tidak valid.";
            } else {
                list($upload_year, $upload_month_num, $upload_day) = $date_components;
                // Konversi nomor bulan ke "MM_NamaBulan"
                $monthName = date('F', mktime(0, 0, 0, $upload_month_num, 10)); // Januari, Februari, dll.
                $upload_month = sprintf('%02d', $upload_month_num) . '_' . $monthName;

                // Sesuaikan path direktori berdasarkan tanggal yang dipilih
                $upload_directory = $initial_directory . "$upload_year/$upload_month/" . sprintf('%02d', $upload_day) . "/";

                // Buat direktori jika belum ada
                if (!file_exists($upload_directory)) {
                    mkdir($upload_directory, 0755, true);
                }

                $files = $_FILES['files'];
                $total_files = count($files['name']);

                for ($i = 0; $i < $total_files; $i++) {
                    // Cek kesalahan pengunggahan
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        $response['messages'][] = "Kesalahan mengunggah file " . htmlspecialchars($files['name'][$i]) . ". Kode kesalahan: " . $files['error'][$i];
                        continue;
                    }

                    // Validasi tipe file
                    $fileType = mime_content_type($files['tmp_name'][$i]);
                    if (strpos($fileType, 'image/') !== 0) {
                        $response['messages'][] = "File " . htmlspecialchars($files['name'][$i]) . " bukan file gambar yang valid.";
                        continue;
                    }

                    $fileExtension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);

                    // Ganti spasi dengan garis bawah dalam nama pelanggan
                    $name_with_underscores = str_replace(' ', '_', $name);

                    // Sanitasi nama pelanggan, tenant, dan username
                    $sanitized_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $name_with_underscores);
                    $sanitized_tenant = preg_replace("/[^a-zA-Z0-9_-]/", "", $tenant);
                    $sanitized_username = preg_replace("/[^a-zA-Z0-9_-]/", "", $username);

                    // Konstruir nama file dalam urutan baru: tenant-date-customer_name-user.ext
                    $filename = "$sanitized_tenant-$date-$sanitized_name-$sanitized_username.$fileExtension";
                    $targetFile = rtrim($upload_directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

                    // Cegah penimpaan file yang sudah ada dengan menambahkan identifikasi unik jika diperlukan
                    $unique_id = 1;
                    $base_filename = pathinfo($filename, PATHINFO_FILENAME);
                    while (file_exists($targetFile)) {
                        $filename = "{$base_filename}_{$unique_id}.$fileExtension";
                        $targetFile = rtrim($upload_directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                        $unique_id++;
                    }

                    if (move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                        $response['messages'][] = "File " . htmlspecialchars($filename) . " berhasil diunggah.";
                    } else {
                        $response['messages'][] = "Maaf, terjadi kesalahan saat mengunggah " . htmlspecialchars($files['name'][$i]) . ".";
                        $response['messages'][] = "Kode kesalahan: " . $files['error'][$i];
                    }
                }
                $response['success'] = true;
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Jika permintaan adalah GET, tampilkan form
// Tentukan folder tanggal saat ini untuk tombol "Kembali ke Direktori"
$current_year = date('Y');
$current_month_num = date('m');
$current_day = date('d');
$current_month_name = date('F', mktime(0, 0, 0, $current_month_num, 10));
$current_month = sprintf('%02d', $current_month_num) . '_' . $current_month_name;
$current_directory = 'Uploads/DPX_IMAGE/' . "$current_year/$current_month/" . sprintf('%02d', $current_day) . "/";

// Pastikan direktori saat ini ada
if (!file_exists($current_directory)) {
    mkdir($current_directory, 0755, true);
}

$last_directory = basename(rtrim($current_directory, '/\\'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unggah File</title>
    <!-- Link ke CSS eksternal -->
    <link href="styleupload.css" rel="stylesheet" type="text/css">
    <link href="style-mobile.css" rel="stylesheet" type="text/css">
    <!-- jQuery CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Compressor.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/compressorjs@1.1.1/dist/compressor.min.js"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Gaya tambahan untuk spinner */
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #09f;
            animation: spin 1s linear infinite;
            margin: 10px auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Gaya untuk pembungkus gambar dan tombol hapus */
        .img-wrapper {
            position: relative;
            display: inline-block;
            margin: 5px;
        }

        .remove-img {
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            cursor: pointer;
            font-size: 14px;
        }

        /* Gaya untuk tombol yang dinonaktifkan */
        .disabled-button {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Gaya untuk notifikasi */
        .notification {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            display: none;
        }

        .notification.success {
            background-color: #d4edda;
            color: #155724;
        }

        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Gaya untuk progress bar */
        .individual-progress-bar-container {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar {
            height: 20px;
            background-color: #4CAF50;
            width: 0%;
            text-align: center;
            color: white;
            line-height: 20px;
        }

        /* Gaya untuk indikator loading */
        .hidden {
            display: none;
        }

        /* Gaya responsif */
        @media (max-width: 600px) {
            .image-preview-container img {
                width: 80px;
                height: 80px;
            }
        }

        /* Gaya untuk menyembunyikan label dan input tanggal */
        .hidden-date {
            display: none;
        }
    </style>
</head>

<body>
<div class="upload-container">
    <div class="welcome-message">
        <span>Selamat Datang, <?= htmlspecialchars($username); ?></span>
        <div>
            <!-- Tombol Logout dengan Ikon -->
            <a href="logout.php" class="logout-button" aria-label="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
            <!-- Tombol Kembali dengan Ikon -->
            <a href="index.php?file=<?= urlencode($current_directory) ?>" class="back-button" aria-label="Kembali ke Direktori">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>

    <h1>Unggah File ke <?= htmlspecialchars($last_directory) ?></h1>
    <form id="uploadForm" method="post" enctype="multipart/form-data">
        <label for="fileToUpload">Ambil foto untuk diunggah:</label>
        <!-- Input File Tersembunyi -->
        <input type="file" name="files[]" id="fileToUpload" accept="image/jpeg" capture="camera" multiple style="display: none;">

        <!-- Tombol Tambah Gambar -->
        <button type="button" class="add-image-button" id="addImageButton">Tambah Gambar</button>

        <!-- Kontainer Pratinjau Gambar -->
        <div id="image-preview-container" class="image-preview-container" style="display: none; margin-top: 10px;">
            <!-- Pratinjau beberapa gambar akan ditambahkan di sini -->
        </div>

        <!-- Kontainer yang Disembunyikan untuk Tanggal -->
        <div class="hidden-date">
            <label for="date">Pilih Tanggal:</label>
            <input type="date" id="date" name="date" required readonly>
        </div>

        <label for="name">Nama Pelanggan:</label>
        <input type="text" id="name" name="name" required>

        <!-- Pemilihan Tenant: Dropdown Tunggal -->
        <label for="tenant">Tenant:</label>
        <select id="tenant" name="tenant" required>
            <option value="">Pilih tenant</option>
            <!-- Opsi akan diisi melalui JavaScript -->
        </select>

        <!-- Tombol Submit dengan ID -->
        <input type="submit" value="Kirim Foto" name="submit" id="submitButton">
    </form>
    <div id="notification" class="notification"></div>
    <div id="progress-bar-container" class="individual-progress-bar-container" style="display: none;">
        <div class="progress-bar" id="progress-bar">0%</div>
    </div>
    <div id="loading" class="hidden">Mengunggah...</div>
</div>

<script>
$(document).ready(function() {
    // Variabel
    var selectedFiles = []; // Array untuk menyimpan semua file yang dipilih
    var MAX_FILES = 20; // Jumlah maksimum file yang diizinkan
    var MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB per file
    var username = '<?= htmlspecialchars($username); ?>'; // Ambil username dari PHP
    var year = new Date().getFullYear();

    // Fungsi untuk mengisi dropdown tenant
    function populateTenants() {
        $.ajax({
            url: 'fetch_tenants.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                var tenantSelect = $('#tenant');
                tenantSelect.empty();
                tenantSelect.append('<option value="">Pilih tenant</option>');
                if (response.length > 0) {
                    response.forEach(function(tenant) {
                        tenantSelect.append('<option value="' + tenant.name + '">' + tenant.name + '</option>');
                    });
                } else {
                    tenantSelect.append('<option value="">Tidak ada tenant tersedia</option>');
                }
            },
            error: function() {
                console.error('Terjadi kesalahan saat mengambil data tenant.');
                var tenantSelect = $('#tenant');
                tenantSelect.empty();
                tenantSelect.append('<option value="">Terjadi kesalahan memuat tenant</option>');
            }
        });
    }

    // Panggil populateTenants saat halaman dimuat
    populateTenants();

    // Fungsi untuk mengatur tanggal hari ini
    function setTodayDate() {
        var today = new Date().toISOString().split('T')[0];
        $('#date').val(today);
    }

    // Atur tanggal hari ini saat halaman dimuat
    setTodayDate();

    // Fungsi untuk menampilkan notifikasi
    function showNotification(message, type) {
        var notification = $('#notification');
        if (type === "success") {
            notification.removeClass('error').addClass('success');
        } else if (type === "error") {
            notification.removeClass('success').addClass('error');
        }
        notification.html(message).slideDown();
        setTimeout(function() {
            notification.slideUp(); // Sembunyikan notifikasi setelah 5 detik
        }, 5000);
    }

    // Fungsi untuk memperbarui pratinjau gambar dengan indikator loading
    function updatePreviews() {
        var previewContainer = $('#image-preview-container');
        previewContainer.empty(); // Bersihkan pratinjau yang ada

        if (selectedFiles.length > 0) {
            selectedFiles.forEach(function(file, index) {
                var imgWrapper = $('<div class="img-wrapper"></div>');
                var spinner = $('<div class="spinner"></div>'); // Spinner loading
                imgWrapper.append(spinner);
                previewContainer.append(imgWrapper);

                var reader = new FileReader();

                reader.onload = function(e) {
                    var img = $('<img>')
                        .attr('src', e.target.result)
                        .attr('alt', 'Pratinjau Gambar')
                        .css({
                            'width': '100px',
                            'height': '100px',
                            'object-fit': 'cover',
                            'border': '1px solid #ccc',
                            'border-radius': '4px'
                        })
                        .on('error', function() {
                            imgWrapper.find('.spinner').remove(); // Hapus spinner
                            showNotification("Gagal memuat pratinjau untuk " + file.name, "error");
                        });

                    var removeBtn = $('<span class="remove-img">&times;</span>');
                    imgWrapper.append(img).append(removeBtn);
                    imgWrapper.find('.spinner').remove(); // Hapus spinner setelah gambar dimuat
                }

                reader.onerror = function() {
                    imgWrapper.find('.spinner').remove(); // Hapus spinner
                    showNotification("Terjadi kesalahan saat membaca file " + file.name, "error");
                }

                reader.readAsDataURL(file);
            });
            previewContainer.show();
        } else {
            previewContainer.hide();
        }
    }

    // Tangani klik tombol Tambah Gambar
    $('#addImageButton').on('click', function() {
        $('#fileToUpload').click(); // Trigger input file tersembunyi
    });

    // Fungsi untuk mengompresi gambar menggunakan Compressor.js dengan kualitas minimal loss
    function compressImage(file, maxSizeKB, callback) {
        new Compressor(file, {
            quality: 0.95, // Level kualitas tinggi
            maxWidth: 1920, // Lebar maksimum (sesuaikan sesuai kebutuhan)
            maxHeight: 1080, // Tinggi maksimum (sesuaikan sesuai kebutuhan)
            convertSize: maxSizeKB * 1024, // Konversi hanya jika ukuran > maxSizeKB
            success(result) {
                callback(result);
            },
            error(err) {
                console.error('Kesalahan kompresi:', err.message);
                callback(file); // Kembalikan file asli jika kompresi gagal
            },
        });
    }

    // Tangani perubahan input file dengan kompresi wajib
    $("#fileToUpload").on('change', function() {
        var files = this.files;
        if (files.length > 0) {
            // Cek apakah menambahkan file ini melebihi batas maksimum
            if (selectedFiles.length + files.length > MAX_FILES) {
                showNotification("Anda dapat mengunggah maksimal " + MAX_FILES + " gambar.", "error");
                return;
            }

            Array.from(files).forEach(function(file) {
                // Validasi tipe file
                if (!file.type.startsWith('image/jpeg')) {
                    showNotification(file.name + " bukan file JPEG yang valid.", "error");
                    return;
                }

                // Validasi ukuran file
                if (file.size > MAX_FILE_SIZE) {
                    showNotification(file.name + " melebihi batas ukuran 5MB.", "error");
                    return;
                }

                // Kompres gambar menggunakan Compressor.js
                compressImage(file, 50, function(compressedFile) {
                    // Cek apakah file sudah ada dalam selectedFiles
                    var exists = selectedFiles.some(function(f) {
                        return f.name === compressedFile.name && f.size === compressedFile.size && f.lastModified === compressedFile.lastModified;
                    });
                    if (!exists) {
                        selectedFiles.push(compressedFile);
                        updatePreviews();
                    }
                });
            });

            // Reset nilai input file untuk memungkinkan memilih file yang sama lagi jika diperlukan
            $(this).val('');
        }
    });

    // Tangani pengiriman form untuk pengunggahan file
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();  // Cegah pengiriman form default
        console.log("Tombol submit diklik");  // Debugging

        // Nonaktifkan tombol submit untuk mencegah klik ganda
        $('#submitButton').prop('disabled', true).val('Mengunggah...').addClass('disabled-button').attr('aria-disabled', 'true');

        // Ambil nilai form secara langsung
        var date = $('#date').val();
        var name = $('#name').val();
        var tenant = $('#tenant').val();

        console.log("Nilai Form:", { date: date, name: name, tenant: tenant });  // Debugging

        // Validasi kustom
        if (!date || !name || !tenant) {
            showNotification("Silakan isi semua bidang.", "error");
            // Aktifkan kembali tombol submit
            $('#submitButton').prop('disabled', false).val('Kirim Foto').removeClass('disabled-button').attr('aria-disabled', 'false');
            return;
        }

        if (selectedFiles.length < 4) {  // Minimum 4 gambar
            // Tampilkan notifikasi daripada alert
            showNotification("Silakan tambahkan setidaknya 4 gambar untuk diunggah.", "error");
            // Aktifkan kembali tombol submit
            $('#submitButton').prop('disabled', false).val('Kirim Foto').removeClass('disabled-button').attr('aria-disabled', 'false');
            return;
        }

        // Buat objek FormData
        var formData = new FormData();
        formData.append('date', date);
        formData.append('name', name);
        formData.append('tenant', tenant);  // Pilihan tenant tunggal

        selectedFiles.forEach(function(file, index) {
            // Sanitasi nama file untuk mencegah masalah keamanan
            var sanitized_name = name.replace(/[^a-zA-Z0-9_-]/g, '');
            var sanitized_tenant = tenant.replace(/[^a-zA-Z0-9_-]/g, '');
            var sanitized_username = username.replace(/[^a-zA-Z0-9_-]/g, '');
            var fileExtension = file.name.split('.').pop();
            var baseName = file.name.split('.').slice(0, -1).join('.');
            var newFilename = `${sanitized_tenant}-${date}-${sanitized_name}-${sanitized_username}-${index + 1}.${fileExtension}`;
            
            formData.append('files[]', file, newFilename);
        });

        console.log("FormData Disiapkan");  // Debugging

        // Permintaan AJAX ke upload.php
        $.ajax({
            url: 'upload.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('#progress-bar').css('width', percentComplete + '%').text(Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            beforeSend: function() {
                $('#loading').removeClass('hidden');
                $('#progress-bar-container').removeClass('hidden');
                $('#progress-bar').css('width', '0%').text('0%');
                $('#notification').hide();
                console.log("Permintaan AJAX Dikirim");  // Debugging
            },
            success: function(response) {
                console.log("AJAX Berhasil:", response);  // Debugging
                $('#loading').addClass('hidden');
                var notification = $('#notification');
                if (response.success) {
                    showNotification(response.messages.join("<br>"), "success");
                    // Reset form dan selectedFiles
                    $('#uploadForm')[0].reset();
                    setTodayDate(); // Atur otomatis tanggal setelah reset
                    selectedFiles = [];
                    $('#image-preview-container').empty().hide();
                    // Isi ulang tenant jika daftar telah berubah
                    populateTenants();
                } else {
                    showNotification(response.messages.join("<br>"), "error");
                }
                setTimeout(function() {
                    notification.slideUp(); // Sembunyikan notifikasi setelah 5 detik
                    $('#progress-bar-container').hide();
                    // Aktifkan kembali tombol submit
                    $('#submitButton').prop('disabled', false).val('Kirim Foto').removeClass('disabled-button').attr('aria-disabled', 'false');
                }, 5000);
            },
            error: function(xhr, status, error) {
                console.error("Kesalahan AJAX:", error);  // Debugging
                $('#loading').addClass('hidden');
                var notification = $('#notification');
                showNotification('Terjadi kesalahan: ' + error, "error");
                setTimeout(function() {
                    notification.slideUp(); // Sembunyikan setelah 5 detik
                    $('#progress-bar-container').hide();
                    // Aktifkan kembali tombol submit
                    $('#submitButton').prop('disabled', false).val('Kirim Foto').removeClass('disabled-button').attr('aria-disabled', 'false');
                }, 5000);
            }
        });
    });

    // Tangani penghapusan gambar sebelum diunggah
    $(document).on('click', '.remove-img', function() {
        var index = $(this).parent().index();
        console.log("Menghapus gambar pada indeks:", index);  // Debugging
        selectedFiles.splice(index, 1);
        $(this).parent().remove();
        if (selectedFiles.length === 0) {
            $('#image-preview-container').hide();
        }
    });
});
</script>

</body>
</html>
