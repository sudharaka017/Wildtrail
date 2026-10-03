<?php
require '../includes/bootstrap.php';
if (!GOOGLE_CLIENT_ID || !GOOGLE_CLIENT_SECRET) { flash('error', 'Google sign-in is temporarily unavailable. Please sign in with email instead.'); header('Location: ../login.php'); exit; }
if (!isset($_GET['state'],$_GET['code']) || !hash_equals($_SESSION['oauth_state']??'',$_GET['state'])) {
    flash('error', 'The Google sign-in session expired or could not be verified. Please try again.');
    header('Location: ../login.php');
    exit;
}
unset($_SESSION['oauth_state']);
function google_post(string $url,array $data):array {
    $c=curl_init($url);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($data),CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>['Accept: application/json']]);
    $r=curl_exec($c);
    $err=curl_error($c);
    $code=(int)curl_getinfo($c,CURLINFO_HTTP_CODE);
    curl_close($c);
    if($r===false||$code>=400) throw new RuntimeException('Google token exchange failed'.($err?': '.$err:''));
    return json_decode($r,true)?:[];
}
function google_get(string $url,string $token):array {
    $c=curl_init($url);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Accept: application/json'],CURLOPT_TIMEOUT=>20]);
    $r=curl_exec($c);
    $code=(int)curl_getinfo($c,CURLINFO_HTTP_CODE);
    curl_close($c);
    if($r===false||$code>=400) throw new RuntimeException('Google profile request failed.');
    return json_decode($r,true)?:[];
}
try {
    $t=google_post('https://oauth2.googleapis.com/token',['code'=>$_GET['code'],'client_id'=>GOOGLE_CLIENT_ID,'client_secret'=>GOOGLE_CLIENT_SECRET,'redirect_uri'=>APP_URL.'/auth/google-callback.php','grant_type'=>'authorization_code']);
    if(empty($t['access_token'])) throw new RuntimeException('Google did not return an access token.');
    $g=google_get('https://openidconnect.googleapis.com/v1/userinfo',$t['access_token']);
    if(empty($g['email'])||empty($g['sub'])||empty($g['email_verified'])) throw new RuntimeException('A verified Google email is required.');
    $email=strtolower(trim($g['email']));
    $st=$pdo->prepare('SELECT * FROM users WHERE email=? OR google_id=? LIMIT 1');
    $st->execute([$email,$g['sub']]);
    $u=$st->fetch();
    if(!$u) {
        $pdo->prepare("INSERT INTO users(full_name,email,google_id,role,status,avatar_url,last_login) VALUES(?,?,?,'tourist','active',?,NOW())")->execute([$g['name']??$email,$email,$g['sub'],$g['picture']??null]);
        $id=(int)$pdo->lastInsertId();
        $st=$pdo->prepare('SELECT * FROM users WHERE id=?');
        $st->execute([$id]);
        $u=$st->fetch();
    }
    else {
        $pdo->prepare('UPDATE users SET google_id=COALESCE(google_id,?), avatar_url=COALESCE(?,avatar_url), last_login=NOW() WHERE id=?')->execute([$g['sub'],$g['picture']??null,$u['id']]);
        $st=$pdo->prepare('SELECT * FROM users WHERE id=?');
        $st->execute([$u['id']]);
        $u=$st->fetch();
    }
    if($u['status']!=='active') throw new RuntimeException('This account is not active.');
    login_user($u);
    header('Location: ../'.home_for_role($u['role']));
    exit;
}
catch(Throwable $e) {
    flash('error',$e->getMessage());
    header('Location: ../login.php');
    exit;
}
