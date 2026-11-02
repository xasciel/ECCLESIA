<?php
require 'bootstrap.php';require_church();require_once 'catalogo.php';
$cid=(int)me()['church_id'];$msg='';$types=ecclesia_tipos();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=$_POST['action']??'save';$id=(int)($_POST['id']??0);
 if($action==='delete'){
  $s=$db->prepare('SELECT COUNT(*) FROM books WHERE parent_book_id=? AND church_id=?');$s->execute([$id,$cid]);$children=(int)$s->fetchColumn();
  $s=$db->prepare('SELECT COUNT(*) FROM records WHERE book_id=? AND church_id=?');$s->execute([$id,$cid]);$acts=(int)$s->fetchColumn();
  if($children||$acts)$msg='No se puede eliminar: tiene sublibros o actas.';
  else{$s=$db->prepare('DELETE FROM books WHERE id=? AND church_id=?');$s->execute([$id,$cid]);if($s->rowCount())audit($db,'delete','book',$id);header('Location: books.php');exit;}
 }
}
$s=$db->prepare('SELECT b.*, (SELECT COUNT(*) FROM books x WHERE x.parent_book_id=b.id AND x.church_id=b.church_id) AS sub_count,
(SELECT COUNT(*) FROM records r WHERE r.book_id=b.id AND r.church_id=b.church_id AND r.deleted_at IS NULL) AS act_count
FROM books b WHERE b.church_id=? AND b.parent_book_id IS NULL ORDER BY b.year_start DESC,b.id DESC');
$s->execute([$cid]);$books=$s->fetchAll(PDO::FETCH_ASSOC);
$s=$db->prepare('SELECT b.*, (SELECT COUNT(*) FROM records r WHERE r.book_id=b.id AND r.church_id=b.church_id AND r.deleted_at IS NULL) AS act_count
FROM books b WHERE b.church_id=? AND b.parent_book_id IS NOT NULL ORDER BY b.division,b.id');
$s->execute([$cid]);$subs=[];
foreach($s->fetchAll(PDO::FETCH_ASSOC) as $r)$subs[$r['parent_book_id']][]=$r;
require 'layout.php';top('Libros');?>
<div class="page-head"><div><h1>Libros parroquiales</h1><p class="muted">Selecciona un libro para ver sus sublibros y actas.</p></div><a class="btn" href="agregar.php?tipo=libro">Agregar libro</a></div>
<?php if($msg):?><p class="notice"><?=e($msg)?></p><?php endif;?>
<section class="card"><h2>Libros registrados</h2>
<?php foreach($books as $b):?>
<details style="border-bottom:1px solid #e7ddd4;padding:14px 0">
<summary style="cursor:pointer;font-weight:700">
<?=e($b['book_number'])?> · <?=e($types[$b['sacrament']]??$b['sacrament'])?> · <?=e($b['year_start'].'–'.$b['year_end'])?>
<span class="muted small">(<?=count($subs[$b['id']]??[])?> sublibros)</span>
</summary>
<div style="padding:14px 8px">
<div class="actions">
<a class="btn secondary" href="ver_libro.php?id=<?=(int)$b['id']?>">Ver libro</a>
<a class="btn secondary" href="agregar.php?tipo=libro&edit=<?=(int)$b['id']?>">Editar</a>
<a class="btn secondary" href="agregar.php?tipo=sublibro&parent=<?=(int)$b['id']?>">+ Agregar sublibro</a>
<form method="post" onsubmit="return confirm('¿Eliminar libro?')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$b['id']?>"><button class="danger">Eliminar</button></form>
</div>
<?php if(!empty($subs[$b['id']])):?>
<div class="table-scroll"><table><thead><tr><th>Sublibro</th><th>Actas</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($subs[$b['id']] as $sub):?>
<tr><td><?=e($sub['division'])?></td><td><?=(int)$sub['act_count']?></td><td><div class="actions">
<a class="btn secondary" href="ver_libro.php?id=<?=(int)$sub['id']?>">Ver</a>
<a class="btn secondary" href="agregar.php?tipo=sublibro&edit=<?=(int)$sub['id']?>">Editar</a>
<form method="post" onsubmit="return confirm('¿Eliminar sublibro?')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$sub['id']?>"><button class="danger">Eliminar</button></form>
</div></td></tr>
<?php endforeach;?></tbody></table></div>
<?php else:?><p class="muted">Sin sublibros. Puedes registrar actas directamente en este libro.</p><?php endif;?>
</div></details>
<?php endforeach;?>
<?php if(!$books):?><p class="muted">No hay libros registrados.</p><?php endif;?>
</section><?php bottom();
