<?php
require 'bootstrap.php';require_church();
$cid=(int)me()['church_id'];$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
$s=$db->prepare('SELECT file_path,clean_file_path FROM templates WHERE id=? AND church_id=?');$s->execute([$id?:0,$cid]);$t=$s->fetch(PDO::FETCH_ASSOC);
if(!$t){http_response_code(404);exit('No encontrado');}
$clean=isset($_GET['clean'])&&$_GET['clean']==='1';$relative=($clean&&!empty($t['clean_file_path']))?$t['clean_file_path']:$t['file_path'];
if(!preg_match('~^uploads/templates/[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|pdf)$~i',$relative)){http_response_code(403);exit('Ruta inválida');}
$root=realpath(__DIR__.'/uploads/templates');$file=realpath(__DIR__.'/'.$relative);
if(!$root||!$file||strpos($file,$root.DIRECTORY_SEPARATOR)!==0||!is_file($file)){http_response_code(404);exit('Archivo no encontrado');}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file);if(!in_array($mime,['image/jpeg','image/png','application/pdf'],true)){http_response_code(415);exit('Tipo inválido');}
header('Content-Type: '.$mime);header('X-Content-Type-Options: nosniff');header('Content-Disposition: inline');header('Cache-Control: private, no-store');readfile($file);
