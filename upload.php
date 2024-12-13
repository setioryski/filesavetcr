<?php
// upload.php

session_start();
require 'db.php';  // Include your database connection

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

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

// Handle file uploads for POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files'])) {
    $initial_directory = 'Uploads/DPX_IMAGE/';

    // Get the submitted date
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $tenant = isset($_POST['tenant']) ? trim($_POST['tenant']) : '';

    // Debugging: Log the values of date, name, tenant, and username
    error_log("DEBUG: Date = $date, Name = $name, Tenant = $tenant, Username = $username");

    $response = array('success' => false, 'messages' => array());

    if (empty($date) || empty($name) || empty($tenant)) {
        $response['messages'][] = "Error: Date, Name, or Tenant is missing.";
    } else {
        // Parse the date to determine the correct folder
        $date_components = explode('-', $date);
        if (count($date_components) != 3) {
            $response['messages'][] = "Error: Invalid date format.";
        } else {
            list($upload_year, $upload_month_num, $upload_day) = $date_components;
            // Convert month number to "MM_MonthName"
            $monthName = date('F', mktime(0, 0, 0, $upload_month_num, 10)); // March, April, etc.
            $upload_month = sprintf('%02d', $upload_month_num) . '_' . $monthName;

            // Adjust the directory path based on the selected date
            $upload_directory = $initial_directory . "$upload_year/$upload_month/" . sprintf('%02d', $upload_day) . "/";

            // Create the directory if it doesn't exist
            if (!file_exists($upload_directory)) {
                mkdir($upload_directory, 0755, true);
            }

            $files = $_FILES['files'];
            $total_files = count($files['name']);

            for ($i = 0; $i < $total_files; $i++) {
                // Check for upload errors
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $response['messages'][] = "Error uploading file " . htmlspecialchars($files['name'][$i]) . ". Error code: " . $files['error'][$i];
                    continue;
                }

                // Validate file type
                $fileType = mime_content_type($files['tmp_name'][$i]);
                if (strpos($fileType, 'image/') !== 0) {
                    $response['messages'][] = "File " . htmlspecialchars($files['name'][$i]) . " is not a valid image.";
                    continue;
                }

                $fileExtension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $sanitized_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $name);
                $sanitized_tenant = preg_replace("/[^a-zA-Z0-9_-]/", "", $tenant);
                $sanitized_username = preg_replace("/[^a-zA-Z0-9_-]/", "", $username);
                $filename = "$date-$sanitized_name-$sanitized_tenant-$sanitized_username.$fileExtension";
                $targetFile = rtrim($upload_directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

                // Prevent overwriting existing files by appending a unique identifier if necessary
                $unique_id = 1;
                $base_filename = pathinfo($filename, PATHINFO_FILENAME);
                while (file_exists($targetFile)) {
                    $filename = "{$base_filename}_{$unique_id}.$fileExtension";
                    $targetFile = rtrim($upload_directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                    $unique_id++;
                }

                if (move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                    $response['messages'][] = "The file " . htmlspecialchars($filename) . " has been uploaded.";
                } else {
                    $response['messages'][] = "Sorry, there was an error uploading " . htmlspecialchars($files['name'][$i]) . ".";
                    $response['messages'][] = "Error code: " . $files['error'][$i];
                }
            }
            $response['success'] = true;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If GET request, render the form
// Determine the current date's folder for the "Back to Directory" button
$current_year = date('Y');
$current_month_num = date('m');
$current_day = date('d');
$current_month_name = date('F', mktime(0, 0, 0, $current_month_num, 10));
$current_month = sprintf('%02d', $current_month_num) . '_' . $current_month_name;
$current_directory = 'Uploads/DPX_IMAGE/' . "$current_year/$current_month/" . sprintf('%02d', $current_day) . "/";

// Ensure the current directory exists
if (!file_exists($current_directory)) {
    mkdir($current_directory, 0755, true);
}

$last_directory = basename(rtrim($current_directory, '/\\'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <!-- Link to external CSS if needed -->
    <link href="styleupload.css" rel="stylesheet" type="text/css">
    <link href="style-mobile.css" rel="stylesheet" type="text/css">
    <!-- jQuery CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

</head>
<body>
<div class="upload-container">
    <div class="welcome-message">
        <span>Welcome, <?= htmlspecialchars($username); ?></span>
        <div>
            <a href="logout.php" class="logout-button">Logout</a>
            <a href="index.php?file=<?= urlencode($current_directory) ?>" class="back-button">← Back to Directory</a>
        </div>
    </div>
    <h1>Upload Files to <?= htmlspecialchars($last_directory) ?></h1>
    <form id="uploadForm" method="post" enctype="multipart/form-data">
        <label for="fileToUpload">Take pictures to upload:</label>
        <!-- Hidden File Input (required removed) -->
        <input type="file" name="files[]" id="fileToUpload" accept="image/*" capture="camera" multiple style="display: none;">

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

        <label for="tenant-search">Tenant:</label>
        <div class="tenant-search">
            <input type="text" id="tenant-search" placeholder="Search tenants...">
            <div class="tenant-list" id="tenant-list"></div>
        </div>
        <div id="selected-tenants" class="selected-tenants"></div>

        <input type="hidden" id="tenant" name="tenant">

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
    var selectedTenants = [];
    var selectedFiles = []; // Array to hold all selected files
    var MAX_FILES = 20; // Maximum number of files allowed
    var MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB per file
    var username = '<?= htmlspecialchars($username); ?>'; // Fetch username from PHP
    var year = new Date().getFullYear();

    // Fetch all tenants initially
    fetchTenants('');

    // Listen for input in the search box
    $('#tenant-search').on('keyup', function() {
        var query = $(this).val();
        if(query.length > 0){
            fetchTenants(query);
        } else {
            $('#tenant-list').hide();
        }
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
                tenantList.show();
            },
            error: function() {
                console.error('Error fetching tenants.');
            }
        });
    }

    // Hide tenant list when clicking outside
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.tenant-search').length) {
            $('#tenant-list').hide();
        }
    });

    // Handle tenant selection from the list
    $(document).on('click', '#tenant-list li', function() {
        var tenantName = $(this).data('name');

        if (!selectedTenants.includes(tenantName)) {
            selectedTenants.push(tenantName);
            $('#selected-tenants').append('<div data-name="' + tenantName + '">' + tenantName + ' <span class="remove-tenant">&times;</span></div>');
            updateTenantInput();
        }

        $('#tenant-list').hide();
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

    // Image Preview Functionality for Multiple Images
    function updatePreviews() {
        var previewContainer = $('#image-preview-container');
        previewContainer.empty(); // Clear existing previews

        if (selectedFiles.length > 0) {
            selectedFiles.forEach(function(file, index) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    var imgWrapper = $('<div class="img-wrapper" style="position: relative;"></div>');
                    var img = $('<img>').attr('src', e.target.result).attr('alt', 'Image Preview');
                    var removeBtn = $('<span class="remove-img" style="position: absolute; top: 5px; right: 5px; background-color: rgba(255,255,255,0.7); color: #ff3b30; border-radius: 50%; padding: 2px 6px; cursor: pointer;">&times;</span>');
                    imgWrapper.append(img).append(removeBtn);
                    previewContainer.append(imgWrapper);
                }

                reader.readAsDataURL(file);
            });
            previewContainer.show();
        } else {
            previewContainer.hide();
        }
    }

    // Handle Add Image Button Click
    $('#addImageButton').on('click', function() {
        $('#fileToUpload').click(); // Trigger the hidden file input
    });

    // Handle file input change event
    $("#fileToUpload").on('change', function() {
        var files = this.files;
        if (files.length > 0) {
            // Check if adding these files exceeds the maximum limit
            if (selectedFiles.length + files.length > MAX_FILES) {
                alert("You can upload a maximum of " + MAX_FILES + " images.");
                return;
            }

            Array.from(files).forEach(function(file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert(file.name + " is not an image file.");
                    return;
                }

                // Validate file size
                if (file.size > MAX_FILE_SIZE) {
                    alert(file.name + " exceeds the 5MB size limit.");
                    return;
                }

                // Check if file already exists in selectedFiles
                var exists = selectedFiles.some(function(f) {
                    return f.name === file.name && f.size === file.size && f.lastModified === file.lastModified;
                });
                if (!exists) {
                    selectedFiles.push(file);
                }
            });

            // Update the previews based on selectedFiles
            updatePreviews();

            // Reset the file input value to allow selecting the same file again if needed
            $(this).val('');
        }
    });

    // Handle form submission for file upload
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();  // Prevent the default form submission
        console.log("Submit button clicked");  // Debugging statement

        // Capture form field values immediately
        var date = $('#date').val();
        var name = $('#name').val();
        var tenant = $('#tenant').val();

        console.log("Form Values:", { date: date, name: name, tenant: tenant });  // Debugging

        // Custom validation
        if (!date || !name || !tenant) {
            alert("Please fill out all fields.");
            return;
        }

        if (selectedFiles.length === 0) {
            alert("Please add at least one image to upload.");
            return;
        }

        // Create a FormData object
        var formData = new FormData();
        formData.append('date', date);
        formData.append('name', name);
        formData.append('tenant', tenant);

        selectedFiles.forEach(function(file, index) {
            // Sanitize filenames to prevent security issues
            var sanitized_name = name.replace(/[^a-zA-Z0-9_-]/g, '');
            var sanitized_tenant = tenant.replace(/[^a-zA-Z0-9_-]/g, '');
            var sanitized_username = username.replace(/[^a-zA-Z0-9_-]/g, '');
            var fileExtension = file.name.split('.').pop();
            var newFilename = `${date}-${sanitized_name}-${sanitized_tenant}-${sanitized_username}.${fileExtension}`;
            
            var renamedFile = new File([file], newFilename, {
                type: file.type,
                lastModified: Date.now(),
            });
            
            formData.append('files[]', renamedFile);
        });

        console.log("FormData Prepared");  // Debugging

        // AJAX request to upload.php
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
                console.log("AJAX Request Sent");  // Debugging
            },
            success: function(response) {
                console.log("AJAX Success:", response);  // Debugging
                $('#loading').addClass('hidden');
                var notification = $('#notification');
                if (response.success) {
                    notification.removeClass('error').addClass('success').html(response.messages.join("<br>")).slideDown();
                    // Reset the form and selectedFiles
                    $('#uploadForm')[0].reset();
                    selectedFiles = [];
                    $('#image-preview-container').empty().hide();
                } else {
                    notification.removeClass('success').addClass('error').html(response.messages.join("<br>")).slideDown();
                }
                setTimeout(function() {
                    notification.slideUp(); // Hide the notification after 5 seconds
                    $('#progress-bar-container').hide();
                }, 5000);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);  // Debugging
                $('#loading').addClass('hidden');
                var notification = $('#notification');
                notification.removeClass('success').addClass('error').html('An error occurred: ' + error).slideDown();
                setTimeout(function() {
                    notification.slideUp(); // Hide after 5 seconds
                    $('#progress-bar-container').hide();
                }, 5000);
            }
        });
    });

    // Handle image removal before upload
    $(document).on('click', '.remove-img', function() {
        var index = $(this).parent().index();
        console.log("Removing image at index:", index);  // Debugging
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
