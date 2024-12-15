<?php
// Aktifkan error reporting untuk debugging (nonaktifkan di produksi)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set session cookie parameters sebelum session_start()
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => true, // Pastikan menggunakan HTTPS
    'httponly' => true,
    'samesite' => 'Strict' // Atau 'Lax' sesuai kebutuhan
]);

session_start();
include 'db.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role_id'] == 1) {
        // Tentukan path folder berdasarkan tanggal hari ini
        $initial_directory = 'Uploads/DPX_IMAGE/';
        $current_date = date('Y-m-d');
        $date_components = explode('-', $current_date);

        if (count($date_components) == 3) {
            list($year, $month_num, $day) = $date_components;
            // Konversi nomor bulan ke "MM_NamaBulan"
            $monthName = date('F', mktime(0, 0, 0, $month_num, 10)); // Januari, Februari, dll.
            $upload_month = sprintf('%02d', $month_num) . '_' . $monthName;

            // Path lengkap direktori berdasarkan tanggal
            $upload_directory = $initial_directory . "$year/$upload_month/" . sprintf('%02d', $day) . "/";

            // Buat direktori jika belum ada
            if (!file_exists($upload_directory)) {
                mkdir($upload_directory, 0755, true);
            }

            // Encode path untuk URL
            $encoded_path = urlencode($upload_directory);

            // Redirect ke URL yang diinginkan
            header("Location: http://localhost/kamarcek/?file=" . $encoded_path);
            exit();
        } else {
            // Jika format tanggal tidak valid, redirect ke halaman admin umum
            header("Location: admin/index.php");
            exit();
        }
    } elseif ($_SESSION['role_id'] == 2) {
        header("Location: upload.php");
        exit();
    }
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Persiapkan dan jalankan query untuk menemukan user
        $stmt = $conn->prepare("SELECT id, password, role_id FROM users WHERE username = ?");
        if ($stmt === false) {
            // Tangani error prepare statement
            $error_message = "Terjadi kesalahan pada server. Silakan coba lagi nanti.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($user_id, $hashed_password, $role_id);
                $stmt->fetch();

                // Verifikasi password
                if (password_verify($password, $hashed_password)) {
                    // Regenerate session ID untuk mencegah session fixation
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['role_id'] = $role_id;

                    // Redirect berdasarkan role
                    if ($role_id == 1) {
                        // Tentukan path folder berdasarkan tanggal hari ini
                        $initial_directory = 'Uploads/DPX_IMAGE/';
                        $current_date = date('Y-m-d');
                        $date_components = explode('-', $current_date);

                        if (count($date_components) == 3) {
                            list($year, $month_num, $day) = $date_components;
                            // Konversi nomor bulan ke "MM_NamaBulan"
                            $monthName = date('F', mktime(0, 0, 0, $month_num, 10)); // Januari, Februari, dll.
                            $upload_month = sprintf('%02d', $month_num) . '_' . $monthName;

                            // Path lengkap direktori berdasarkan tanggal
                            $upload_directory = $initial_directory . "$year/$upload_month/" . sprintf('%02d', $day) . "/";

                            // Buat direktori jika belum ada
                            if (!file_exists($upload_directory)) {
                                mkdir($upload_directory, 0755, true);
                            }

                            // Encode path untuk URL
                            $encoded_path = urlencode($upload_directory);

                            // Redirect ke URL yang diinginkan
                            header("Location: /kamarcek/?file=" . $encoded_path);
                            exit();
                        } else {
                            // Jika format tanggal tidak valid, redirect ke halaman admin umum
                            header("Location: admin/index.php");
                            exit();
                        }
                    } elseif ($role_id == 2) {
                        header("Location: upload.php");
                        exit();
                    }
                } else {
                    $error_message = "Username atau password tidak valid.";
                }
            } else {
                $error_message = "Username atau password tidak valid.";
            }

            $stmt->close();
        }
    } else {
        $error_message = "Silakan isi kedua bidang.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link href="stylelogin.css" rel="stylesheet" type="text/css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* Tambahkan gaya tambahan jika diperlukan */
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .login-container h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-container p {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .login-container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #aaa;
            border-radius: 4px;
        }

        .login-container button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .login-container button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if (!empty($error_message)): ?>
            <p><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="post" action="login.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
