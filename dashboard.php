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
top('Inicio');?>
<div class="page-head"><div><h1>Archivo parroquial</h1><p class="muted">Consulta todas las actas. Escribe un nombre, apellido, padres, año, libro o folio para reducir las coincidencias automáticamente.</p></div><a class="btn" href="records.php">+ Agregar acta</a></div>
<section class="card">
<label for="busquedaGeneral">Buscar en todas las actas</label>
<input id="busquedaGeneral" type="search" autocomplete="off" placeholder="Ej. nombre del padre, año o folio" aria-controls="resultadosActas">
<div id="resultadosActas" aria-live="polite"><p class="muted">Cargando actas...</p></div>
</section>
<script>
(()=> {
 const input=document.getElementById('busquedaGeneral'),out=document.getElementById('resultadosActas');
 let timer,controller;
 async function load(){
  if(controller)controller.abort();
  controller=new AbortController();
  out.setAttribute('aria-busy','true');
  try{
   const res=await fetch('buscar_actas_inicio.php?q='+encodeURIComponent(input.value.trim()),{signal:controller.signal,credentials:'same-origin'});
   if(!res.ok)throw new Error('HTTP '+res.status);
   out.innerHTML=await res.text();
  }catch(err){if(err.name!=='AbortError')out.textContent='No fue posible consultar las actas. Intenta nuevamente.';}
  finally{out.removeAttribute('aria-busy');}
 }
 input.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(load,250);});
 load();
})();
</script>
<?php bottom();
