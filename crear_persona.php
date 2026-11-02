<?php
require 'bootstrap.php';require_church();header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'Método no permitido']);exit;}
verify_csrf();$cid=(int)me()['church_id'];$first=trim($_POST['first_names']??'');$last=trim($_POST['last_names']??'');
if($first===''||$last===''){http_response_code(422);echo json_encode(['error'=>'Ingresa nombres y apellidos']);exit;}
try{$s=$db->prepare('INSERT INTO people(first_names,last_names,birth_date,birthplace,father_name,mother_name,church_id) VALUES(?,?,?,?,?,?,?)');$s->execute([$first,$last,($_POST['birth_date']??'')?:null,trim($_POST['birthplace']??''),trim($_POST['father_name']??''),trim($_POST['mother_name']??''),$cid]);$id=(int)$db->lastInsertId();audit($db,'save','person',$id);echo json_encode(['ok'=>true,'id'=>$id,'name'=>$first.' '.$last],JSON_UNESCAPED_UNICODE);}catch(Throwable $ex){error_log($ex->getMessage());http_response_code(500);echo json_encode(['error'=>'No fue posible registrar a la persona']);}
