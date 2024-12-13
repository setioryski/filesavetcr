<?php
// upload.php

session_start();
require 'db.php';  // Sertakan koneksi database Anda

// Aktifkan pelaporan kesalahan untuk debugging (nonaktifkan di produksi)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cek autentikasi
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil username dari database menggunakan user_id yang disimpan di session
$user_id = $_SESSION['user_id'];
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Error: User tidak ditemukan.");
}

$username = $user['username'];  // Username dari orang yang mengunggah file

// Tangani unggahan file untuk permintaan POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files'])) {
    $initial_directory = 'Uploads/DPX_IMAGE/';

    // Ambil tanggal yang dikirimkan
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $tenant = isset($_POST['tenant']) ? trim($_POST['tenant']) : '';  // Pilihan tenant tunggal

    // Debugging: Log nilai tanggal, nama, tenant, dan username
    error_log("DEBUG: Date = $date, Name = $name, Tenant = $tenant, Username = $username");

    $response = array('success' => false, 'messages' => array());

    if (empty($date) || empty($name) || empty($tenant)) {
        $response['messages'][] = "Error: Tanggal, Nama, atau Tenant tidak diisi.";
    } else {
        // Cek apakah setidaknya 4 file diunggah
        if (count($_FILES['files']['name']) < 4) {
            $response['messages'][] = "Error: Anda harus mengunggah setidaknya 4 gambar.";
        } else {
            // Parse tanggal untuk menentukan folder yang benar
            $date_components = explode('-', $date);
            if (count($date_components) != 3) {
                $response['messages'][] = "Error: Format tanggal tidak valid.";
            } else {
                list($upload_year, $upload_month_num, $upload_day) = $date_components;
                // Konversi nomor bulan ke "MM_NamaBulan"
                $monthName = date('F', mktime(0, 0, 0, $upload_month_num, 10)); // Januari, Februari, dst.
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
                    // Cek error unggahan
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        $response['messages'][] = "Error mengunggah file " . htmlspecialchars($files['name'][$i]) . ". Kode error: " . $files['error'][$i];
                        continue;
                    }

                    // Validasi tipe file
                    $fileType = mime_content_type($files['tmp_name'][$i]);
                    if (strpos($fileType, 'image/') !== 0) {
                        $response['messages'][] = "File " . htmlspecialchars($files['name'][$i]) . " bukan file gambar yang valid.";
                        continue;
                    }

                    $fileExtension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                    $sanitized_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $name);
                    $sanitized_tenant = preg_replace("/[^a-zA-Z0-9_-]/", "", $tenant);
                    $sanitized_username = preg_replace("/[^a-zA-Z0-9_-]/", "", $username);
                    $filename = "$date-$sanitized_name-$sanitized_tenant-$sanitized_username.$fileExtension";
                    $targetFile = rtrim($upload_directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

                    // Cegah penimpaan file yang sudah ada dengan menambahkan identifier unik jika perlu
                    $unique_id = 1;
                    $base_filename = pathinfo($filename, PATHINFO_FILENAME);
                    while (file_exists($targetFile)) {
                        $filename = "{$base_filename}_{$unique_id}.$fileExtension";
                        $targetFile = rtrim($upload_directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                        $unique_id++;
                    }

                    if (move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                        $response['messages'][] = "File " . htmlspecialchars($filename) . " telah berhasil diunggah.";
                    } else {
                        $response['messages'][] = "Maaf, terjadi kesalahan saat mengunggah " . htmlspecialchars($files['name'][$i]) . ".";
                        $response['messages'][] = "Kode error: " . $files['error'][$i];
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

// Jika permintaan GET, tampilkan form
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
    <title>Upload File</title>
    <!-- Link ke CSS eksternal -->
    <link href="styleupload.css" rel="stylesheet" type="text/css">
    <link href="style-mobile.css" rel="stylesheet" type="text/css">
    <!-- jQuery CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Compressor.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/compressorjs@1.1.1/dist/compressor.min.js"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
<div class="upload-container">
<div class="welcome-message">
    <span>Welcome, <?= htmlspecialchars($username); ?></span>
    <div>
        <!-- Logout Button dengan Ikon -->
        <a href="logout.php" class="logout-button" aria-label="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
        <!-- Back Button dengan Ikon -->
        <a href="index.php?file=<?= urlencode($current_directory) ?>" class="back-button" aria-label="Back to Directory">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>
</div>

    <h1>Upload Files to <?= htmlspecialchars($last_directory) ?></h1>
    <form id="uploadForm" method="post" enctype="multipart/form-data">
        <label for="fileToUpload">Take pictures to upload:</label>
        <!-- Hidden File Input -->
        <input type="file" name="files[]" id="fileToUpload" accept="image/jpeg" capture="camera" multiple style="display: none;">

        <!-- Add Image Button -->
        <button type="button" class="add-image-button" id="addImageButton">Add Image</button>

        <!-- Image Preview Container -->
        <div id="image-preview-container" class="image-preview-container" style="display: none; margin-top: 10px;">
            <!-- Multiple image previews will be appended here -->
        </div>

        <label for="date">Select Date:</label>
        <input type="date" id="date" name="date" required readonly>

        <label for="name">Customer Name:</label>
        <input type="text" id="name" name="name" required>

        <!-- Tenant Selection: Single Dropdown -->
        <label for="tenant">Tenant:</label>
        <select id="tenant" name="tenant" required>
            <option value="">Select a tenant</option>
            <!-- Options will be populated via JavaScript -->
        </select>

        <input type="submit" value="Submit Photos" name="submit">
    </form>
    <div id="notification" class="notification"></div>
    <div id="progress-bar-container" class="individual-progress-bar-container" style="display: none;">
        <div class="progress-bar" id="progress-bar">0%</div>
    </div>
    <div id="loading" class="hidden">Uploading...</div>
</div>

<script>
$(document).ready(function() {
    // Variabel
    var selectedFiles = []; // Array untuk menyimpan semua file yang dipilih
    var MAX_FILES = 20; // Maksimum jumlah file yang diizinkan
    var MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB per file
    var username = '<?= htmlspecialchars($username); ?>'; // Mengambil username dari PHP
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
                tenantSelect.append('<option value="">Select a tenant</option>');
                if (response.length > 0) {
                    response.forEach(function(tenant) {
                        tenantSelect.append('<option value="' + tenant.name + '">' + tenant.name + '</option>');
                    });
                } else {
                    tenantSelect.append('<option value="">No tenants available</option>');
                }
            },
            error: function() {
                console.error('Error fetching tenants.');
                var tenantSelect = $('#tenant');
                tenantSelect.empty();
                tenantSelect.append('<option value="">Error loading tenants</option>');
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

    // Fungsi untuk memperbarui preview gambar dengan indikator loading
    function updatePreviews() {
        var previewContainer = $('#image-preview-container');
        previewContainer.empty(); // Bersihkan preview yang ada

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
                        .attr('alt', 'Image Preview')
                        .css({
                            'width': '100px',
                            'height': '100px',
                            'object-fit': 'cover',
                            'border': '1px solid #ccc',
                            'border-radius': '4px'
                        })
                        .on('error', function() {
                            imgWrapper.find('.spinner').remove(); // Hapus spinner
                            showNotification("Gagal memuat preview untuk " + file.name, "error");
                        });

                    var removeBtn = $('<span class="remove-img">&times;</span>');
                    imgWrapper.append(img).append(removeBtn);
                    imgWrapper.find('.spinner').remove(); // Hapus spinner setelah gambar dimuat
                }

                reader.onerror = function() {
                    imgWrapper.find('.spinner').remove(); // Hapus spinner
                    showNotification("Error membaca file " + file.name, "error");
                }

                reader.readAsDataURL(file);
            });
            previewContainer.show();
        } else {
            previewContainer.hide();
        }
    }

    // Handle klik tombol Add Image
    $('#addImageButton').on('click', function() {
        $('#fileToUpload').click(); // Trigger input file tersembunyi
    });

    // Fungsi untuk mengompres gambar menggunakan Compressor.js dengan kualitas minimal loss
    function compressImage(file, maxSizeKB, callback) {
        new Compressor(file, {
            quality: 0.95, // Tingkat kualitas tinggi
            maxWidth: 1920, // Maksimal lebar (sesuaikan dengan kebutuhan)
            maxHeight: 1080, // Maksimal tinggi (sesuaikan dengan kebutuhan)
            convertSize: maxSizeKB * 1024, // Mengonversi hanya jika ukuran lebih besar dari maxSizeKB
            success(result) {
                callback(result);
            },
            error(err) {
                console.error('Compression error:', err.message);
                callback(file); // Kembalikan file asli jika kompresi gagal
            },
        });
    }

    // Handle perubahan input file dengan kompresi wajib
    $("#fileToUpload").on('change', function() {
        var files = this.files;
        if (files.length > 0) {
            // Cek apakah menambahkan file ini melebihi batas maksimum
            if (selectedFiles.length + files.length > MAX_FILES) {
                showNotification("Anda dapat mengunggah maksimum " + MAX_FILES + " gambar.", "error");
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

                // Kompresi gambar dengan Compressor.js
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

    // Handle pengiriman form untuk unggah file
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();  // Mencegah pengiriman form default
        console.log("Tombol Submit diklik");  // Debugging

        // Tangkap nilai field form segera
        var date = $('#date').val();
        var name = $('#name').val();
        var tenant = $('#tenant').val();

        console.log("Nilai Form:", { date: date, name: name, tenant: tenant });  // Debugging

        // Validasi kustom
        if (!date || !name || !tenant) {
            showNotification("Harap isi semua bidang.", "error");
            return;
        }

        if (selectedFiles.length < 4) {  // Minimum 4 gambar
            // Tampilkan notifikasi alih-alih alert
            showNotification("Harap tambahkan setidaknya 4 gambar untuk diunggah.", "error");
            return;
        }

        // Buat objek FormData
        var formData = new FormData();
        formData.append('date', date);
        formData.append('name', name);
        formData.append('tenant', tenant);  // Tenant tunggal

        selectedFiles.forEach(function(file, index) {
            // Sanitasi nama file untuk mencegah masalah keamanan
            var sanitized_name = name.replace(/[^a-zA-Z0-9_-]/g, '');
            var sanitized_tenant = tenant.replace(/[^a-zA-Z0-9_-]/g, '');
            var sanitized_username = username.replace(/[^a-zA-Z0-9_-]/g, '');
            var fileExtension = file.name.split('.').pop();
            var baseName = file.name.split('.').slice(0, -1).join('.');
            var newFilename = `${date}-${sanitized_name}-${sanitized_tenant}-${sanitized_username}-${index + 1}.${fileExtension}`;
            
            formData.append('files[]', file, newFilename);
        });

        console.log("FormData Disiapkan");  // Debugging

        // AJAX request ke upload.php
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
                console.log("AJAX Request Dikirim");  // Debugging
            },
            success: function(response) {
                console.log("AJAX Success:", response);  // Debugging
                $('#loading').addClass('hidden');
                var notification = $('#notification');
                if (response.success) {
                    showNotification(response.messages.join("<br>"), "success");
                    // Reset form dan selectedFiles
                    $('#uploadForm')[0].reset();
                    setTodayDate(); // Otomatis mengisi tanggal setelah reset
                    selectedFiles = [];
                    $('#image-preview-container').empty().hide();
                    // Re-populate tenants jika daftar telah berubah
                    populateTenants();
                } else {
                    showNotification(response.messages.join("<br>"), "error");
                }
                setTimeout(function() {
                    notification.slideUp(); // Sembunyikan notifikasi setelah 5 detik
                    $('#progress-bar-container').hide();
                }, 5000);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);  // Debugging
                $('#loading').addClass('hidden');
                var notification = $('#notification');
                showNotification('Terjadi kesalahan: ' + error, "error");
                setTimeout(function() {
                    notification.slideUp(); // Sembunyikan setelah 5 detik
                    $('#progress-bar-container').hide();
                }, 5000);
            }
        });
    });

    // Handle penghapusan gambar sebelum diunggah
    $(document).on('click', '.remove-img', function() {
        var index = $(this).parent().index();
        console.log("Menghapus gambar pada index:", index);  // Debugging
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
