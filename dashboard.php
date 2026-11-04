<?php
require 'bootstrap.php';require_login();
$admin=me()['role']==='superadmin';
require 'layout.php';
if($admin){
 $counts=['Iglesias'=>$db->query('SELECT COUNT(*) FROM churches')->fetchColumn(),'Por aprobar'=>$db->query("SELECT COUNT(*) FROM churches WHERE status='pending'")->fetchColumn(),'Usuarios'=>$db->query('SELECT COUNT(*) FROM users')->fetchColumn()];
 try{$counts['Planes']=$db->query('SELECT COUNT(*) FROM subscription_plans')->fetchColumn();}catch(Throwable $e){$counts['Planes']='—';}
 top('Panel principal');?>
 <div class="page-head"><div><h1>Panel general</h1><p class="muted">Administra iglesias, accesos y membresías.</p></div></div>
 <div class="grid stats-grid"><?php foreach($counts as $label=>$value):?><section class="card stat-card"><div class="metric"><?=e($value)?></div><div class="muted"><?=e($label)?></div></section><?php endforeach;?></div>
 <section class="card"><h2>Administración</h2><div class="quick-actions"><a class="quick-link" href="approvals.php">Administrar iglesias</a><a class="quick-link" href="plans.php">Planes y membresías</a></div></section>
 <?php bottom();exit;
}
$counts=[];
foreach([
 'Actas'=>['SELECT COUNT(*) FROM records WHERE church_id=? AND deleted_at IS NULL','records.php'],
 'Libros'=>['SELECT COUNT(*) FROM books WHERE church_id=?','books.php'],
 'Personas'=>['SELECT COUNT(*) FROM people WHERE church_id=?','dashboard.php#searchTitle'],
] as $label=>$config){
 $statement=$db->prepare($config[0]);$statement->execute([(int)me()['church_id']]);
 $counts[$label]=['value'=>$statement->fetchColumn(),'href'=>$config[1]];
}
top('Inicio');?>
<section class="hero-panel"><div><p class="eyebrow">BIENVENIDO A TU ARCHIVO</p><h1>Todo comienza con una historia.</h1><p>Encuentra actas, organiza tus libros y conserva la memoria de tu parroquia.</p></div><a class="btn" data-permission="create" href="records.php">＋ Registrar un acta</a></section>
<div class="grid stats-grid" aria-label="Resumen de la parroquia"><?php foreach($counts as $label=>$stat):?><a class="card stat-card stat-link" href="<?=e($stat['href'])?>"><div class="stat-label"><?=e($label)?><span aria-hidden="true">↗</span></div><div class="metric"><?=e($stat['value'])?></div></a><?php endforeach;?></div>
<section aria-labelledby="quickTitle"><div class="section-title"><h2 id="quickTitle">¿Qué quieres hacer hoy?</h2></div><div class="quick-actions">
<a class="quick-link" data-permission="create" href="records.php"><span class="action-icon" aria-hidden="true">＋</span><span><strong>Registrar un acta</strong><small>Vincula una persona con su libro.</small></span></a>
<a class="quick-link" href="books.php"><span class="action-icon" aria-hidden="true">▤</span><span><strong>Consultar libros</strong><small>Explora y filtra tu archivo.</small></span></a>
<a class="quick-link" data-permission="create" href="agregar.php"><span class="action-icon" aria-hidden="true">＋</span><span><strong>Crear un libro</strong><small>Organiza un nuevo periodo.</small></span></a>
<a class="quick-link" href="templates.php"><span class="action-icon" aria-hidden="true">▧</span><span><strong>Personalizar formatos</strong><small>Prepara la presentación de tus actas.</small></span></a>
</div></section>
<section class="card" style="margin-top:24px" aria-labelledby="searchTitle"><p class="eyebrow">ENCUENTRA LO QUE NECESITAS</p><h2 id="searchTitle">Buscar en todas las actas</h2>
<form id="globalSearchForm"><label for="busquedaGeneral">Nombre, apellido, padres, año, libro o folio</label><div class="search-toolbar"><input id="busquedaGeneral" name="q" type="search" autocomplete="off" placeholder="Ej. María López, 2018…" aria-controls="resultadosActas"><button type="submit">Buscar</button><button type="button" class="secondary" id="clearSearch">Limpiar</button></div></form>
<p class="search-help">Los resultados se actualizan mientras escribes. Puedes combinar varios datos.</p>
<div id="resultadosActas" aria-live="polite" aria-atomic="false"><p class="muted">Cargando actas…</p></div>
<noscript><p class="notice">Activa JavaScript para buscar aquí o <a href="records.php">consulta las actas</a>.</p></noscript>
</section>
<script>
(()=> {
 const input=document.getElementById('busquedaGeneral'),out=document.getElementById('resultadosActas');
 let timer,controller,requestId=0;
 function feedback(message,loading=false){
  out.replaceChildren();const box=document.createElement('div');box.className='search-feedback';
  if(loading){const spinner=document.createElement('span');spinner.className='loading-spinner';spinner.setAttribute('aria-hidden','true');box.append(spinner);}
  const p=document.createElement('p');p.textContent=message;box.append(p);
  if(!loading){const retry=document.createElement('button');retry.type='button';retry.className='secondary';retry.textContent='Reintentar';retry.addEventListener('click',load);box.append(retry);}
  out.append(box);
 }
 async function load(){
  clearTimeout(timer);if(controller)controller.abort();
  const thisRequest=++requestId;controller=new AbortController();
  out.setAttribute('aria-busy','true');feedback('Buscando actas…',true);
  try{
   const res=await fetch('buscar_actas_inicio.php?q='+encodeURIComponent(input.value.trim()),{signal:controller.signal,credentials:'same-origin'});
   if(!res.ok)throw new Error('HTTP '+res.status);
   const html=await res.text();if(thisRequest===requestId)out.innerHTML=html;
  }catch(err){if(err.name!=='AbortError'&&thisRequest===requestId)feedback('No fue posible consultar las actas. Comprueba tu conexión e inténtalo de nuevo.');}
  finally{if(thisRequest===requestId)out.removeAttribute('aria-busy');}
 }
 input.addEventListener('input',()=>{clearTimeout(timer);if(controller)controller.abort();++requestId;timer=setTimeout(load,250);});
 document.getElementById('globalSearchForm').addEventListener('submit',e=>{e.preventDefault();load();});
 document.getElementById('clearSearch').addEventListener('click',()=>{input.value='';input.focus();load();});
 load();
})();
</script>
<?php bottom();
