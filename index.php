<?php
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// The initial directory path (e.g. /www/your_directory/)
$initial_directory = 'uploads/';
// The current directory path
$current_directory = $initial_directory;
if (isset($_GET['file'])) {
    // If the file is a directory
    if (is_dir($_GET['file'])) {
        // Update the current directory
        $current_directory = rtrim($_GET['file'], '/') . '/';
    } else {
        // Download file
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($_GET['file']) . '"');
        readfile($_GET['file']);
        exit;
    }
}

// Retrieve all files and directories
$results = glob(str_replace(['[',']',"\f[","\f]"], ["\f[","\f]",'[[]','[]]'], ($current_directory ? $current_directory : $initial_directory)) . '*');
// If true, directories will appear first in the populated file list
$directory_first = true;
// Sort files
if ($directory_first) {
    usort($results, function($a, $b){
        $a_is_dir = is_dir($a);
        $b_is_dir = is_dir($b);
        if ($a_is_dir === $b_is_dir) {
            return strnatcasecmp($a, $b);
        } else if ($a_is_dir && !$b_is_dir) {
            return -1;
        } else if (!$a_is_dir && $b_is_dir) {
            return 1;
        }
    });
}

function convert_filesize($bytes, $precision = 2) {
    $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Determine the file type icon
function get_filetype_icon($filetype) {
    if (is_dir($filetype)) {
        return '<i class="fa-solid fa-folder"></i>';
    } else if (preg_match('/image\/*/', mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-image"></i>';
    } else if (preg_match('/video\/*/', mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-video"></i>';
    } else if (preg_match('/audio\/*/', mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-audio"></i>';
    }
    return '<i class="fa-solid fa-file"></i>';
}

// Extract base and current folder names
$base_folder = basename(realpath($initial_directory));
$current_folder = basename(realpath($current_directory));

function build_breadcrumb($initial_directory, $current_directory) {
    $breadcrumb = htmlspecialchars(basename(realpath($initial_directory)));
    $path = $initial_directory;
    $parts = explode('/', trim(str_replace($initial_directory, '', $current_directory), '/'));
    foreach ($parts as $part) {
        if ($part) {
            $path .= $part . '/';
            $breadcrumb .= ' / ' . htmlspecialchars($part);
        }
    }
    return rtrim($breadcrumb, ' / '); // remove trailing slash and space
}

$breadcrumb = build_breadcrumb($initial_directory, $current_directory);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,minimum-scale=1">
    <title>File Management System</title>
    <link rel="stylesheet" href="style-desktop.css" media="screen and (min-width: 601px)">
    <link rel="stylesheet" href="style-mobile.css" media="screen and (max-width: 600px)">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        .breadcrumb a {
            color: gray;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .breadcrumb {
            font-size: 16px;
            color: gray;
        }
        .breadcrumb .breadcrumb-slash {
            margin: 0 5px;
        }
        .upload-button {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50px;
            background-color: #397ed3;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="file-manager">
        <div class="file-manager-container">
            <div class="file-manager-header">
            <div class="breadcrumb">
    <?php
    $basePath = 'Uploads'; // adjust this to your base folder name
    $currentPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $current_folder);
    $relativePath = str_replace($basePath, '', $currentPath);
    $path = array_filter(explode('/', $relativePath));
    $breadcrumb = array();
    $currentPath = $basePath;
    foreach ($path as $folder) {
        $currentPath .= '/' . $folder;
        $breadcrumb[] = '<span>' . htmlspecialchars($folder) . '</span>';
    }
    echo '<span>' . htmlspecialchars($basePath) . '</span> / ' . implode(' / ', $breadcrumb);
    ?>
</div>
                <a href="upload.php?directory=<?= urlencode($current_directory) ?>" class="upload-button"><i class="fa-solid fa-plus"></i></a>
            </div>

            <table class="file-manager-table">
                <thead>
                    <tr>
                        <th class="selected-column">Name<i class="fa-solid fa-arrow-down-long fa-xs"></i></th>
                        <th>Size</th>
                        <th>Modified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($_GET['file']) && realpath($current_directory) != realpath($initial_directory)): ?>
                    <tr>
                        <td colspan="4" class="name"><i class="fa-solid fa-folder"></i><a href="?file=<?= urlencode(dirname($_GET['file'])) ?>">...</a></td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($results as $result): ?>
                    <tr class="file" data-file="<?= htmlspecialchars($result) ?>">
                        <td class="name" data-label="Name"><?= get_filetype_icon($result) ?><a class="view-file" href="?file=<?= urlencode($result) ?>"><?= basename($result) ?></a></td>
                        <td data-label="Size"><?= is_dir($result) ? 'Folder' : convert_filesize(filesize($result)) ?></td>
                        <td class="date" data-label="Modified"><?= str_replace(date('F j, Y'), 'Today,', date('F j, Y H:ia', filemtime($result))) ?></td>
                        <td class="actions" data-label="Actions">
                            <?php if (!is_dir($result)): ?>
                            <a href="rename.php?file=<?= urlencode($result) ?>" class="btn blue"><i class="fa-solid fa-pen-to-square fa-xs"></i></a>
                            <button class="btn red delete-btn" data-file="<?= htmlspecialchars($result) ?>"><i class="fa-solid fa-trash fa-xs"></i></button>
                            <a href="?file=<?= urlencode($result) ?>" class="btn green"><i class="fa-solid fa-download fa-xs"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="message" class="message hidden"></div>
    </div>

    <script>
        $(document).ready(function() {
            $('.delete-btn').on('click', function() {
                var row = $(this).closest('tr');
                var file = $(this).data('file');
                var confirmed = confirm('Are you sure you want to delete ' + file + '?');

                if (confirmed) {
                    $.ajax({
                        url: 'delete.php',
                        type: 'POST',
                        data: { file: encodeURIComponent(file) },
                        success: function(response) {
                            $('#message').removeClass('hidden').removeClass('error').removeClass('success');

                            if (response.success) {
                                $('#message').addClass('success').text(response.message);
                                row.remove(); // Remove the deleted file row from the table
                            } else {
                                $('#message').addClass('error').text(response.message);
                            }
                        },
                        error: function() {
                            $('#message').removeClass('hidden').addClass('error').text('An error occurred while deleting the file.');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
