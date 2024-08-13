<?php
session_start();

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// The initial directory path (e.g. /www/your_directory/)
$initial_directory = 'Uploads/';
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
function get_filetype_icon($file) {
    static $mime_cache = [];

    // Check if file is a directory
    if (is_dir($file)) {
        return '<i class="fa-solid fa-folder"></i>';
    }

    // Get mime type and cache it
    if (!isset($mime_cache[$file])) {
        $mime_type = @mime_content_type($file);
        if ($mime_type === false) {
            $mime_type = 'application/octet-stream'; // Default to a binary file type
        }
        $mime_cache[$file] = $mime_type;
    } else {
        $mime_type = $mime_cache[$file];
    }

    // Determine the icon based on the mime type
    switch (true) {
        case preg_match('/^image\//', $mime_type):
            return '<i class="fa-solid fa-file-image"></i>';
        case preg_match('/^video\//', $mime_type):
            return '<i class="fa-solid fa-file-video"></i>';
        case preg_match('/^audio\//', $mime_type):
            return '<i class="fa-solid fa-file-audio"></i>';
        case preg_match('/^application\/pdf$/', $mime_type):
            return '<i class="fa-solid fa-file-pdf"></i>';
        case preg_match('/^application\/(msword|vnd.openxmlformats-officedocument.wordprocessingml.document)$/', $mime_type):
            return '<i class="fa-solid fa-file-word"></i>';
        case preg_match('/^application\/(vnd.ms-excel|vnd.openxmlformats-officedocument.spreadsheetml.sheet)$/', $mime_type):
            return '<i class="fa-solid fa-file-excel"></i>';
        case preg_match('/^application\/(vnd.ms-powerpoint|vnd.openxmlformats-officedocument.presentationml.presentation)$/', $mime_type):
            return '<i class="fa-solid fa-file-powerpoint"></i>';
        case preg_match('/^text\//', $mime_type):
            return '<i class="fa-solid fa-file-alt"></i>';
        case preg_match('/^application\/(zip|x-tar|gzip)$/', $mime_type):
            return '<i class="fa-solid fa-file-archive"></i>';
        default:
            return '<i class="fa-solid fa-file"></i>';
    }
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
        /* Styles for the image modal */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            padding-top: 60px; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.9);
        }

        .modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
        }

        .modal-content, #caption {  
            animation-name: zoom;
            animation-duration: 0.6s;
        }

        @keyframes zoom {
            from {transform:scale(0)} 
            to {transform:scale(1)}
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
        }

        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        .prev, .next {
            cursor: pointer;
            position: absolute;
            top: 50%;
            width: auto;
            padding: 16px;
            margin-top: -50px;
            color: white;
            font-weight: bold;
            font-size: 20px;
            transition: 0.6s ease;
            border-radius: 0 3px 3px 0;
            user-select: none;
        }

        .next {
            right: 0;
            border-radius: 3px 0 0 3px;
        }

        .prev:hover, .next:hover {
            background-color: rgba(0,0,0,0.8);
        }

        #caption {
            text-align: center;
            color: #ccc;
            padding: 10px 0;
            height: auto;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
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
                <a href="submit.php?directory=<?= urlencode($current_directory) ?>" class="upload-button"><i class="fa-solid fa-plus"></i></a>
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
    <td class="name" data-label="Name"><?= get_filetype_icon($result) ?><a class="view-file truncate" href="?file=<?= urlencode($result) ?>" data-fullname="<?= basename($result) ?>"><?= basename($result) ?></a></td>
    <td data-label="Size"><?= is_dir($result) ? 'Folder' : convert_filesize(filesize($result)) ?></td>
    <td class="date" data-label="Modified"><?= str_replace(date('F j, Y'), 'Today,', date('F j, Y H:ia', filemtime($result))) ?></td>
    <td class="actions">
        <?php if (!is_dir($result)): ?>
            <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
                <a href="rename.php?file=<?= urlencode($result) ?>" class="btn blue"><i class="fa-solid fa-pen-to-square fa-xs"></i></a>
                <button class="btn red delete-btn" data-file="<?= htmlspecialchars($result) ?>"><i class="fa-solid fa-trash fa-xs"></i></button>
                <a href="?file=<?= urlencode($result) ?>" class="btn green"><i class="fa-solid fa-download fa-xs"></i></a>
            <?php endif; ?>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="message" class="message hidden"></div>
    </div>

    <!-- The Modal -->
    <div id="myModal" class="modal">
        <span class="close">&times;</span>
        <a class="prev">&#10094;</a>
        <img class="modal-content" id="img01">
        <a class="next">&#10095;</a>
        <div id="caption"></div>
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

            // JavaScript to truncate filename but keep the extension
            $('.truncate').each(function() {
                var text = $(this).text();
                var maxLength = 30; // Adjust this value as needed
                if (text.length > maxLength) {
                    var extIndex = text.lastIndexOf('.');
                    if (extIndex > 0) {
                        var name = text.substring(0, extIndex);
                        var ext = text.substring(extIndex);
                        var truncatedName = name.slice(0, maxLength - ext.length - 8) + '..u';
                        $(this).text(truncatedName + ext);
                    } else {
                        var truncatedText = text.slice(0, maxLength - 8) + '..d';
                        $(this).text(truncatedText);
                    }
                }
            });

            // JavaScript for image gallery
            var modal = document.getElementById("myModal");
            var modalImg = document.getElementById("img01");
            var captionText = document.getElementById("caption");
            var images = $("a.view-file").filter(function() {
                return $(this).closest("tr").find("i.fa-file-image").length > 0;
            });
            var currentIndex = -1;

            images.on("click", function(event) {
                event.preventDefault();
                currentIndex = images.index(this);
                modal.style.display = "block";
                modalImg.src = this.href;
                captionText.innerHTML = $(this).data("fullname"); // Set full filename in caption
            });

            $(".close").on("click", function() {
                modal.style.display = "none";
            });

            $(".prev").on("click", function() {
                currentIndex = (currentIndex > 0) ? currentIndex - 1 : images.length - 1;
                modalImg.src = images[currentIndex].href;
                captionText.innerHTML = images.eq(currentIndex).data("fullname"); // Set full filename in caption
            });

            $(".next").on("click", function() {
                currentIndex = (currentIndex < images.length - 1) ? currentIndex + 1 : 0;
                modalImg.src = images[currentIndex].href;
                captionText.innerHTML = images.eq(currentIndex).data("fullname"); // Set full filename in caption
            });

            $(document).keydown(function(e) {
                if (modal.style.display === "block") {
                    if (e.key === "ArrowLeft") {
                        $(".prev").click();
                    } else if (e.key === "ArrowRight") {
                        $(".next").click();
                    } else if (e.key === "Escape") {
                        modal.style.display = "none";
                    }
                }
            });
        });
    </script>
</body>
</html>

