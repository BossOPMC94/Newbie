<?php
session_start();
require_once 'vendor/autoload.php'; // Install with: composer require team-reflex/oauth2-discord

// Configuration (Update these!)
define('DISCORD_CLIENT_ID', '1334950570229891193');
define('DISCORD_CLIENT_SECRET', 'u3WxR0exjlSU1oSDECFA3hU0wDRe7vFc');
define('DISCORD_REDIRECT_URI', 'http://your-domain.com/callback.php');
$dataFile = 'data.json';

// Discord OAuth Setup
$provider = new \Discord\OAuth\Discord([
    'clientId'     => DISCORD_CLIENT_ID,
    'clientSecret' => DISCORD_CLIENT_SECRET,
    'redirectUri'  => DISCORD_REDIRECT_URI,
]);

// Handle Discord Login
if (!isset($_SESSION['user']) && isset($_GET['action']) && $_GET['action'] === 'login') {
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;
}

// Handle Discord Callback
if (!isset($_SESSION['user']) && isset($_GET['code'])) {
    if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
        exit('Invalid state');
    }

    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    $_SESSION['user'] = $provider->getResourceOwner($token)->toArray();
    header('Location: index.php');
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Check Login
if (!isset($_SESSION['user'])) {
    echo '<a href="?action=login">Login with Discord</a>';
    exit;
}

// Load VPS Data
$data = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

// Function to Save Data
function saveData($data, $file) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Function to Start Serveo.net Forwarding
function startServeoForwarding($containerName) {
    // Start Serveo.net forwarding for the container's SSH port
    $serveoCommand = "ssh -R 80:localhost:22 serveo.net -R $containerName:22:localhost:22 -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null";
    exec("docker exec -d $containerName $serveoCommand", $output, $return);

    if ($return === 0) {
        return "https://$containerName.serveo.net"; // Serveo.net URL
    }
    return false;
}

// Handle VPS Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user']['id'];
    
    if (isset($_POST['create'])) {
        $password = $_POST['password']; // Password for the VPS
        if (empty($password)) {
            echo "Password is required.";
            exit;
        }

        // Generate unique container name
        $containerName = 'vps_' . uniqid();

        // Start Docker container with password
        exec("docker run -d --name $containerName -e ROOT_PASSWORD=$password vps-ssh-image", $output, $return);

        if ($return === 0) {
            // Start Serveo.net forwarding
            $serveoUrl = startServeoForwarding($containerName);

            if ($serveoUrl) {
                // Save VPS data
                $data[] = [
                    'user_id' => $userId,
                    'container' => $containerName,
                    'serveo_url' => $serveoUrl,
                    'password' => $password, // Store password (for demo purposes only)
                    'created_at' => date('Y-m-d H:i:s')
                ];
                saveData($data, $dataFile);
            } else {
                echo "Failed to start Serveo.net forwarding.";
            }
        } else {
            echo "Failed to create VPS.";
        }
    } elseif (isset($_POST['delete'])) {
        $container = $_POST['container'];
        exec("docker stop $container && docker rm $container");
        $data = array_filter($data, fn($v) => $v['container'] !== $container);
        saveData($data, $dataFile);
    }
}

// Display Dashboard
?>
<!DOCTYPE html>
<html>
<head>
    <title>VPS Manager</title>
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: Arial, sans-serif;
        }
        .vps-card {
            background-color: #1e1e1e;
            border: 1px solid #333;
            padding: 15px;
            margin: 10px;
            border-radius: 5px;
        }
        button {
            background-color: #333;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #555;
        }
        input[type="password"] {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #333;
            background-color: #1e1e1e;
            color: #fff;
        }
    </style>
</head>
<body>
    <h1>Welcome, <?= $_SESSION['user']['username'] ?>!</h1>
    <a href="?logout">Logout</a>

    <h2>Your VPS Instances</h2>
    <?php foreach (array_filter($data, fn($v) => $v['user_id'] === $_SESSION['user']['id']) as $vps): ?>
    <div class="vps-card">
        <p>SSH: <code>ssh root@<?= $vps['serveo_url'] ?> -p 22</code></p>
        <p>Password: <code><?= $vps['password'] ?></code></p>
        <form method="POST">
            <input type="hidden" name="container" value="<?= $vps['container'] ?>">
            <button type="submit" name="delete">Delete VPS</button>
        </form>
    </div>
    <?php endforeach; ?>

    <h3>Create New VPS</h3>
    <form method="POST">
        <label for="password">Set VPS Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" name="create">Create VPS</button>
    </form>
</body>
</html>