<?php
session_start();
require_once 'vendor/autoload.php';
define('DISCORD_CLIENT_ID', 'YOUR_DISCORD_CLIENT_ID');
define('DISCORD_CLIENT_SECRET', 'YOUR_DISCORD_CLIENT_SECRET');
define('DISCORD_REDIRECT_URI', 'http://your-domain.com/callback.php');
$provider = new \Discord\OAuth\Discord([
    'clientId'     => DISCORD_CLIENT_ID,
    'clientSecret' => DISCORD_CLIENT_SECRET,
    'redirectUri'  => DISCORD_REDIRECT_URI,
]);
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
?>