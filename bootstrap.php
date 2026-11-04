<?php
session_start();
$db=require __DIR__.'/config/database.php';
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function csrf(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function verify_csrf(){if(!hash_equals(csrf(),$_POST['csrf']??''))exit('Solicitud inválida');}
function me(){return $_SESSION['user']??null;}
function require_login(){
 static $checked=false;
 if(!me()){header('Location: index.php');exit;}
 if($checked)return;
 global $db;
 $s=$db->prepare('SELECT u.id,u.name,u.role,u.church_id,u.active,u.password_hash,c.status church_status FROM users u LEFT JOIN churches c ON c.id=u.church_id WHERE u.id=?');
 $s->execute([(int)me()['id']]);$user=$s->fetch(PDO::FETCH_ASSOC);
 if(!$user||!$user['active']||($user['role']!=='superadmin'&&$user['church_status']!=='approved')){
  unset($_SESSION['user'],$_SESSION['auth_signature']);header('Location: index.php');exit;
 }
 $signature=hash('sha256',$user['password_hash']??'');
 if(isset($_SESSION['auth_signature'])&&!hash_equals($_SESSION['auth_signature'],$signature)){unset($_SESSION['user'],$_SESSION['auth_signature']);header('Location: index.php');exit;}
 $_SESSION['auth_signature']=$signature;
 $_SESSION['user']=array_intersect_key($user,array_flip(['id','name','role','church_id']));$checked=true;
}
function require_church(){require_login();if(me()['role']==='superadmin')exit('Vista reservada a iglesias');}
function audit($db,$action,$entity='',$id=null){$s=$db->prepare('INSERT INTO audit_logs(church_id,user_id,action,entity,entity_id) VALUES(?,?,?,?,?)');$s->execute([me()['church_id']??null,me()['id']??null,$action,$entity,$id]);}

require_once __DIR__.'/permissions.php';
