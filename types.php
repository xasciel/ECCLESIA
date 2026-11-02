<?php
require 'bootstrap.php'; require_church();
$cid=(int)me()['church_id']; $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $action=$_POST['action']??''; $id=(int)($_POST['id']??0);
 if($action==='save'){
  $name=trim((string)($_POST['name']??''));
  $slug=mb_strtolower($name,'UTF-8');$slug=preg_replace('/[^\pL\pN]+/u','-', $slug);$slug=trim($slug,'-');
  if($name===''||mb_strlen($name)>120){$msg='Escribe un nombre válido (máximo 120 caracteres).';}
  else {try{
   if($id){$s=$db->prepare('UPDATE record_types SET name=? WHERE id=? AND church_id=?');$s->execute([$name,$id,$cid]);}
   else{$s=$db->prepare('INSERT INTO record_types(church_id,name,slug) VALUES(?,?,?)');$s->execute([$cid,$name,$slug]);$id=(int)$db->lastInsertId();}
   audit($db,'save','record_type',$id);header('Location: types.php');exit;
  }catch(Throwable $ex){$msg='Ya existe un tipo similar o no se pudo guardar.';}}
 }elseif($action==='toggle'){
  $s=$db->prepare('UPDATE record_types SET active=IF(active=1,0,1) WHERE id=? AND church_id=?');$s->execute([$id,$cid]);audit($db,'toggle','record_type',$id);header('Location: types.php');exit;
 }
}
$s=$db->prepare('SELECT * FROM record_types WHERE church_id=? ORDER BY active DESC,name');$s->execute([$cid]);$types=$s->fetchAll();
require 'layout.php';top('Tipos de actas');
?>
<div class="page-head"><div><h1>Tipos de actas</h1><p class="muted">Cada parroquia administra sus propios documentos.</p></div><a class="btn secondary" href="records.php">Volver a actas</a></div>
<?php if($msg):?><p class="notice"><?=e($msg)?></p><?php endif;?>
<section class="card"><h2>Agregar tipo de acta</h2><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save"><label>Nombre del documento</label><input name="name" required maxlength="120" placeholder="Ej. Constancia de defunción"><button>Agregar tipo</button></form></section>
<section class="card"><h2>Tipos registrados</h2><div class="table-scroll"><table><tr><th>Nombre</th><th>Estado</th><th>Acciones</th></tr><?php foreach($types as $t):?><tr><td><form method="post" class="inline-edit"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><input name="name" aria-label="Nombre" required value="<?=e($t['name'])?>" maxlength="120"><button>Guardar cambio</button></form></td><td><?= $t['active']?'Activo':'Desactivado' ?></td><td><form method="post" onsubmit="return confirm('¿Cambiar disponibilidad de este tipo? Las actas existentes se conservan.')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=(int)$t['id']?>"><button class="secondary"><?= $t['active']?'Desactivar':'Activar' ?></button></form></td></tr><?php endforeach;?></table></div></section>
<?php bottom();
