<?php
include 'auth.php'; // Include authentication check
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        /* General Page Styling */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f7; /* Light gray background */
            color: #333; /* Dark gray text */
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff; /* White background */
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #1a1a1a; /* Darker text for headings */
            border-bottom: 2px solid #e0e0e0; /* Subtle underline */
            padding-bottom: 10px;
        }

        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        li {
            margin-bottom: 15px;
        }

        a {
            text-decoration: none;
            color: #007aff; /* Safari blue */
            font-size: 18px;
            font-weight: 500;
            transition: color 0.3s;
        }

        a:hover {
            color: #0051a8; /* Darker blue on hover */
        }

        a:focus {
            outline: 2px solid #007aff; /* Focus indicator */
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    
    <div class="container">
        <h1>Admin Panel
            <!-- Logout button added -->
        <a href="../logout.php" class="logout-button" style="float: right; padding: 5px 10px; background-color: #f44336; color: white; text-decoration: none; border-radius: 5px;">Logout</a>
        </h1>
        <ul>
            <li><a href="tenants.php">Manage Tenants</a></li>
            <li><a href="users.php">Manage Users</a></li>
            <li><a href="../index.php">Filesave Page</a></li> <!-- Link to root index.php -->
            <li><a href="../submit.php">Submit Page</a></li> <!-- Link to submit.php -->
        </ul>
    </div>
</body>
</html>
