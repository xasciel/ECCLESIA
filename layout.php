<?php
function top($title){
    $admin=me()['role']==='superadmin';
    $current=basename($_SERVER['SCRIPT_NAME']);
    $addPages=['agregar.php','records.php'];
    $openAdd=in_array($current,$addPages,true);
    ?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> | Ecclesia</title><link rel="stylesheet" href="assets/style.css">
<style>
/* El botón Agregar recibe el mismo resaltado y transición que los enlaces. */
.side-nav .ecclesia-toggle{box-sizing:border-box;display:flex;align-items:center;justify-content:space-between;width:100%;border:0;border-left:3px solid transparent;border-radius:12px;background:transparent;color:inherit;font:inherit;text-align:left;cursor:pointer;padding:14px 18px;transition:background-color .25s ease,border-color .25s ease,color .25s ease;}
.side-nav .ecclesia-toggle:hover,.side-nav .ecclesia-toggle:focus-visible,.side-nav .ecclesia-toggle[aria-expanded="true"]{background:rgba(255,255,255,.12);border-left-color:#d6b878;outline:none;}
.side-nav .ecclesia-toggle .chevron{display:inline-block;transition:transform .3s ease;}
.side-nav .ecclesia-toggle[aria-expanded="true"] .chevron{transform:rotate(180deg);}
/* No usar hidden/display:none: impediría animar la apertura y el cierre. */
.side-nav .ecclesia-submenu{display:grid;grid-template-rows:0fr;opacity:0;visibility:hidden;transition:grid-template-rows .3s ease,opacity .25s ease,visibility .3s;}
.side-nav .ecclesia-submenu.is-open{grid-template-rows:1fr;opacity:1;visibility:visible;}
.side-nav .ecclesia-submenu-inner{overflow:hidden;min-height:0;}
.side-nav .ecclesia-submenu a{display:block;}
@media(prefers-reduced-motion:reduce){.side-nav .ecclesia-toggle,.side-nav .ecclesia-toggle .chevron,.side-nav .ecclesia-submenu{transition:none;}}
</style>
</head><body class="app-body"><aside class="sidebar"><div class="side-brand">✝ <span>ECCLESIA</span><small>Archivo sacramental</small></div><div class="side-label">MENÚ PRINCIPAL</div><nav class="side-nav">
<?php if($admin): ?>
<a class="<?=$current==='dashboard.php'?'current':''?>" href="dashboard.php">Inicio</a>
<a class="<?=$current==='approvals.php'?'current':''?>" href="approvals.php">Iglesias</a>
<a class="<?=$current==='plans.php'?'current':''?>" href="plans.php">Planes y membresías</a>
<?php else: ?>
<a class="<?=$current==='dashboard.php'?'current':''?>" href="dashboard.php">Inicio</a>
<button type="button" class="ecclesia-toggle" id="toggleAgregar" aria-expanded="<?=$openAdd?'true':'false'?>" aria-controls="submenuAgregar"><span>Agregar</span><span class="chevron" aria-hidden="true">⌄</span></button>
<div class="ecclesia-submenu <?=$openAdd?'is-open':''?>" id="submenuAgregar"><div class="side-subnav ecclesia-submenu-inner">
<a class="<?=$current==='records.php'?'current':''?>" href="records.php">↳ Acta</a>
<a href="books.php">↳ Libro / sublibro</a>
</div></div>
<a class="<?=$current==='books.php'||$current==='ver_libro.php'?'current':''?>" href="books.php">Libros</a>
<a class="<?=$current==='templates.php'?'current':''?>" href="templates.php">Formatos</a>
<?php endif; ?>
</nav><div class="side-bottom"><div><?=e(me()['name'])?></div><small><?=$admin?'Administrador general':'Personal parroquial'?></small><a href="logout.php">Cerrar sesión</a></div></aside><div class="workspace"><header class="topbar"><div class="top-title"><?=e($title)?></div><div class="top-user"><?=e(me()['name'])?></div></header><main class="app-main">
<script>
(function(){const btn=document.getElementById('toggleAgregar'),sub=document.getElementById('submenuAgregar');if(!btn||!sub)return;btn.addEventListener('click',function(){const expanded=btn.getAttribute('aria-expanded')==='true';btn.setAttribute('aria-expanded',String(!expanded));sub.classList.toggle('is-open',!expanded);});})();
</script>
<?php }
function bottom(){echo '</main></div></body></html>';}
