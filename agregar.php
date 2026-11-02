<?php
require 'bootstrap.php';require_church();require_once 'catalogo.php';
$cid=(int)me()['church_id'];$types=ecclesia_tipos();$msg='';
$mode=$_GET['tipo']??'libro';if(!in_array($mode,['libro','sublibro'],true))$mode='libro';
$editId=(int)($_GET['edit']??0);$edit=null;
if($editId){$s=$db->prepare('SELECT * FROM books WHERE id=? AND church_id=?');$s->execute([$editId,$cid]);$edit=$s->fetch(PDO::FETCH_ASSOC);if(!$edit){http_response_code(404);exit('Libro no encontrado');}$mode=$edit['parent_book_id']?'sublibro':'libro';}
$s=$db->prepare('SELECT * FROM books WHERE church_id=? AND parent_book_id IS NULL ORDER BY year_start DESC,id DESC');$s->execute([$cid]);$parents=$s->fetchAll(PDO::FETCH_ASSOC);
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$id=(int)($_POST['id']??0);$parent=(int)($_POST['parent_book_id']??0);
 $num=trim($_POST['book_number']??'');$division=trim($_POST['division']??'');$sac=trim($_POST['sacrament']??'');
 $start=(int)($_POST['year_start']??0);$end=(int)($_POST['year_end']??0);$notes=trim($_POST['notes']??'');
 if($id){$s=$db->prepare('SELECT * FROM books WHERE id=? AND church_id=?');$s->execute([$id,$cid]);$old=$s->fetch(PDO::FETCH_ASSOC);if(!$old){http_response_code(404);exit('Registro no encontrado');}}
 if($parent){
  $s=$db->prepare('SELECT * FROM books WHERE id=? AND church_id=? AND parent_book_id IS NULL');$s->execute([$parent,$cid]);$p=$s->fetch(PDO::FETCH_ASSOC);
  if(!$p)$msg='Selecciona un libro principal válido.';
  else{$num=$p['book_number'];$sac=$p['sacrament'];$start=(int)$p['year_start'];$end=(int)$p['year_end'];if($division==='')$msg='Escribe el nombre del sublibro.';}
 }elseif($num===''||!isset($types[$sac])||$start<1500||$end>2200||$start>$end)$msg='Revisa los datos del libro.';
 if(!$msg){
  if($id){
   if((int)($old['parent_book_id']??0)!==$parent)$msg='No se permite cambiar el libro principal de un registro existente.';
   else{
    $s=$db->prepare('UPDATE books SET book_number=?,sacrament=?,year_start=?,year_end=?,notes=?,division=? WHERE id=? AND church_id=?');
    $s->execute([$num,$sac,$start,$end,$notes,$division,$id,$cid]);
   }
  }else{
   $s=$db->prepare('INSERT INTO books(church_id,sacrament,book_number,year_start,year_end,notes,division,parent_book_id) VALUES(?,?,?,?,?,?,?,?)');
   $s->execute([$cid,$sac,$num,$start,$end,$notes,$parent?:null]);$id=(int)$db->lastInsertId();
  }
  if(!$msg){audit($db,'save','book',$id);header('Location: books.php');exit;}
 }
}
require 'layout.php';top('Agregar libro');?>
<div class="page-head"><div><h1><?= $edit?'Editar':'Agregar' ?> <?= $mode==='sublibro'?'sublibro':'libro principal' ?></h1></div><a class="btn secondary" href="books.php">Volver a libros</a></div>
<?php if($msg):?><p class="notice"><?=e($msg)?></p><?php endif;?>
<section class="card compact-form"><form method="post">
<input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=(int)($edit['id']??0)?>">
<?php if($mode==='sublibro'):?>
<label>Libro principal</label><select name="parent_book_id" required>
<option value="">Seleccionar libro</option>
<?php foreach($parents as $b):?><option value="<?=(int)$b['id']?>" <?=((int)($edit['parent_book_id']??($_GET['parent']??0))===(int)$b['id'])?'selected':''?>><?=e($b['book_number'].' · '.($types[$b['sacrament']]??$b['sacrament']).' · '.$b['year_start'].'–'.$b['year_end'])?></option><?php endforeach;?>
</select><label>Nombre del sublibro</label><input name="division" maxlength="60" required placeholder="A, B, Especial..." value="<?=e($edit['division']??'')?>">
<p class="muted small">El sublibro hereda el tipo de acta y el periodo de su libro principal.</p>
<?php else:?>
<input type="hidden" name="parent_book_id" value="0">
<label>Tipo de acta</label><select name="sacrament" required><?php foreach($types as $slug=>$name):?><option value="<?=e($slug)?>" <?=($edit['sacrament']??'')===$slug?'selected':''?>><?=e($name)?></option><?php endforeach;?></select>
<label>Número del libro</label><input name="book_number" required value="<?=e($edit['book_number']??'')?>">
<div class="field-grid"><div><label>Año inicial</label><input type="number" name="year_start" min="1500" max="2200" required value="<?=e($edit['year_start']??'')?>"></div><div><label>Año final</label><input type="number" name="year_end" min="1500" max="2200" required value="<?=e($edit['year_end']??'')?>"></div></div>
<?php endif;?>
<label>Observaciones</label><textarea name="notes"><?=e($edit['notes']??'')?></textarea>
<div class="form-actions"><button>Guardar</button><a class="btn secondary" href="books.php">Cancelar</a></div>
</form></section><?php bottom();
