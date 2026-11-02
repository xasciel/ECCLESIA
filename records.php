<?php
require 'bootstrap.php';require_church();$cid=(int)me()['church_id'];$msg='';require_once 'catalogo.php';$catalogo=ecclesia_tipos();
$bs=$db->prepare('SELECT id,book_number,sacrament,year_start,year_end,division FROM books WHERE church_id=? ORDER BY year_start DESC,book_number,division');$bs->execute([$cid]);$books=$bs->fetchAll();
$types=[];foreach($catalogo as $slug=>$name)$types[]=['slug'=>$slug,'name'=>$name];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=$_POST['action']??'save';$id=(int)($_POST['id']??0);
 if($action==='delete'){$s=$db->prepare('UPDATE records SET deleted_at=NOW() WHERE id=? AND church_id=? AND deleted_at IS NULL');$s->execute([$id,$cid]);audit($db,'archive','record',$id);header('Location: records.php');exit;}
 $person=(int)($_POST['person_id']??0);$book=(int)($_POST['book_id']??0);$postedType=trim((string)($_POST['record_type']??''));$date=trim((string)($_POST['sacrament_date']??''));
 $s=$db->prepare('SELECT COUNT(*) FROM people WHERE id=? AND church_id=?');$s->execute([$person,$cid]);$validPerson=(bool)$s->fetchColumn();
 $s=$db->prepare('SELECT id,sacrament,year_start,year_end FROM books WHERE id=? AND church_id=?');$s->execute([$book,$cid]);$selectedBook=$s->fetch();
 $validType=isset($catalogo[$postedType]);
 if(!$validPerson||!$selectedBook)$msg='Selecciona una persona y un libro existentes.';
 elseif(!$validType||$selectedBook['sacrament']!==$postedType)$msg='El tipo de acta no corresponde al libro seleccionado.';
 elseif(!$date||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!($dt=DateTimeImmutable::createFromFormat('!Y-m-d',$date))||$dt->format('Y-m-d')!==$date)$msg='Ingresa una fecha válida para el acta.';
 elseif((int)substr($date,0,4)<(int)$selectedBook['year_start']||(int)substr($date,0,4)>(int)$selectedBook['year_end'])$msg='La fecha del acta debe estar entre los años '.$selectedBook['year_start'].' y '.$selectedBook['year_end'].' del libro seleccionado.';
 else{
  $data=[$person,$book,trim($_POST['record_number']??''),trim($_POST['folio']??''),$date,trim($_POST['godparents']??''),trim($_POST['notes']??'')];
  if($id){$s=$db->prepare('UPDATE records SET person_id=?,book_id=?,record_number=?,folio=?,sacrament_date=?,godparents=?,notes=? WHERE id=? AND church_id=? AND deleted_at IS NULL');$s->execute([...$data,$id,$cid]);}
  else{$s=$db->prepare('INSERT INTO records(person_id,book_id,record_number,folio,sacrament_date,godparents,notes,church_id,created_by) VALUES(?,?,?,?,?,?,?,?,?)');$s->execute([...$data,$cid,me()['id']]);$id=(int)$db->lastInsertId();}
  audit($db,'save','record',$id);header('Location: records.php');exit;
 }
}
$edit=null;$selectedPerson=null;if(isset($_GET['edit'])){$s=$db->prepare('SELECT * FROM records WHERE id=? AND church_id=? AND deleted_at IS NULL');$s->execute([(int)$_GET['edit'],$cid]);$edit=$s->fetch();if($edit){$s=$db->prepare('SELECT * FROM people WHERE id=? AND church_id=?');$s->execute([$edit['person_id'],$cid]);$selectedPerson=$s->fetch();}}
$q=trim($_GET['q']??'');$s=$db->prepare('SELECT r.*,p.first_names,p.last_names,b.book_number,b.sacrament FROM records r JOIN people p ON p.id=r.person_id AND p.church_id=r.church_id JOIN books b ON b.id=r.book_id AND b.church_id=r.church_id WHERE r.church_id=? AND r.deleted_at IS NULL AND (CONCAT(p.first_names," ",p.last_names) LIKE ? OR CONCAT(p.last_names," ",p.first_names) LIKE ? OR r.record_number LIKE ? OR r.folio LIKE ?) ORDER BY r.id DESC LIMIT 100');$like='%'.$q.'%';$s->execute([$cid,$like,$like,$like,$like]);$rows=$s->fetchAll();
require 'layout.php';top('Actas');?>
<div class="page-head"><div><h1>Actas parroquiales</h1><p class="muted">Busca a la persona, elige el tipo y registra el acta.</p></div><a class="btn secondary" href="agregar.php">Volver a Agregar</a></div>
<?php if($msg):?><p class="notice"><?=e($msg)?></p><?php endif;?>
<?php if(!$books):?><p class="hint">Primero necesitas al menos un libro. <a href="books.php">Agregar libro</a></p><?php endif;?>
<section class="card compact-form"><h2><?=$edit?'Editar acta':'Nueva acta'?></h2><form method="post" id="recordForm"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="id" value="<?=e($edit['id']??0)?>"><input type="hidden" name="person_id" id="personId" value="<?=e($edit['person_id']??'')?>">
<label>Tipo de acta</label><select id="recordType" name="record_type" required><option value="">Seleccionar tipo</option><?php foreach($types as $t):?><option value="<?=e($t['slug'])?>"><?=e($t['name'])?></option><?php endforeach;?></select>
<label>Buscar persona</label><input id="personSearch" autocomplete="off" placeholder="Escribe al menos 2 letras del nombre o apellido" value="<?=e($selectedPerson?($selectedPerson['first_names'].' '.$selectedPerson['last_names']):'')?>"><div id="personResults" class="search-results" hidden></div><p id="personPicked" class="record-person-picked" <?=!$selectedPerson?'hidden':''?>><?= $selectedPerson?'Seleccionada: '.e($selectedPerson['first_names'].' '.$selectedPerson['last_names']):'' ?></p><p class="muted small">Si no aparece, puedes <button type="button" class="btn secondary" id="openNewPerson">registrarla aquí</button> sin salir del acta.</p>
<label>Libro correspondiente</label><select name="book_id" id="bookId" required><option value="">Selecciona primero el tipo de acta</option><?php foreach($books as $b):?><option data-type="<?=e($b['sacrament'])?>" data-start="<?=(int)$b['year_start']?>" data-end="<?=(int)$b['year_end']?>" value="<?=(int)$b['id']?>" <?=($edit['book_id']??null)==$b['id']?'selected':''?>><?=e('Libro '.$b['book_number'].(!empty($b['division'])?' · '.$b['division']:'').' · '.$b['year_start'].'–'.$b['year_end'])?></option><?php endforeach;?></select>
<div class="field-grid"><div><label>Número de acta</label><input name="record_number" value="<?=e($edit['record_number']??'')?>"></div><div><label>Folio</label><input name="folio" value="<?=e($edit['folio']??'')?>"></div></div><label>Fecha del acta (debe estar dentro del periodo del libro)</label><input id="sacramentDate" name="sacrament_date" type="date" required value="<?=e($edit['sacrament_date']??'')?>"><label>Padrinos o testigos</label><input name="godparents" value="<?=e($edit['godparents']??'')?>"><label>Observaciones</label><textarea name="notes"><?=e($edit['notes']??'')?></textarea><div class="form-actions"><button <?=!$books?'disabled':''?>>Guardar acta</button><?php if($edit):?><a class="btn secondary" href="records.php">Cancelar</a><?php endif;?></div></form></section>
<section class="card"><h2>Buscar actas</h2><form method="get"><input name="q" value="<?=e($q)?>" placeholder="Nombre, apellido, folio o número"><button>Buscar</button></form><div class="table-scroll"><table><tr><th>Persona</th><th>Tipo</th><th>Libro / Folio</th><th>Acciones</th></tr><?php foreach($rows as $r):?><tr><td><?=e($r['first_names'].' '.$r['last_names'])?></td><td><?=e($r['sacrament'])?></td><td><?=e($r['book_number'].' / '.$r['folio'])?></td><td><div class="actions"><a class="btn secondary" href="imprimir_acta.php?id=<?=(int)$r['id']?>">Imprimir</a><a class="btn secondary" href="records.php?edit=<?=(int)$r['id']?>">Editar</a><form method="post" onsubmit="return confirm('¿Enviar esta acta a la papelera?')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="danger">Eliminar</button></form></div></td></tr><?php endforeach;?></table></div></section>
<dialog id="newPersonDialog" class="ec-dialog"><form id="newPersonForm"><h2>Registrar persona</h2><p class="muted">Se creará un expediente para vincular todas sus actas.</p><label>Nombres</label><input name="first_names" required maxlength="180"><label>Apellidos</label><input name="last_names" required maxlength="180"><label>Fecha de nacimiento</label><input name="birth_date" type="date"><label>Lugar de nacimiento</label><input name="birthplace"><label>Nombre del padre</label><input name="father_name"><label>Nombre de la madre</label><input name="mother_name"><p id="newPersonMessage" class="notice" hidden></p><div class="form-actions"><button type="submit">Guardar persona</button><button type="button" class="btn secondary" id="closeNewPerson">Cancelar</button></div></form></dialog>
<script>
const type = document.getElementById('recordType');
const book = document.getElementById('bookId');
const dateInput = document.getElementById('sacramentDate');
const options = [...book.querySelectorAll('option[data-type]')];
const originalBook = <?=json_encode((string)($edit['book_id']??''))?>;
const originalType = <?=json_encode($edit?(array_values(array_filter($books,fn($b)=>(int)$b['id']===(int)$edit['book_id']))[0]['sacrament']??''):'')?>;
function updateDateRange(){
  const selected = book.selectedOptions[0];
  if (selected && selected.dataset.start) {
    dateInput.min = selected.dataset.start + '-01-01';
    dateInput.max = selected.dataset.end + '-12-31';
    dateInput.setCustomValidity('');
  } else {dateInput.removeAttribute('min');dateInput.removeAttribute('max');}
}
function filterBooks(){
  const old = book.value;
  book.innerHTML = '';
  const placeholder = document.createElement('option');
  placeholder.value = ''; placeholder.textContent = type.value ? 'Selecciona un libro' : 'Selecciona primero el tipo de acta';
  book.appendChild(placeholder);
  options.filter(o => o.dataset.type === type.value).forEach(o => book.appendChild(o));
  if ([...book.options].some(o => o.value === old)) book.value = old;
  updateDateRange();
}
type.addEventListener('change',filterBooks);
book.addEventListener('change',updateDateRange);
if(originalType){type.value=originalType;filterBooks();book.value=originalBook;updateDateRange();}
else filterBooks();
const search = document.getElementById('personSearch');
const results = document.getElementById('personResults');
const pid = document.getElementById('personId');
const picked = document.getElementById('personPicked');
const dialog=document.getElementById('newPersonDialog');document.getElementById('openNewPerson').onclick=()=>dialog.showModal();document.getElementById('closeNewPerson').onclick=()=>dialog.close();
document.getElementById('newPersonForm').addEventListener('submit',async e=>{e.preventDefault();const form=e.currentTarget,msg=document.getElementById('newPersonMessage');msg.hidden=true;const fd=new FormData(form);fd.append('csrf',<?=json_encode(csrf())?>);try{const res=await fetch('crear_persona.php',{method:'POST',body:fd,credentials:'same-origin'});const data=await res.json();if(!res.ok||!data.ok)throw Error(data.error||'No se pudo guardar');pid.value=data.id;search.value=data.name;picked.textContent='Seleccionada: '+data.name+' · Expediente #'+data.id;picked.hidden=false;results.hidden=true;dialog.close();form.reset()}catch(err){msg.hidden=false;msg.textContent=err.message}});

let controller, debounce;
const show = text => {results.hidden=false;results.replaceChildren();const p=document.createElement('p');p.className='muted';p.textContent=text;results.appendChild(p)};
search.addEventListener('input',()=>{
  pid.value='';picked.hidden=true;results.hidden=true;
  clearTimeout(debounce);if(controller)controller.abort();
  const q=search.value.trim();if(q.length<2)return;
  debounce=setTimeout(async()=>{
    controller=new AbortController();show('Buscando personas...');
    try {
      const r=await fetch('search_people.php?q='+encodeURIComponent(q),{signal:controller.signal,credentials:'same-origin',cache:'no-store'});
      if(!r.ok)throw new Error('HTTP '+r.status);
      const people=await r.json();
      if(!Array.isArray(people))throw new Error('Respuesta inesperada');
      results.replaceChildren();results.hidden=false;
      people.forEach(p=>{
        const btn=document.createElement('button');btn.type='button';btn.className='person-result';
        const full=[p.first_names,p.last_names].filter(Boolean).join(' ');
        const title=document.createElement('strong');title.textContent=full+' · Expediente #'+p.id;
        const info=document.createElement('span');info.className='muted small';
        info.textContent='Nacimiento: '+(p.birth_date||'No registrado')+' · Lugar: '+(p.birthplace||'No registrado')+' · Padre: '+(p.father_name||'No registrado')+' · Madre: '+(p.mother_name||'No registrado');
        btn.append(title,info);
        btn.addEventListener('click',()=>{pid.value=p.id;search.value=full;picked.textContent='Seleccionada: '+full+' · Expediente #'+p.id+' · Padres: '+(p.father_name||'No registrado')+' / '+(p.mother_name||'No registrado');picked.hidden=false;results.hidden=true});
        results.appendChild(btn);
      });
      if(!people.length)show('No hay coincidencias. Revisa que la persona esté registrada en esta parroquia.');
    }catch(err){if(err.name!=='AbortError')show('No se pudo consultar personas ('+err.message+'). Comprueba que search_people.php esté subido al servidor.');}
  },250);
});
document.getElementById('recordForm').addEventListener('submit',e=>{
  if(!pid.value||!book.value||!type.value){e.preventDefault();alert('Selecciona un tipo, una persona existente y un libro.');return;}
  if(!dateInput.value||!dateInput.checkValidity()){e.preventDefault();dateInput.reportValidity();return;}
});
</script>
<?php bottom();
