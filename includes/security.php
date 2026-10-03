<?php
function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function csrf_token() {
    if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">';
}
function verify_csrf() {
    if($_SERVER['REQUEST_METHOD']==='POST' && (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf']??'',$_POST['csrf']))) {
        http_response_code(419);
        exit('Invalid security token. Please refresh and try again.');
    }
}
function flash($type,$msg) {
    $_SESSION['flash']=['type'=>$type,'msg'=>$msg];
}
function pull_flash() {
    $f=$_SESSION['flash']??null;
    unset($_SESSION['flash']);
    return $f;
}
function client_ip() {
    return $_SERVER['REMOTE_ADDR']??'unknown';
}
function validate_phone($p, $required=false) {
    return \WildTrail\Support\Validator::sriLankanPhone((string)$p, (bool)$required);
}
function safe_upload($field,$dir,$allowed=['application/pdf','image/jpeg','image/png']) {
    if(empty($_FILES[$field]['name'])) return null;
    $f=$_FILES[$field];
    if($f['error']!==UPLOAD_ERR_OK || $f['size']>MAX_UPLOAD_BYTES) throw new RuntimeException('Upload failed or file is too large.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if(!in_array($mime,$allowed,true)) throw new RuntimeException('Unsupported file type.');
    $ext=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'][$mime];
    if(!is_dir($dir)) mkdir($dir,0755,true);
    $name=bin2hex(random_bytes(16)).'.'.$ext;
    move_uploaded_file($f['tmp_name'],$dir.'/'.$name);
    return $name;
}
