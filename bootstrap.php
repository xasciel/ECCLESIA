<?php
session_start();
$db=require __DIR__.'/config/database.php';
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function csrf(){if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function verify_csrf(){if(!hash_equals(csrf(),$_POST['csrf']??''))exit('Solicitud inválida');}
function me(){return $_SESSION['user']??null;}
function require_login(){if(!me()){header('Location: index.php');exit;}}
function require_church(){require_login();if(me()['role']==='superadmin')exit('Vista reservada a iglesias');}
function audit($db,$action,$entity='',$id=null){$s=$db->prepare('INSERT INTO audit_logs(church_id,user_id,action,entity,entity_id) VALUES(?,?,?,?,?)');$s->execute([me()['church_id']??null,me()['id']??null,$action,$entity,$id]);}
