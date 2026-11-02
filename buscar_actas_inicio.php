<?php
require 'bootstrap.php';
require_church();
header('Content-Type: text/html; charset=utf-8');
$cid=(int)me()['church_id'];
$q=trim((string)($_GET['q']??''));
$words=preg_split('/\s+/u',$q,-1,PREG_SPLIT_NO_EMPTY);
$words=array_slice($words,0,8);
$params=[$cid];
$sql='SELECT r.id,r.record_number,r.folio,r.sacrament_date,p.id person_id,p.first_names,p.last_names,p.father_name,p.mother_name,p.birth_date,b.book_number,b.sacrament,b.division
FROM records r
INNER JOIN people p ON p.id=r.person_id AND p.church_id=r.church_id
INNER JOIN books b ON b.id=r.book_id AND b.church_id=r.church_id
WHERE r.church_id=? AND r.deleted_at IS NULL';
foreach($words as $word){
 $sql.=' AND CONCAT_WS(" ",p.first_names,p.last_names,p.father_name,p.mother_name,COALESCE(p.birth_date,""),COALESCE(r.sacrament_date,""),COALESCE(r.record_number,""),COALESCE(r.folio,""),b.book_number,b.sacrament,COALESCE(b.division,"")) LIKE ?';
 $params[]='%'.$word.'%';
}
$sql.=' ORDER BY r.sacrament_date DESC,r.id DESC LIMIT 101';
try{$st=$db->prepare($sql);$st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);}
catch(Throwable $ex){http_response_code(500);echo '<p class="notice">No fue posible cargar las actas. Revisa la estructura de la base de datos.</p>';exit;}
$more=count($rows)>100;
if($more)array_pop($rows);
?>
<p class="muted small"><?=count($rows)?> acta(s) mostrada(s)<?=$more?' · Hay más resultados; escribe más datos para filtrar.':''?></p>
<div class="table-scroll"><table>
<thead><tr><th>Persona</th><th>Padre / Madre</th><th>Tipo y fecha</th><th>Libro / Folio</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($rows as $r):?>
<tr>
<td><strong><?=e($r['first_names'].' '.$r['last_names'])?></strong><div class="muted small">Expediente #<?=(int)$r['person_id']?> · Nacimiento: <?=e($r['birth_date']?:'Sin dato')?></div></td>
<td><div>Padre: <?=e($r['father_name']?:'Sin dato')?></div><div>Madre: <?=e($r['mother_name']?:'Sin dato')?></div></td>
<td><?=e(ucfirst($r['sacrament']))?><div class="muted small"><?=e($r['sacrament_date']?:'Sin fecha')?></div></td>
<td><?=e($r['book_number'].' '.($r['division']??''))?><div class="muted small">Folio: <?=e($r['folio']?:'—')?> · Acta: <?=e($r['record_number']?:'—')?></div></td>
<td><div class="actions"><a class="btn secondary" href="consulta.php?persona=<?=(int)$r['person_id']?>">Ver expediente</a><a class="btn secondary" href="imprimir_acta.php?id=<?=(int)$r['id']?>">Imprimir</a><a class="btn secondary" href="records.php?edit=<?=(int)$r['id']?>">Editar</a></div></td>
</tr>
<?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="5">No se encontraron actas con esos datos.</td></tr><?php endif;?>
</tbody></table></div>
