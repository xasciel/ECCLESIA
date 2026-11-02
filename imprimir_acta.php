<?php
require 'bootstrap.php';require_church();require_once 'plantilla_campos.php';
$cid=(int)me()['church_id'];$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id){http_response_code(400);exit('Acta inválida');}
$s=$db->prepare('SELECT r.*,p.first_names,p.last_names,p.birth_date,p.birthplace,p.father_name,p.mother_name,b.book_number,b.division,b.year_start,b.year_end,b.sacrament FROM records r JOIN people p ON p.id=r.person_id AND p.church_id=r.church_id JOIN books b ON b.id=r.book_id AND b.church_id=r.church_id WHERE r.id=? AND r.church_id=? AND r.deleted_at IS NULL LIMIT 1');$s->execute([$id,$cid]);$r=$s->fetch(PDO::FETCH_ASSOC);if(!$r){http_response_code(404);exit('Acta no encontrada');}
$s=$db->prepare('SELECT name,address FROM churches WHERE id=?');$s->execute([$cid]);$church=$s->fetch(PDO::FETCH_ASSOC)?:[];
function fecha_acta_ec($v){if(!$v)return '';try{return (new DateTimeImmutable($v))->format('d/m/Y');}catch(Throwable $e){return (string)$v;}}
$s=$db->prepare('SELECT t.id,t.file_path,t.clean_file_path,tp.positions_json FROM templates t JOIN template_positions tp ON tp.template_id=t.id AND tp.church_id=t.church_id WHERE t.church_id=? AND t.sacrament=? AND t.clean_file_path IS NOT NULL ORDER BY t.id DESC LIMIT 1');$s->execute([$cid,$r['sacrament']]);$template=$s->fetch(PDO::FETCH_ASSOC);
$positions=$template?ecclesia_posiciones($template['positions_json']):[];$usePhoto=!!$template&&count($positions)>0;
$values=['iglesia'=>$church['name']??'','direccion'=>$church['address']??'','tipo'=>ucfirst($r['sacrament']),'numero'=>$r['record_number'],'folio'=>$r['folio'],'libro'=>$r['book_number'],'division'=>$r['division']??'','periodo'=>$r['year_start'].' – '.$r['year_end'],'fecha_acta'=>fecha_acta_ec($r['sacrament_date']),'nombre'=>trim($r['first_names'].' '.$r['last_names']),'nacimiento'=>fecha_acta_ec($r['birth_date']),'lugar'=>$r['birthplace'],'padre'=>$r['father_name'],'madre'=>$r['mother_name'],'padrinos'=>$r['godparents'],'observaciones'=>$r['notes']];
require 'layout.php';top('Imprimir acta');
?>
<style>
.ec-print-actions{display:flex;gap:12px;flex-wrap:wrap;margin:0 0 20px}.ec-print-page{position:relative;width:100%;max-width:794px;aspect-ratio:210/297;margin:auto;background:#fff;color:#111;box-shadow:0 4px 22px #0002;overflow:hidden}.ec-print-page>img{position:absolute;inset:0;width:100%;height:100%;object-fit:fill}.ec-print-value{position:absolute;font-family:Arial,sans-serif;white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.15}.ec-default{padding:50px;box-sizing:border-box}.ec-default h1{text-align:center;color:#5b2937}.ec-default .line{padding:10px 0;border-bottom:1px solid #ddd}
@media print{@page{size:A4;margin:0}html,body{margin:0!important;padding:0!important;background:#fff!important}.sidebar,.topbar,.ec-print-actions,nav,.app-sidebar,footer{display:none!important}.workspace,.app-main,main,.content,.main-content{margin:0!important;padding:0!important;width:100%!important;max-width:none!important;background:#fff!important}.ec-print-page{width:210mm!important;height:297mm!important;max-width:none!important;aspect-ratio:auto!important;box-shadow:none!important;break-inside:avoid}.ec-print-page>img{print-color-adjust:exact;-webkit-print-color-adjust:exact}}
</style>
<div class="ec-print-actions"><button type="button" onclick="window.print()">Imprimir / Guardar PDF</button><a class="btn secondary" href="records.php">Volver a actas</a></div>
<?php if($usePhoto):?>
<article class="ec-print-page"><img src="ver_plantilla.php?id=<?=(int)$template['id']?>&clean=1" alt="Formato parroquial">
<?php foreach($positions as $key=>$pos):?><div class="ec-print-value" style="left:<?=e($pos['x'])?>%;top:<?=e($pos['y'])?>%;width:<?=e($pos['width'])?>%;font-size:<?=e($pos['size'])?>px"><?=e((string)($values[$key]??''))?></div><?php endforeach;?></article>
<?php else:?>
<article class="ec-print-page ec-default"><div style="text-align:center;font-size:30px">✝</div><h2 style="text-align:center"><?=e($church['name']??'Parroquia')?></h2><p style="text-align:center"><?=e($church['address']??'')?></p><h1><?=e(ucfirst($r['sacrament']))?></h1><p style="text-align:center">Constancia de registro parroquial</p>
<?php foreach(['numero'=>'Número de acta','folio'=>'Folio','libro'=>'Libro','periodo'=>'Periodo','fecha_acta'=>'Fecha del acta','nombre'=>'Nombre completo','nacimiento'=>'Nacimiento','lugar'=>'Lugar de nacimiento','padre'=>'Padre','madre'=>'Madre','padrinos'=>'Padrinos o testigos','observaciones'=>'Observaciones'] as $key=>$label):?><div class="line"><small><?=e($label)?></small><div><strong><?=e((string)($values[$key]?:'—'))?></strong></div></div><?php endforeach;?></article>
<?php endif;?>
<?php bottom();
