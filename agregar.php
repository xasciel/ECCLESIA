<?php
require 'bootstrap.php';require_church();require_once 'catalogo.php';
$cid=(int)me()['church_id'];$types=ecclesia_tipos();$msg='';
$editId=(int)($_GET['edit']??0);$edit=null;
if($editId){$s=$db->prepare('SELECT * FROM books WHERE id=? AND church_id=?');$s->execute([$editId,$cid]);$edit=$s->fetch(PDO::FETCH_ASSOC);if(!$edit){http_response_code(404);exit('Libro no encontrado');}}
require_permission(((int)($_POST['id']??$editId))?'edit':'create');
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$id=(int)($_POST['id']??0);
 $num=trim($_POST['book_number']??'');$sac=trim($_POST['sacrament']??'');
 $start=(int)($_POST['year_start']??0);$end=(int)($_POST['year_end']??0);$notes=trim($_POST['notes']??'');
 if($id){$s=$db->prepare('SELECT * FROM books WHERE id=? AND church_id=?');$s->execute([$id,$cid]);$old=$s->fetch(PDO::FETCH_ASSOC);if(!$old){http_response_code(404);exit('Registro no encontrado');}}
 if($num===''||!isset($types[$sac])||$start<1500||$end>2200||$start>$end)$msg='Revisa los datos del libro.';
 if(!$msg){
  if($id){
   $s=$db->prepare('UPDATE books SET book_number=?,sacrament=?,year_start=?,year_end=?,notes=?,parent_book_id=NULL WHERE id=? AND church_id=?');
   $s->execute([$num,$sac,$start,$end,$notes,$id,$cid]);
  }else{
   $s=$db->prepare('INSERT INTO books(church_id,sacrament,book_number,year_start,year_end,notes,division,parent_book_id) VALUES(?,?,?,?,?,?,?,?)');
   $s->execute([$cid,$sac,$num,$start,$end,$notes,'',null]);$id=(int)$db->lastInsertId();
  }
  if(!$msg){audit($db,'save','book',$id);header('Location: books.php');exit;}
 }
}
require 'layout.php';top('Agregar libro');?>
<div class="page-head"><div><h1><?= $edit?'Editar':'Agregar' ?> libro</h1></div><a class="btn secondary" href="books.php">Volver a libros</a></div>
<?php if($msg):?><p class="notice"><?=e($msg)?></p><?php endif;?>
<section class="card compact-form"><form method="post">
<input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=(int)($edit['id']??0)?>">
<label>Tipo de acta</label><select name="sacrament" required><?php foreach($types as $slug=>$name):?><option value="<?=e($slug)?>" <?=($edit['sacrament']??'')===$slug?'selected':''?>><?=e($name)?></option><?php endforeach;?></select>
<label>Número del libro</label><input name="book_number" required value="<?=e($edit['book_number']??'')?>">
<div class="field-grid"><div><label>Año inicial</label><input type="number" name="year_start" min="1500" max="2200" required value="<?=e($edit['year_start']??'')?>"></div><div><label>Año final</label><input type="number" name="year_end" min="1500" max="2200" required value="<?=e($edit['year_end']??'')?>"></div></div>
<label>Observaciones</label><textarea name="notes"><?=e($edit['notes']??'')?></textarea>
<div class="form-actions"><button>Guardar</button><a class="btn secondary" href="books.php">Cancelar</a></div>
</form></section><?php bottom();
