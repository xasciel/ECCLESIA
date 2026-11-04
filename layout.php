<?php
function top($title){
    $admin=me()['role']==='superadmin';
    $current=basename($_SERVER['SCRIPT_NAME']);
    $links=$admin
        ? ['dashboard.php'=>['⌂','Inicio'],'approvals.php'=>['✝','Iglesias'],'plans.php'=>['◇','Planes y membresías']]
        : ['dashboard.php'=>['⌂','Inicio'],'records.php'=>['＋','Actas'],'books.php'=>['▤','Libros'],'templates.php'=>['▧','Formatos']];
    if(!$admin && ecclesia_can('users'))$links['team.php']=['♧','Equipo y permisos'];
    $active=$current==='ver_libro.php'||$current==='agregar.php'?'books.php':$current;
    $cssVersion=filemtime(__DIR__.'/assets/style.css');
    $jsVersion=filemtime(__DIR__.'/assets/app.js');
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> | Ecclesia</title>
<link rel="stylesheet" href="assets/style.css?v=<?=$cssVersion?>"><script src="assets/app.js?v=<?=$jsVersion?>" defer></script>
</head><body class="app-body" data-page="<?=e($current)?>" <?php foreach(array_keys(permission_labels()) as $permission):?>data-can-<?=e($permission)?>="<?=ecclesia_can($permission)?'true':'false'?>" <?php endforeach;?>>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<aside class="sidebar" id="appSidebar" aria-label="Menú principal">
<div class="side-brand"><span aria-hidden="true">✝</span> <span>ECCLESIA</span><small>Archivo sacramental</small></div>
<button class="mobile-only sidebar-close secondary" type="button" data-menu-close>Cerrar menú <span aria-hidden="true">×</span></button>
<div class="side-label">TU ESPACIO DE TRABAJO</div><nav class="side-nav" aria-label="Navegación principal">
<?php foreach($links as $href=>$item):?>
<a class="<?=$active===$href?'current':''?>" href="<?=e($href)?>" <?=$active===$href?'aria-current="page"':''?>><span class="nav-icon" aria-hidden="true"><?=e($item[0])?></span><?=e($item[1])?></a>
<?php endforeach;?>
<?php if(!$admin && ecclesia_can('create')):?><a class="nav-create" href="agregar.php"><span class="nav-icon" aria-hidden="true">＋</span>Crear libro</a><?php endif;?>
</nav>
<div class="side-bottom"><strong><?=e(me()['name'])?></strong><small><?=$admin?'Administrador general':(church_owner()?'Responsable parroquial':'Colaborador parroquial')?></small><a class="logout-link" href="logout.php">Cerrar sesión <span aria-hidden="true">↗</span></a></div>
</aside>
<button class="nav-overlay" type="button" data-menu-close aria-label="Cerrar menú" hidden></button>
<div class="workspace"><header class="topbar"><div class="topbar-start"><button class="mobile-only secondary" id="menuToggle" type="button" aria-expanded="false" aria-controls="appSidebar" aria-label="Abrir menú">☰</button><div><div class="top-eyebrow">ECCLESIA / ARCHIVO SACRAMENTAL</div><div class="top-title"><?=e($title)?></div></div></div>
<div class="topbar-actions"><button class="btn secondary" type="button" id="openShortcuts"><span aria-hidden="true">⌕</span> Ir a… <kbd>Ctrl K</kbd></button><span class="user-avatar" title="<?=e(me()['name'])?>" aria-label="Usuario: <?=e(me()['name'])?>"><?=e(preg_match('/^./us',me()['name'],$initial)?$initial[0]:'E')?></span></div></header>
<main class="app-main" id="contenido" tabindex="-1">
<?php }
function bottom(){ ?>
</main><footer class="app-footer">ECCLESIA <span>Tu archivo parroquial, en un solo lugar.</span></footer></div>
<dialog class="ec-dialog shortcuts-dialog" id="shortcutsDialog" aria-labelledby="shortcutsTitle"><div class="section-title"><h2 id="shortcutsTitle">¿A dónde quieres ir?</h2><button class="btn secondary" id="closeShortcuts" type="button" aria-label="Cerrar accesos rápidos">×</button></div><p class="muted">Elige una sección para continuar.</p><div class="shortcut-list">
<?php if(me()['role']==='superadmin'):?>
<a href="dashboard.php">Inicio <span aria-hidden="true">→</span></a><a href="approvals.php">Administrar iglesias <span aria-hidden="true">→</span></a><a href="plans.php">Planes y membresías <span aria-hidden="true">→</span></a>
<?php else:?>
<a data-permission="create" href="records.php">＋ Registrar un acta <span aria-hidden="true">→</span></a><a href="books.php">▤ Consultar libros <span aria-hidden="true">→</span></a><a data-permission="create" href="agregar.php">＋ Crear un libro <span aria-hidden="true">→</span></a><a href="templates.php">▧ Editar formatos <span aria-hidden="true">→</span></a><?php if(ecclesia_can('users')):?><a href="team.php">♧ Equipo y permisos <span aria-hidden="true">→</span></a><?php endif;?><a href="dashboard.php">⌕ Buscar actas <span aria-hidden="true">→</span></a>
<?php endif;?></div><p class="muted small">Esc para cerrar · Ctrl/Cmd + K para abrir</p></dialog>
</body></html>
<?php }
