<?php
require '../includes/bootstrap.php';
if (!GOOGLE_CLIENT_ID || !GOOGLE_CLIENT_SECRET) {
    flash('error','Google sign-in is temporarily unavailable. Please sign in with email instead.');
    header('Location: ../login.php'); exit;
}
$_SESSION['oauth_state']=bin2hex(random_bytes(32));
$params=[
 'client_id'=>GOOGLE_CLIENT_ID,
 'redirect_uri'=>APP_URL.'/auth/google-callback.php',
 'response_type'=>'code',
 'scope'=>'openid email profile',
 'state'=>$_SESSION['oauth_state'],
 'prompt'=>'select_account',
 'access_type'=>'online'
];
header('Location: https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params));
exit;
