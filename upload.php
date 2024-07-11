<?php
session_start();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$initial_directory = 'uploads/';

if (isset($_GET['directory'])) {
    $current_directory = realpath($_GET['directory']);
    if ($current_directory === false || strpos($current_directory, realpath($initial_directory)) !== 0) {
        die("Error: Invalid directory path");
    }
} else {
    $current_directory = $initial_directory;
}

$response = array('success' => false, 'messages' => array());

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files'])) {
    if (realpath($current_directory) == realpath($initial_directory)) {
        $response['messages'][] = "Error: You cannot upload files directly to the base 'uploads' folder.";
    } else {
        $files = $_FILES['files'];
        $total_files = count($files['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $filename = basename($files['name'][$i]);
            $targetFile = rtrim($current_directory, '/') . '/' . $filename;

            if (file_exists($targetFile)) {
                $response['messages'][] = "Sorry, file $filename already exists.";
            } else {
                if (move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                    $response['messages'][] = "The file $filename has been uploaded.";
                } else {
                    $response['messages'][] = "Sorry, there was an error uploading $filename.";
                }
            }
        }

        $response['success'] = true;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <link href="style.css" rel="stylesheet" type="text/css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f4f4f4;
        }
        .upload-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input[type="file"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        input[type="submit"]:hover {
            background: #45a049;
        }
        .progress-bar-container {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 20px;
        }
        .progress-bar {
            width: 0;
            height: 20px;
            background-color: #4CAF50;
            text-align: center;
            color: white;
            line-height: 20px;
            transition: width 0.4s ease;
        }
        #loading {
            text-align: center;
            margin-top: 20px;
        }
        #result {
            text-align: center;
            margin-top: 20px;
            color: green;
        }
        .error {
            color: red;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h1>Upload Files to <?= htmlspecialchars($current_directory) ?></h1>
        <form id="uploadForm" action="?directory=<?= urlencode($current_directory) ?>" method="post" enctype="multipart/form-data">
            <label for="fileToUpload">Select files to upload:</label>
            <input type="file" name="files[]" id="fileToUpload" multiple required>
            <input type="submit" value="Upload Files" name="submit">
        </form>
        <div id="loading" class="hidden">Uploading...</div>
        <div class="progress-bar-container hidden" id="progress-bar-container">
            <div class="progress-bar" id="progress-bar">0%</div>
        </div>
        <div id="result" class="hidden"></div>
        <a href="index.php?file=<?= urlencode($current_directory) ?>" class="back-link">Back to File Manager</a>
    </div>

    <script>
        $(document).ready(function() {
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();  // Prevent the default form submission

                var formData = new FormData(this);
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
                    $('#result').addClass('hidden').text('');
                });

                xhr.upload.addEventListener('loadend', function() {
                    $('#loading').addClass('hidden');
                });

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        $('#progress-bar-container').addClass('hidden');
                        $('#result').removeClass('hidden');
                        var response = JSON.parse(xhr.responseText);
                        response.messages.forEach(function(message) {
                            if (response.success) {
                                $('#result').css('color', 'green').append('<p>' + message + '</p>');
                            } else {
                                $('#result').css('color', 'red').append('<p>' + message + '</p>');
                            }
                        });
                    }
                };

                xhr.open('POST', this.action, true);
                xhr.send(formData);
            });
        });
    </script>
</body>
</html>
