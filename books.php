<?php
require 'bootstrap.php';require_church();require_once 'catalogo.php';
$cid=(int)me()['church_id'];$msg='';$types=ecclesia_tipos();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=$_POST['action']??'save';$id=(int)($_POST['id']??0);
 if($action==='delete'){
  require_permission('delete');
  $s=$db->prepare('SELECT COUNT(*) FROM records WHERE book_id=? AND church_id=?');$s->execute([$id,$cid]);$acts=(int)$s->fetchColumn();
  if($acts)$msg='No se puede eliminar: tiene actas.';
  else{$db->beginTransaction();$s=$db->prepare('UPDATE books SET parent_book_id=NULL WHERE parent_book_id=? AND church_id=?');$s->execute([$id,$cid]);$s=$db->prepare('DELETE FROM books WHERE id=? AND church_id=?');$s->execute([$id,$cid]);if($s->rowCount())audit($db,'delete','book',$id);$db->commit();header('Location: books.php');exit;}
 }
}
$s=$db->prepare('SELECT b.*, (SELECT COUNT(*) FROM records r WHERE r.book_id=b.id AND r.church_id=b.church_id AND r.deleted_at IS NULL) AS act_count FROM books b WHERE b.church_id=? ORDER BY b.year_start DESC,b.id DESC');
$s->execute([$cid]);$books=$s->fetchAll(PDO::FETCH_ASSOC);
require 'layout.php';top('Libros');?>
<div class="page-head"><div><h1>Libros parroquiales</h1><p class="muted">Selecciona un libro para ver sus actas.</p></div><a class="btn" href="agregar.php?tipo=libro">Agregar libro</a></div>
<?php if($msg):?><p class="notice"><?=e($msg)?></p><?php endif;?>
<section class="card books-panel" aria-labelledby="booksTitle">
<div class="section-title"><div><p class="eyebrow">TU ARCHIVO</p><h2 id="booksTitle">Libros registrados</h2></div><span class="status-pill" id="bookCount" role="status" aria-live="polite"><?=count($books)?> libros</span></div>
<div class="book-filters" id="bookFilters" hidden>
<div><label for="bookSearch">Buscar libro</label><input type="search" id="bookSearch" placeholder="Número, tipo o periodo…" autocomplete="off"></div>
<div><label for="bookType">Tipo de acta</label><select id="bookType"><option value="">Todos los tipos</option><?php foreach($types as $slug=>$name):?><option value="<?=e($slug)?>"><?=e($name)?></option><?php endforeach;?></select></div>
<div><label for="bookSort">Ordenar por</label><select id="bookSort"><option value="recent">Más recientes</option><option value="oldest">Más antiguos</option><option value="number">Número de libro</option><option value="records">Más actas</option></select></div>
<button class="btn secondary" type="button" id="resetBooks">Limpiar filtros</button>
</div>
<div class="book-grid" id="bookGrid">
<?php foreach($books as $b):?>
<article class="book-card" data-type="<?=e($b['sacrament'])?>" data-year="<?=(int)$b['year_start']?>" data-number="<?=e($b['book_number'])?>" data-records="<?=(int)$b['act_count']?>">
<div class="book-card-top"><span class="book-symbol" aria-hidden="true">▤</span><span class="status-pill"><?=(int)$b['act_count']?> actas</span></div>
<p class="eyebrow"><?=e($types[$b['sacrament']]??$b['sacrament'])?></p>
<h3>Libro <?=e($b['book_number'].(!empty($b['division'])?' · '.$b['division']:''))?></h3>
<p class="muted">Periodo <?=e($b['year_start'].'–'.$b['year_end'])?></p>
<div class="actions"><a class="btn" href="ver_libro.php?id=<?=(int)$b['id']?>">Abrir libro <span aria-hidden="true">→</span></a><a class="btn secondary" href="agregar.php?edit=<?=(int)$b['id']?>">Editar</a></div>
<form method="post" class="book-delete" <?=ecclesia_can('delete')?'':'hidden'?> onsubmit="return confirm('¿Eliminar este libro? Esta acción no se puede deshacer.')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$b['id']?>"><button class="text-danger" type="submit">Eliminar libro</button></form>
</article>
<?php endforeach;?>
</div>
<p class="empty-state" id="noMatchingBooks" hidden>No encontramos libros con esos filtros. Prueba con otro número o tipo de acta.</p>
<?php if(!$books):?><div class="empty-state"><span aria-hidden="true">▤</span><h3>Tu archivo comienza aquí</h3><p class="muted">Crea tu primer libro para organizar las actas de tu parroquia.</p><a class="btn" href="agregar.php">＋ Crear primer libro</a></div><?php endif;?>
</section><?php bottom();
