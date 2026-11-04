<?php
require 'bootstrap.php';require_church();require_once 'catalogo.php';require_once 'plantilla_campos.php';
$cid=(int)me()['church_id'];$msg='';$types=ecclesia_tipos();$fields=ecclesia_campos_acta();
if($_SERVER['REQUEST_METHOD']==='POST'){
 require_permission('templates');
 verify_csrf();$action=$_POST['action']??'';
 if($action==='layout'){
  $tid=(int)($_POST['template_id']??0);$s=$db->prepare('SELECT id FROM templates WHERE id=? AND church_id=?');$s->execute([$tid,$cid]);
  if(!$s->fetchColumn())$msg='Plantilla no encontrada.';
  else{$positions=ecclesia_posiciones($_POST['positions']??'');$s=$db->prepare('INSERT INTO template_positions(template_id,church_id,positions_json) VALUES(?,?,?) ON DUPLICATE KEY UPDATE positions_json=VALUES(positions_json),church_id=VALUES(church_id)');$s->execute([$tid,$cid,json_encode($positions,JSON_UNESCAPED_UNICODE)]);audit($db,'save','template',$tid);$msg='Campos guardados.';}
 }elseif($action==='upload'){
  $file=$_FILES['sample']??null;$sac=$_POST['sacrament']??'';$title=trim($_POST['title']??'');
  if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']>8*1024*1024||$file['size']<1)$msg='Archivo inválido (máximo 8 MB).';
  elseif(!isset($types[$sac])||$title==='')$msg='Completa el nombre y el tipo de acta.';
  else{$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$allowed=['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
   if(!isset($allowed[$mime]))$msg='Solo PDF, JPG o PNG.';
   else{$folder=__DIR__.'/uploads/templates';if(!is_dir($folder)&&!mkdir($folder,0750,true))$msg='No se pudo crear carpeta.';
    if(!$msg){$name=bin2hex(random_bytes(20)).'.'.$allowed[$mime];if(move_uploaded_file($file['tmp_name'],$folder.'/'.$name)){$s=$db->prepare('INSERT INTO templates(church_id,sacrament,title,file_path) VALUES(?,?,?,?)');$s->execute([$cid,$sac,$title,'uploads/templates/'.$name]);$tid=(int)$db->lastInsertId();audit($db,'upload','template',$tid);header('Location: templates.php?edit='.$tid);exit;}else $msg='No se pudo subir.';}
   }
  }
 }
}
$s=$db->prepare('SELECT t.*,tp.positions_json FROM templates t LEFT JOIN template_positions tp ON tp.template_id=t.id AND tp.church_id=t.church_id WHERE t.church_id=? ORDER BY t.id DESC');$s->execute([$cid]);$templates=$s->fetchAll(PDO::FETCH_ASSOC);
$editId=(int)($_GET['edit']??0);$selected=null;foreach($templates as $t){if((int)$t['id']===$editId)$selected=$t;}
require 'layout.php';top('Formatos');
?>
<style>
.ec-stage{position:relative;width:100%;max-width:794px;aspect-ratio:210/297;margin:14px auto;background:white;border:1px solid #ddd;overflow:hidden;touch-action:none}
.ec-stage img{width:100%;height:100%;object-fit:fill;pointer-events:none;display:block}.ec-tag{position:absolute;background:#fff9c9b8;border:1px dashed #713447;color:#2b1b20;cursor:move;touch-action:none;white-space:normal;line-height:1.2;padding:2px;box-sizing:border-box}.ec-tag.active{outline:2px solid #b58a43}.ec-toolbar{display:flex;flex-wrap:wrap;gap:12px;align-items:end}.ec-toolbar label{display:block}.ec-toolbar input,.ec-toolbar select{max-width:190px}.ec-clean{position:relative;width:100%;max-width:794px;aspect-ratio:210/297;margin:14px auto;border:1px solid #ddd;background:white;touch-action:none}.ec-clean canvas{display:block;width:100%;height:100%;cursor:crosshair}.ec-help{font-size:14px;color:#725e62}
</style>
<div class="page-head"><div><h1>Formatos de la parroquia</h1><p class="muted">Reconstruye una fotografía y coloca los datos del acta.</p></div></div>
<?php if($msg):?><p class="notice"><?=e($msg)?></p><?php endif;?>
<section class="card"><h2>Subir formato</h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="upload"><label>Nombre</label><input name="title" maxlength="180" required><label>Tipo de acta</label><select name="sacrament"><?php foreach($types as $slug=>$label):?><option value="<?=e($slug)?>"><?=e($label)?></option><?php endforeach;?></select><label>Fotografía JPG/PNG o PDF (máximo 8 MB)</label><input type="file" name="sample" accept=".jpg,.jpeg,.png,.pdf" required><button>Guardar formato</button></form></section>
<section class="card"><h2>Plantillas registradas</h2><div class="table-scroll"><table><tr><th>Nombre</th><th>Tipo</th><th>Estado</th><th>Acción</th></tr><?php foreach($templates as $t):?><tr><td><?=e($t['title'])?></td><td><?=e($types[$t['sacrament']]??$t['sacrament'])?></td><td><?=!empty($t['clean_file_path'])?'Fondo reconstruido':'Original'?></td><td><a class="btn secondary" href="templates.php?edit=<?=(int)$t['id']?>">Reconstruir / configurar</a></td></tr><?php endforeach;?></table></div></section>
<?php if($selected):?>
<section class="card"><h2>Reconstruir: <?=e($selected['title'])?></h2>
<?php if(preg_match('/\.(jpe?g|png)$/i',$selected['file_path'])):?>
<p class="ec-help">Paso 1: arrastra el cursor para cubrir los datos antiguos con rectángulos del color del papel. <strong>No conserves firmas ni sellos antiguos en nuevas actas.</strong> La imagen original no se modifica. Si el papel tiene textura o marcas de agua, quizá necesites limpiar la imagen externamente para un resultado perfecto.</p>
<div class="ec-toolbar"><div><label>Color de limpieza</label><input id="cleanColor" type="color" value="#ffffff"></div><button type="button" class="secondary" id="undoClean">Deshacer</button><button type="button" class="secondary" id="resetClean">Restablecer original</button><button type="button" id="saveClean">Guardar fondo limpio</button><span id="cleanStatus" class="ec-help"></span></div>
<div class="ec-clean"><canvas id="cleanCanvas"></canvas></div>
<p class="ec-help">Paso 2: coloca los campos dinámicos sobre el fondo limpio y guarda sus posiciones.</p>
<div class="ec-toolbar"><div><label>Campo</label><select id="fieldPick"><?php foreach($fields as $key=>$label):?><option value="<?=e($key)?>"><?=e($label)?></option><?php endforeach;?></select></div><button type="button" id="addField">Añadir campo</button><div><label>Letra (px)</label><input id="fontSize" type="number" min="8" max="40" value="14"></div><div><label>Ancho (%)</label><input id="fieldWidth" type="number" min="5" max="100" value="40"></div><button type="button" class="secondary" id="removeField">Quitar campo</button></div>
<div id="editor" class="ec-stage"><img id="editorImage" src="ver_plantilla.php?id=<?=(int)$selected['id']?>&clean=1" alt="Plantilla"></div>
<form method="post" id="positionsForm"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="layout"><input type="hidden" name="template_id" value="<?=(int)$selected['id']?>"><input type="hidden" name="positions" id="positions"><button>Guardar posiciones</button></form>
<script>
(()=>{
const templateId=<?=(int)$selected['id']?>, csrf=<?=json_encode(csrf())?>;
const originalUrl='ver_plantilla.php?id='+templateId,cleanUrl=originalUrl+'&clean=1';
const canvas=document.getElementById('cleanCanvas'),ctx=canvas.getContext('2d'),color=document.getElementById('cleanColor');
const source=new Image();let masks=[],drag=null,ready=false;
source.onload=()=>{canvas.width=source.naturalWidth;canvas.height=source.naturalHeight;ready=true;paint()};source.src=cleanUrl;
function paint(){if(!ready)return;ctx.drawImage(source,0,0,canvas.width,canvas.height);for(const m of masks){ctx.fillStyle=m.color;ctx.fillRect(m.x,m.y,m.w,m.h);}if(drag){ctx.fillStyle=color.value;ctx.fillRect(drag.x,drag.y,drag.w,drag.h);}}
function point(e){const r=canvas.getBoundingClientRect();return{x:(e.clientX-r.left)*canvas.width/r.width,y:(e.clientY-r.top)*canvas.height/r.height};}
canvas.addEventListener('pointerdown',e=>{if(!ready)return;const p=point(e);drag={x:p.x,y:p.y,w:0,h:0};canvas.setPointerCapture(e.pointerId)});
canvas.addEventListener('pointermove',e=>{if(!drag)return;const p=point(e);drag.w=p.x-drag.x;drag.h=p.y-drag.y;paint()});
canvas.addEventListener('pointerup',()=>{if(!drag)return;let m={x:Math.min(drag.x,drag.x+drag.w),y:Math.min(drag.y,drag.y+drag.h),w:Math.abs(drag.w),h:Math.abs(drag.h),color:color.value};if(m.w>2&&m.h>2)masks.push(m);drag=null;paint()});
document.getElementById('undoClean').onclick=()=>{masks.pop();paint()};
document.getElementById('resetClean').onclick=()=>{if(confirm('¿Volver a la fotografía original?')){source.src=originalUrl+'&v='+Date.now();masks=[];}};
document.getElementById('saveClean').onclick=async()=>{if(!ready)return;const status=document.getElementById('cleanStatus');status.textContent='Guardando...';try{const fd=new FormData();fd.append('csrf',csrf);fd.append('template_id',templateId);fd.append('image',canvas.toDataURL('image/png'));const res=await fetch('guardar_fondo.php',{method:'POST',body:fd,credentials:'same-origin'});const data=await res.json();if(!res.ok||!data.ok)throw Error(data.error||'Error al guardar');status.textContent='Fondo limpio guardado';document.getElementById('editorImage').src=cleanUrl+'&v='+Date.now();}catch(e){status.textContent=e.message}};
const labels=<?=json_encode($fields,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP)?>,initial=<?=json_encode(ecclesia_posiciones($selected['positions_json']??''),JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP)?>;
const editor=document.getElementById('editor'),pick=document.getElementById('fieldPick'),size=document.getElementById('fontSize'),width=document.getElementById('fieldWidth'),out=document.getElementById('positions');let positions={...initial},active=null;
function draw(){editor.querySelectorAll('.ec-tag').forEach(el=>el.remove());Object.entries(positions).forEach(([key,p])=>{const el=document.createElement('div');el.className='ec-tag'+(key===active?' active':'');el.textContent=labels[key];el.style.left=p.x+'%';el.style.top=p.y+'%';el.style.fontSize=p.size+'px';el.style.width=p.width+'%';el.dataset.key=key;el.addEventListener('pointerdown',e=>{e.preventDefault();active=key;pick.value=key;size.value=p.size;width.value=p.width;el.setPointerCapture(e.pointerId);drawActive()});el.addEventListener('pointermove',e=>{if(active!==key||!el.hasPointerCapture(e.pointerId))return;const r=editor.getBoundingClientRect();positions[key].x=Math.max(0,Math.min(98,100*(e.clientX-r.left)/r.width));positions[key].y=Math.max(0,Math.min(98,100*(e.clientY-r.top)/r.height));el.style.left=positions[key].x+'%';el.style.top=positions[key].y+'%';out.value=JSON.stringify(positions)});editor.appendChild(el)});out.value=JSON.stringify(positions)}
function drawActive(){editor.querySelectorAll('.ec-tag').forEach(el=>el.classList.toggle('active',el.dataset.key===active))}
document.getElementById('addField').onclick=()=>{const k=pick.value;if(!positions[k])positions[k]={x:20,y:20,size:14,width:40};active=k;size.value=positions[k].size;width.value=positions[k].width;draw()};
pick.onchange=()=>{active=pick.value;drawActive();if(positions[active]){size.value=positions[active].size;width.value=positions[active].width}};
size.oninput=()=>{if(positions[active]){positions[active].size=Math.max(8,Math.min(40,+size.value||14));draw()}};
width.oninput=()=>{if(positions[active]){positions[active].width=Math.max(5,Math.min(100,+width.value||40));draw()}};
document.getElementById('removeField').onclick=()=>{if(active){delete positions[active];active=null;draw()}};
document.getElementById('positionsForm').addEventListener('submit',()=>out.value=JSON.stringify(positions));draw();
})();
</script>
<?php else:?><p class="notice">Para reconstruir un PDF, primero conviértelo a JPG o PNG y súbelo como una nueva plantilla.</p><?php endif;?>
</section>
<?php endif;?>
<?php bottom();
