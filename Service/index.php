<?php
/**
 * Service signup file, for users to create their own JaxBoards forum.
 *
 * PHP Version 5.3.7
 *
 * @category Jaxboards
 * @package  Jaxboards
 *
 * @author  Sean Johnson <seanjohnson08@gmail.com>
 * @author  World's Tallest Ladder <wtl420@users.noreply.github.com>
 * @license MIT <https://opensource.org/licenses/MIT>
 *
 * @link https://github.com/Jaxboards/Jaxboards Jaxboards Github repo
 */
if (!defined('JAXBOARDS_ROOT')) {
    define('JAXBOARDS_ROOT', dirname(__DIR__));
}
if (!defined('SERVICE_ROOT')) {
    define('SERVICE_ROOT', __DIR__);
}

require_once JAXBOARDS_ROOT . '/inc/classes/mysql.php';
require_once JAXBOARDS_ROOT . '/inc/classes/jax.php';

$JAX = new JAX();
$DB = new MySQL();

if (!file_exists(JAXBOARDS_ROOT . '/config.php')) {
    die('Jaxboards not installed!');
}
require_once JAXBOARDS_ROOT . '/config.php';

if (!$CFG['service']) {
    die('Service mode not enabled');
}

/**
 * Recurisvely copies one directory to another.
 *
 * @param string $src The source directory- this must exist already
 * @param string $dst The destination directory- this is assumed to not exist already
 *
 * @return void
 */
function recurseCopy($src, $dst)
{
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (('.' !== $file) && ('..' !== $file)) {
            if (is_dir($src . '/' . $file)) {
                recurseCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

$connected = $DB->connect(
    $CFG['sql_host'],
    $CFG['sql_username'],
    $CFG['sql_password'],
    $CFG['sql_db']
);

$errors = array();
if (isset($JAX->p['submit']) && $JAX->p['submit']) {
    if (isset($JAX->p['post']) && $JAX->p['post']) {
        header('Location: https://test.' . $CFG['domain']);
    }

    if (!$connected) {
        $errors[] = 'There was an error connecting to the MySQL database.';
    }

    $JAX->p['boardurl'] = mb_strtolower($JAX->b['boardurl']);
    if (!$JAX->p['boardurl']
        || !$JAX->p['username']
        || !$JAX->p['password']
        || !$JAX->p['email']
    ) {
        $errors[] = 'all fields required.';
    } elseif (mb_strlen($JAX->p['boardurl']) > 30) {
        $errors[] = 'board url too long';
    } elseif ('www' == $JAX->p['boardurl']) {
        $errors[] = 'WWW is reserved.';
    } elseif (preg_match('@\\W@', $JAX->p['boardurl'])) {
        $errors[] = 'board url needs to consist of letters, ' .
            'numbers, and underscore only';
    }

    $result = $DB->safeselect(
        '`id`',
        'directory',
        'WHERE `registrar_ip`=INET6_ATON(?) AND `date`>?',
        $JAX->getIp(),
        (time() - 7 * 24 * 60 * 60)
    );
    if ($DB->num_rows($result) > 3) {
        $errors[] = 'You may only register one 3 boards per week.';
    }
    $DB->disposeresult($result);

    if (!$JAX->isemail($JAX->p['email'])) {
        $errors[] = 'invalid email';
    }

    if (mb_strlen($JAX->p['username']) > 50) {
        $errors[] = 'username too long';
    } elseif (preg_match('@\\W@', $JAX->p['username'])) {
        $errors[] = 'username needs to consist of letters, ' .
            'numbers, and underscore only';
    }

    $result = $DB->safeselect(
        '`id`',
        'directory',
        'WHERE `boardname`=?',
        $DB->basicvalue($JAX->p['boardurl'])
    );
    if ($DB->arow($result)) {
        $errors[] = 'that board already exists';
    }
    $DB->disposeresult($result);

    if (empty($errors)) {
        $board = $JAX->p['boardurl'];
        $boardPrefix = $board . '_';

        $DB->prefix('');
        // Add board to directory.
        $DB->safeinsert(
            'directory',
            array(
                'boardname' => $board,
                'registrar_email' => $JAX->p['email'],
                'registrar_ip' => $JAX->ip2bin(),
                'date' => time(),
                'referral' => isset($JAX->b['r']) ? $JAX->b['r'] : '',
            )
        );
        $DB->prefix($boardPrefix);

        // Create the directory and blueprint tables
        // Import sql file and run it with php from this:
        // https://stackoverflow.com/a/19752106
        // It's not pretty or perfect but it'll work for our use case...
        $query = '';
        $lines = file(SERVICE_ROOT . '/blueprint.sql');
        foreach ($lines as $line) {
            // Skip comments.
            if ('--' == mb_substr($line, 0, 2) || '' == $line) {
                continue;
            }

            // Replace blueprint_ with board name.
            $line = preg_replace('/blueprint_/', $boardPrefix, $line);

            // Add line to current query.
            $query .= $line;

            // If it has a semicolon at the end, it's the end of the query.
            if (';' == mb_substr(trim($line), -1, 1)) {
                // Perform the query.
                $result = $DB->safequery($query);
                $DB->disposeresult($result);
                // Reset temp variable to empty.
                $query = '';
            }
        }

        // Don't forget to create the admin.
        $DB->safeinsert(
            'members',
            array(
                'name' => $JAX->p['username'],
                'display_name' => $JAX->p['username'],
                'pass' => password_hash($JAX->p['password'], PASSWORD_DEFAULT),
                'email' => $JAX->p['email'],
                'sig' => '',
                'posts' => 0,
                'group_id' => 2,
                'join_date' => time(),
                'last_visit' => time(),
            )
        );

        $dbError = $DB->error();
        if ($dbError) {
            $errors[] = $dbError;
        } else {
            recurseCopy('blueprint', JAXBOARDS_ROOT . '/boards/' . $board);

            header('Location: https://' . $JAX->p['boardurl'] . '.' . $CFG['domain']);
        }
    }
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"https://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="https://www.w3.org/1999/xhtml/" xml:lang="en" lang="en">
<head>
<link media="all" rel="stylesheet" href="./css/main.css" />
<meta name="description" content="The world's very first instant forum." />
<title>Jaxboards - The free AJAX powered forum host</title>
</head>
<body onload="if(top.location!=self.location) top.location=self.location">
<div id='container'>
<div id='logo'><a href="https://<?php echo $CFG['domain']; ?>">&nbsp;</a></div>
  <div id='bar'>
<a href="https://support.<?php echo $CFG['domain']; ?>" class="support">
Support Forum
</a>
<a href="http://test.<?php echo $CFG['domain']; ?>" class="test">
Test Forum
</a>
<a href="http://support.<?php echo $CFG['domain']; ?>" class="resource">
Resources
</a>
</div>
  <div id='content'>
   <div class='box'>
    <div class='content'>
     <form id="signup" method="post">
<?php
foreach ($errors as $error) {
    echo "<div class='error'>${error}</div>";
}
?>
      <input type="text" name="boardurl" id="boardname" />.<?php
        echo $CFG['domain']; ?><br />
      <label for="username">Username:</label>
        <input type="text" id="username" name="username" /><br />
      <label for="password">Password:</label>
        <input type="password" id="password" name="password" /><br />
      <label for="email">Email:</label>
        <input type="text" name="email" id="email" /><br />
      <input type="text" name="post" id="post" />
      <div class='center'>
<input type="submit" name="submit" value="Register a Forum!" />
</div>
     </form>
     <strong>So, you want a community. You've come to the right place.</strong>
<br /><br />
      JaxBoards has been built from the ground up: utilizing feedback from
        members and forum gurus along the way to create the world's first
        real-time, AJAX-powered forum - the first bulletin board software
        to utilize modern technology to make each user's experience as
        easy and as enjoyable as possible.
      <br clear="all" />
     </div>
   </div>
   <div class='box mini box1'>
<div class='title'>Customizable</div>
<div class='content'>
Jaxboards offers entirely new ways to make your forum look exactly the way you want:
<ul>
   <li>Easy CSS</li>
   <li>Template access</li>
   </ul></div>
   </div>
   <div class='box mini box2'>
<div class='title'>Stable &amp; Secure</div>
<div class='content'>
Jaxboards maintains the highest standards of efficient, optimized software
that can handle anything you throw at it, and a support forum that will back
you up 100%.
</div>
</div>
   <div class='box mini box3'>
<div class='title'>Real Time!</div>
<div class='content'>
In an age where communication is becoming ever more terse, we know how
valuable you and your members' time is. Everything that is posted, messaged,
or shared shows up instantly on the screen.<br /><br />
Save your refresh button.
</div>
</div>
    <br clear="all" />
   </div>
</div>
   <div id='copyright'>
        JaxBoards &copy; 2007-<?php date('Y'); ?>, All Rights Reserved
    </div>
</body>
</html>
