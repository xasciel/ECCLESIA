<?php
require 'bootstrap.php';require_church();
header('Content-Type: application/json; charset=utf-8');
function fail_clean($msg,$code=400){http_response_code($code);echo json_encode(['ok'=>false,'error'=>$msg]);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST')fail_clean('Método inválido',405);
verify_csrf();$cid=(int)me()['church_id'];$tid=(int)($_POST['template_id']??0);
$s=$db->prepare('SELECT id,file_path,clean_file_path FROM templates WHERE id=? AND church_id=?');$s->execute([$tid,$cid]);$t=$s->fetch(PDO::FETCH_ASSOC);if(!$t)fail_clean('Plantilla no encontrada',404);
if(!preg_match('/\.(png|jpe?g)$/i',$t['file_path']))fail_clean('Solo imágenes JPG o PNG');
$data=(string)($_POST['image']??'');if(!preg_match('~^data:image/png;base64,([a-zA-Z0-9+/=]+)$~D',$data,$m))fail_clean('Imagen inválida');
$bytes=base64_decode($m[1],true);if($bytes===false||strlen($bytes)>10*1024*1024||strlen($bytes)<100)fail_clean('La imagen excede 10 MB o está vacía');
$info=@getimagesizefromstring($bytes);if(!$info||$info[2]!==IMAGETYPE_PNG||$info[0]>4000||$info[1]>6000)fail_clean('Formato o dimensiones no válidos');
$dir=__DIR__.'/uploads/templates';if(!is_dir($dir)&&!mkdir($dir,0750,true))fail_clean('No se pudo crear la carpeta');
$name='limpia_'.bin2hex(random_bytes(16)).'.png';$path=$dir.'/'.$name;
if(file_put_contents($path,$bytes,LOCK_EX)===false)fail_clean('No se pudo guardar la imagen',500);
$relative='uploads/templates/'.$name;
try{$s=$db->prepare('UPDATE templates SET clean_file_path=? WHERE id=? AND church_id=?');$s->execute([$relative,$tid,$cid]);audit($db,'clean','template',$tid);echo json_encode(['ok'=>true,'message'=>'Fondo limpio guardado']);}
catch(Throwable $e){@unlink($path);fail_clean('No se pudo actualizar la plantilla',500);}
