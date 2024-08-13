<?php
require 'db.php';  // Ensure your database connection file is correctly included

$query = isset($_GET['query']) ? $_GET['query'] : '';

if ($query !== '') {
    $stmt = $conn->prepare("SELECT id, name_tenant FROM tenant WHERE name_tenant LIKE ?");
    $search = "%$query%";
    $stmt->bind_param("s", $search);
} else {
    // If the query is empty, fetch all tenants
    $stmt = $conn->prepare("SELECT id, name_tenant FROM tenant");
}

$stmt->execute();
$result = $stmt->get_result();

$tenants = array();
while ($row = $result->fetch_assoc()) {
    $tenants[] = array("id" => $row['id'], "name" => $row['name_tenant']);
}

// echo json_encode($tenants);
?>

<?php
session_start();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include the database connection file
include 'db.php';

// Fetch the username from the database using the user_id stored in the session
$user_id = $_SESSION['user_id'];
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Error: User not found.");
}

$username = $user['username'];  // Username of the person submitting the file

function handleUpload($username) {
    $initial_directory = 'Uploads/DPX_IMAGE/';
    
    // Get today's date components
    $year = date('Y');
    $month = date('m_F');  // Format month as '08_August'
    $day = date('d');       // Format day as '09'

    // Build the directory path
    $current_directory = $initial_directory . "$year/$month/$day/";

    // Create the directory if it doesn't exist
    if (!file_exists($current_directory)) {
        mkdir($current_directory, 0777, true);
    }

    $response = array('success' => false, 'messages' => array());

    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files'])) {
        $files = $_FILES['files'];
        $total_files = count($files['name']);
        $date = isset($_POST['date']) ? $_POST['date'] : '';
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        $tenant = isset($_POST['tenant']) ? $_POST['tenant'] : '';

        // Debugging: Log the values of date, name, tenant, year, and username
        error_log("DEBUG: Date = $date, Name = $name, Tenant = $tenant, Year = $year, Username = $username");

        if (empty($date) || empty($name) || empty($tenant)) {
            $response['messages'][] = "Error: Date, Name, or Tenant is missing.";
        } else {
            for ($i = 0; $i < $total_files; $i++) {
                $fileExtension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $filename = "$date-$name-$tenant-$username.$fileExtension";
                $targetFile = rtrim($current_directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

                // Debugging: Log the generated filename and target path
                error_log("DEBUG: Filename = $filename, Target = $targetFile");

                if (file_exists($targetFile)) {
                    $response['messages'][] = "Sorry, file $filename already exists.";
                } else {
                    if (move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                        $response['messages'][] = "The file $filename has been uploaded.";
                    } else {
                        $response['messages'][] = "Sorry, there was an error uploading $filename.";
                        $response['messages'][] = "Error code: " . $_FILES['files']['error'][$i];
                    }
                }
            }
            $response['success'] = true;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    return $current_directory;
}

$current_directory = handleUpload($username);
$last_directory = basename($current_directory);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <link href="styleupload.css" rel="stylesheet" type="text/css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
<div class="upload-container">
    <div class="welcome-message">
        Welcome, <?= htmlspecialchars($username); ?>!
        <!-- Logout button added -->
        <a href="logout.php" class="logout-button" style="float: right; padding: 5px 10px; background-color: #f44336; color: white; text-decoration: none; border-radius: 5px;">Logout</a>
    </div>
    <h1>Upload Files to <?= htmlspecialchars($last_directory) ?></h1>
    <form id="uploadForm" action="?directory=<?= urlencode($current_directory) ?>" method="post" enctype="multipart/form-data">
        <label for="fileToUpload">Take a picture to upload:</label>
        <input type="file" name="files[]" id="fileToUpload" accept="image/*" capture="camera" required>

        <label for="date">Select Date:</label>
        <input type="date" id="date" name="date" required readonly>

        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="tenant-search">Tenant:</label>
        <div class="tenant-search">
            <input type="text" id="tenant-search" placeholder="Search tenants...">
            <div class="tenant-list" id="tenant-list"></div>
        </div>
        <div id="selected-tenants"></div>

        <input type="hidden" id="tenant" name="tenant">

        <input type="submit" value="Submit Photo" name="submit">
    </form>
    <div id="notification" class="notification"></div>
</div>

<script>
$(document).ready(function() {
    var selectedTenants = [];

    // Fetch all tenants initially
    fetchTenants('');

    // Listen for input in the search box
    $('#tenant-search').on('keyup', function() {
        var query = $(this).val();
        fetchTenants(query);
    });

    // Function to fetch tenants from the server
    function fetchTenants(query) {
        $.ajax({
            url: 'fetch_tenants.php',
            method: 'GET',
            data: { query: query },
            dataType: 'json',
            success: function(response) {
                var tenantList = $('#tenant-list');
                tenantList.empty();

                if (response.length > 0) {
                    response.forEach(function(tenant) {
                        tenantList.append('<li data-name="' + tenant.name + '">' + tenant.name + '</li>');
                    });
                } else {
                    tenantList.append('<li>No results found</li>');
                }
            }
        });
    }

    // Handle tenant selection from the list
    $(document).on('click', '#tenant-list li', function() {
        var tenantName = $(this).data('name');

        if (!selectedTenants.includes(tenantName)) {
            selectedTenants.push(tenantName);
            $('#selected-tenants').append('<div data-name="' + tenantName + '">' + tenantName + ' <span class="remove-tenant" style="cursor:pointer;color:#ff3b30;">&times;</span></div>');
            updateTenantInput();
        }

        $('#tenant-list').empty();
        $('#tenant-search').val('');
    });

    // Handle tenant removal from the selected list
    $(document).on('click', '.remove-tenant', function() {
        var tenantDiv = $(this).parent();
        var tenantName = tenantDiv.data('name');

        selectedTenants = selectedTenants.filter(function(name) {
            return name !== tenantName;
        });

        tenantDiv.remove();
        updateTenantInput();
    });

    // Update the hidden input with selected tenant names separated by underscores
    function updateTenantInput() {
        $('#tenant').val(selectedTenants.join('_'));
    }

    // Set the date input to today's date and make it read-only
    var today = new Date().toISOString().split('T')[0];
    $('#date').val(today);

    // Handle form submission for file upload
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();  // Prevent the default form submission

        // Capture form field values immediately
        var date = $('#date').val();
        var name = $('#name').val();
        var tenant = $('#tenant').val();

        if (!date || !name || !tenant) {
            alert("Please fill out all fields.");
            return;
        }

        var files = $('#fileToUpload')[0].files;
        if (files.length > 0) {
            for (var i = 0; i < files.length; i++) {
                resizeAndCompressImage(files[i], function(resizedFile) {
                    uploadFile(resizedFile, date, name, tenant);
                });
            }
        }
    });

    // Function to resize and compress image files
    function resizeAndCompressImage(file, callback) {
        const maxWidth = 800;
        const maxHeight = 800;
        const targetSizeKB = 50;
        const reader = new FileReader();

        reader.onload = function(event) {
            const img = new Image();
            img.src = event.target.result;

            img.onload = function() {
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height *= maxWidth / width;
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width *= maxHeight / height;
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                let quality = 0.8;
                function compress() {
                    canvas.toBlob(function(blob) {
                        if (blob.size <= targetSizeKB * 1024 || quality < 0.2) {
                            const resizedFile = new File([blob], file.name, {
                                type: file.type,
                                lastModified: Date.now(),
                            });
                            callback(resizedFile);
                        } else {
                            quality -= 0.1;
                            compress();
                        }
                    }, file.type, quality);
                }

                compress();
            };
        };

        reader.readAsDataURL(file);
    }

    // Function to upload the resized file
    function uploadFile(file, date, name, tenant) {
        var formData = new FormData();
        var year = new Date().getFullYear();
        var fileExtension = file.name.split('.').pop();
        var newFilename = `${year}-${date}-${name}-${tenant}.${fileExtension}`;

        var renamedFile = new File([file], newFilename, {
            type: file.type,
            lastModified: Date.now(),
        });

        formData.append('date', date);
        formData.append('name', name);
        formData.append('tenant', tenant);
        formData.append('files[]', renamedFile);

        var xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var percentComplete = (e.loaded / e.total) * 100;
                $('#progress-bar').css('width', percentComplete + '%').text(Math.round(percentComplete) + '%');
            }
        });

        xhr.upload.addEventListener('loadstart', function() {
            $('#loading').removeClass('hidden');
            $('#progress-bar-container').removeClass('hidden');
        });

        xhr.upload.addEventListener('loadend', function() {
            $('#loading').addClass('hidden');
        });

        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    var notification = $('#notification');
                    var resultDiv = $('<div class="result-entry"></div>');
                    response.messages.forEach(function(message) {
                        if (response.success) {
                            notification.removeClass('error').addClass('success').html('Successfully uploaded: ' + newFilename).slideDown();
                            // Reset the form fields
                            $('#fileToUpload').val('');
                            $('#name').val('');
                            $('#tenant-search').val('');
                            $('#selected-tenants').empty();
                            selectedTenants = [];
                            updateTenantInput();
                        } else {
                            notification.removeClass('success').addClass('error').html(message).slideDown();
                        }
                    });
                    setTimeout(function() {
                        notification.slideUp(); // Hide the notification after a few seconds
                    }, 5000); // Hide after 5 seconds
                } else {
                    console.error('Error: ' + xhr.statusText);
                    var notification = $('#notification');
                    notification.removeClass('success').addClass('error').html('Error: ' + xhr.statusText).slideDown();
                    setTimeout(function() {
                        notification.slideUp(); // Hide the notification after a few seconds
                    }, 5000); // Hide after 5 seconds
                }
            }
        };

        xhr.onerror = function() {
            console.error('Network Error');
            var notification = $('#notification');
            notification.removeClass('success').addClass('error').html('Network Error').slideDown();
            setTimeout(function() {
                notification.slideUp(); // Hide the notification after a few seconds
            }, 5000); // Hide after 5 seconds
        };

        xhr.ontimeout = function() {
            console.error('Request Timed Out');
            var notification = $('#notification');
            notification.removeClass('success').addClass('error').html('Request Timed Out').slideDown();
            setTimeout(function() {
                notification.slideUp(); // Hide the notification after a few seconds
            }, 5000); // Hide after 5 seconds
        };

        xhr.open('POST', $('#uploadForm').attr('action'), true);
        xhr.send(formData);
    }
});
</script>

</body>
</html>
