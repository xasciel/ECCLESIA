<?php
require 'bootstrap.php';require_permission('users');
$cid=(int)me()['church_id'];$message='';$ready=permissions_ready();$labels=permission_labels();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  $action=$_POST['action']??'';
  if($action==='install'){
   $db->exec('CREATE TABLE IF NOT EXISTS ecclesia_user_permissions (user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,church_id BIGINT UNSIGNED NOT NULL,permissions_json TEXT NOT NULL,INDEX church_permissions (church_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
   audit($db,'enable_permissions','church',$cid);header('Location: team.php?ok=1');exit;
  }
  if(!$ready)throw new RuntimeException('Activa primero la administración de permisos.');
  $permissions=array_values(array_intersect(array_keys($labels),is_array($_POST['permissions']??null)?$_POST['permissions']:[]));
  $id=(int)($_POST['id']??0);
  if($action==='create'){
   $name=trim($_POST['name']??'');$email=trim($_POST['email']??'');$password=$_POST['password']??'';
   if($name===''||strlen($name)>180||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||strlen($password)<12||strlen($password)>72)throw new RuntimeException('Revisa el nombre, correo y contraseña (entre 12 y 72 caracteres).');
   $db->beginTransaction();
   $s=$db->prepare("INSERT INTO users(church_id,name,email,password_hash,role,active) VALUES(?,?,?,?,'church_admin',1)");
   $s->execute([$cid,$name,$email,password_hash($password,PASSWORD_DEFAULT)]);$id=(int)$db->lastInsertId();
   $s=$db->prepare('INSERT INTO ecclesia_user_permissions(user_id,church_id,permissions_json) VALUES(?,?,?)');$s->execute([$id,$cid,json_encode($permissions)]);
   audit($db,'create_collaborator','user',$id);$db->commit();
  }elseif($action==='update'){
   if($id===(int)me()['id'])throw new RuntimeException('Tu cuenta de responsable conserva acceso completo.');
   $s=$db->prepare("SELECT id FROM users WHERE id=? AND church_id=? AND role<>'superadmin'");$s->execute([$id,$cid]);
   if(!$s->fetchColumn())throw new RuntimeException('Usuario no encontrado en tu parroquia.');
   $password=$_POST['password']??'';
   if($password!==''&&(strlen($password)<12||strlen($password)>72))throw new RuntimeException('La contraseña debe tener entre 12 y 72 caracteres.');
   $db->beginTransaction();
   $s=$db->prepare('UPDATE users SET active=? WHERE id=? AND church_id=?');$s->execute([isset($_POST['active'])?1:0,$id,$cid]);
   if($password!==''){$s=$db->prepare('UPDATE users SET password_hash=? WHERE id=? AND church_id=?');$s->execute([password_hash($password,PASSWORD_DEFAULT),$id,$cid]);}
   $s=$db->prepare('INSERT INTO ecclesia_user_permissions(user_id,church_id,permissions_json) VALUES(?,?,?) ON DUPLICATE KEY UPDATE permissions_json=VALUES(permissions_json)');$s->execute([$id,$cid,json_encode($permissions)]);
   audit($db,'update_permissions','user',$id);$db->commit();
  }else{throw new RuntimeException('Acción no válida.');}
  header('Location: team.php?ok=1');exit;
 }catch(Throwable $ex){if($db->inTransaction())$db->rollBack();error_log('Ecclesia equipo: '.$ex->getMessage());$message=$ex instanceof PDOException?'No se pudo guardar. Comprueba que el correo no esté registrado y que la base de datos permita la operación.':$ex->getMessage();}
}
$users=[];
if($ready){$s=$db->prepare("SELECT u.id,u.name,u.email,u.active,p.permissions_json FROM users u LEFT JOIN ecclesia_user_permissions p ON p.user_id=u.id AND p.church_id=u.church_id WHERE u.church_id=? AND u.role<>'superadmin' ORDER BY u.id");$s->execute([$cid]);$users=$s->fetchAll(PDO::FETCH_ASSOC);}
require 'layout.php';top('Equipo y permisos');
?>
<div class="page-head"><div><p class="eyebrow">TRABAJA EN EQUIPO</p><h1>Equipo y permisos</h1><p class="muted">Tú conservas el control. Cada colaborador recibe solo los permisos que eliges.</p></div><a class="btn secondary" href="dashboard.php">Volver al inicio</a></div>
<?php if($message):?><p class="notice" role="alert"><?=e($message)?></p><?php endif;?>
<?php if(isset($_GET['ok'])):?><p class="notice" role="status">Cambios guardados correctamente.</p><?php endif;?>
<?php if(!$ready):?>
<section class="card"><h2>Activa tu equipo</h2><p>Se creará una tabla de permisos. Los libros, actas y cuentas existentes se conservan. Tu cuenta de responsable mantiene el acceso completo.</p><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="install"><button>Activar administración de permisos</button></form></section>
<?php else:?>
<section class="card compact-form"><h2>Invita a un colaborador</h2><p class="muted">Comparte el acceso de forma privada con esa persona. No se envían correos automáticamente.</p><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="create"><div class="field-grid"><div><label for="memberName">Nombre</label><input id="memberName" name="name" maxlength="180" required autocomplete="off"></div><div><label for="memberEmail">Correo de acceso</label><input id="memberEmail" type="email" name="email" maxlength="190" required autocomplete="off"></div></div><label for="memberPassword">Contraseña inicial (12 a 72 caracteres)</label><input id="memberPassword" type="password" name="password" minlength="12" maxlength="72" required autocomplete="new-password"><fieldset class="permission-options"><legend>Puede consultar la información de esta parroquia. Además, permite:</legend><?php foreach($labels as $key=>$label):?><label class="checkline"><input type="checkbox" name="permissions[]" value="<?=e($key)?>"><?=e($label)?></label><?php endforeach;?></fieldset><button>＋ Crear colaborador</button></form></section>
<section class="card"><h2>Personas de tu equipo</h2><div class="team-grid">
<?php foreach($users as $user):$owner=(int)$user['id']===(int)me()['id'];$allowed=json_decode($user['permissions_json']??'[]',true);if(!is_array($allowed))$allowed=[];?>
<article class="team-card"><h3><?=e($user['name'])?></h3><p class="muted"><?=e($user['email'])?></p>
<?php if($owner):?><span class="status-pill">Responsable · Acceso completo</span><?php else:?>
<form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=(int)$user['id']?>"><label class="checkline"><input type="checkbox" name="active" value="1" <?=$user['active']?'checked':''?>>Acceso activo</label><fieldset class="permission-options"><legend>Permisos de <?=e($user['name'])?></legend><?php foreach($labels as $key=>$label):?><label class="checkline"><input type="checkbox" name="permissions[]" value="<?=e($key)?>" <?=in_array($key,$allowed,true)?'checked':''?>><?=e($label)?></label><?php endforeach;?></fieldset><label for="password<?=(int)$user['id']?>">Nueva contraseña (opcional)</label><input id="password<?=(int)$user['id']?>" type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" placeholder="Vacío para conservar la actual"><button class="secondary">Guardar permisos</button></form>
<?php endif;?></article><?php endforeach;?>
</div></section>
<?php endif;bottom();
