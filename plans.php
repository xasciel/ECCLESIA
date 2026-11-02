<?php
require 'bootstrap.php'; require_login();
if(me()['role']!=='superadmin'){http_response_code(403);exit('Acceso denegado');}
$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=$_POST['action']??'';
 try{
  if($action==='save'){
   $id=(int)($_POST['id']??0);$name=trim($_POST['name']??'');
   $price=filter_var($_POST['price']??null,FILTER_VALIDATE_FLOAT);
   $days=filter_var($_POST['duration_days']??null,FILTER_VALIDATE_INT);
   $users=filter_var($_POST['max_users']??null,FILTER_VALIDATE_INT);
   $records=trim($_POST['max_records']??'');
   if($name===''||mb_strlen($name)>100||$price===false||$price<0||$days===false||$days<1||$users===false||$users<1||($records!==''&&(!ctype_digit($records)||(int)$records<1)))throw new Exception('Revisa los datos del plan.');
   $params=[$name,trim($_POST['description']??''),$price,$days,$users,$records===''?null:(int)$records,(int)isset($_POST['is_free']),(int)isset($_POST['is_active'])];
   if($id){$s=$db->prepare('UPDATE subscription_plans SET name=?,description=?,price=?,duration_days=?,max_users=?,max_records=?,is_free=?,is_active=? WHERE id=?');$s->execute([...$params,$id]);}
   else{$s=$db->prepare('INSERT INTO subscription_plans(name,description,price,duration_days,max_users,max_records,is_free,is_active) VALUES(?,?,?,?,?,?,?,?)');$s->execute($params);}
   header('Location: plans.php?ok=1');exit;
  }
  if($action==='assign'){
   $church=(int)($_POST['church_id']??0);$plan=(int)($_POST['plan_id']??0);$start=$_POST['starts_at']??'';$end=$_POST['ends_at']??'';$trial=$_POST['trial_ends_at']??'';
   $dateOk=fn($d)=>preg_match('/^\d{4}-\d{2}-\d{2}$/',$d)&&date('Y-m-d',strtotime($d))===$d;
   if(!$dateOk($start)||($end!==''&&!$dateOk($end))||($trial!==''&&!$dateOk($trial))||($end!==''&&$end<$start)||($trial!==''&&$trial<$start))throw new Exception('Revisa las fechas.');
   $s=$db->prepare("SELECT COUNT(*) FROM churches WHERE id=? AND status='approved'");$s->execute([$church]);if(!$s->fetchColumn())throw new Exception('Primero debes aprobar la iglesia.');
   $s=$db->prepare('SELECT COUNT(*) FROM subscription_plans WHERE id=? AND is_active=1');$s->execute([$plan]);if(!$s->fetchColumn())throw new Exception('Selecciona un plan activo.');
   $db->beginTransaction();
   $s=$db->prepare("UPDATE church_subscriptions SET status='cancelled' WHERE church_id=? AND status IN ('active','trial')");$s->execute([$church]);
   $s=$db->prepare('INSERT INTO church_subscriptions(church_id,plan_id,starts_at,ends_at,trial_ends_at,status,notes,assigned_by) VALUES(?,?,?,?,?,?,?,?)');
   $s->execute([$church,$plan,$start,$end?:null,$trial?:null,$trial?'trial':'active',trim($_POST['notes']??''),me()['id']]);
   $db->commit();header('Location: plans.php?assigned=1');exit;
  }
 }catch(Throwable $ex){if($db->inTransaction())$db->rollBack();$notice=$ex instanceof PDOException?'No se pudo guardar. Revisa que hayas importado la migración SQL.':$ex->getMessage();}
}
$plans=$db->query('SELECT * FROM subscription_plans ORDER BY is_active DESC,price ASC')->fetchAll();
$churches=$db->query("SELECT id,name FROM churches WHERE status='approved' ORDER BY name")->fetchAll();
$subs=$db->query('SELECT s.*,c.name church_name,p.name plan_name FROM church_subscriptions s JOIN churches c ON c.id=s.church_id JOIN subscription_plans p ON p.id=s.plan_id ORDER BY s.id DESC LIMIT 80')->fetchAll();
$edit=null;if(isset($_GET['edit'])){foreach($plans as $plan){if($plan['id']==(int)$_GET['edit']){$edit=$plan;break;}}}
require 'layout.php';top('Planes y membresías');
?>
<div class="page-head"><div><div class="eyebrow">ADMINISTRACIÓN COMERCIAL</div><h1>Planes y membresías</h1><p class="muted">Crea planes, ofrece pruebas y asígnalos a las iglesias aprobadas.</p></div><a class="btn secondary" href="approvals.php">Ver iglesias</a></div>
<?php if(isset($_GET['ok'])):?><div class="notice">Plan guardado correctamente.</div><?php endif;?>
<?php if(isset($_GET['assigned'])):?><div class="notice">Membresía asignada correctamente.</div><?php endif;?>
<?php if($notice):?><div class="notice"><?=e($notice)?></div><?php endif;?>
<div class="two-col"><section class="card"><h2><?=$edit?'Editar plan':'Crear nuevo plan'?></h2><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e($edit['id']??0)?>"><label>Nombre del plan</label><input name="name" maxlength="100" required placeholder="Parroquial" value="<?=e($edit['name']??'')?>"><label>Descripción</label><input name="description" maxlength="255" value="<?=e($edit['description']??'')?>"><div class="field-grid"><div><label>Precio (MXN)</label><input type="number" step="0.01" min="0" name="price" required value="<?=e($edit['price']??'0')?>"></div><div><label>Duración (días)</label><input type="number" min="1" name="duration_days" required value="<?=e($edit['duration_days']??30)?>"></div><div><label>Máximo de usuarios</label><input type="number" min="1" name="max_users" required value="<?=e($edit['max_users']??1)?>"></div><div><label>Límite de actas (vacío = sin límite)</label><input type="number" min="1" name="max_records" value="<?=e($edit['max_records']??'')?>"></div></div><label class="checkline"><input type="checkbox" name="is_free" <?=!empty($edit['is_free'])?'checked':''?>> Plan gratuito</label><label class="checkline"><input type="checkbox" name="is_active" <?=!$edit||$edit['is_active']?'checked':''?>> Disponible para asignar</label><button>Guardar plan</button></form></section>
<section class="card"><h2>Asignar membresía</h2><p class="muted">Selecciona la iglesia y el plan. Una nueva asignación cancela la membresía activa anterior.</p><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="action" value="assign"><label>Iglesia aprobada</label><select name="church_id" required><option value="">Seleccionar iglesia</option><?php foreach($churches as $c):?><option value="<?=$c['id']?>"><?=e($c['name'])?></option><?php endforeach;?></select><label>Plan</label><select name="plan_id" required><option value="">Seleccionar plan</option><?php foreach($plans as $p):if(!$p['is_active'])continue;?><option value="<?=$p['id']?>"><?=e($p['name'])?> — $<?=e($p['price'])?></option><?php endforeach;?></select><div class="field-grid"><div><label>Fecha de inicio</label><input type="date" name="starts_at" required value="<?=date('Y-m-d')?>"></div><div><label>Vencimiento (opcional)</label><input type="date" name="ends_at"></div></div><label>Fin de prueba gratuita (opcional)</label><input type="date" name="trial_ends_at"><label>Notas internas</label><input name="notes" maxlength="255" placeholder="Cortesía, pago recibido, etc."><button <?=!$churches||!$plans?'disabled':''?>>Asignar plan a iglesia</button></form></section></div>
<section class="card"><div class="section-title"><h2>Planes creados</h2><span class="muted"><?=count($plans)?> planes</span></div><div class="table-scroll"><table><thead><tr><th>Nombre</th><th>Precio</th><th>Duración</th><th>Usuarios</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($plans as $p):?><tr><td><strong><?=e($p['name'])?></strong></td><td>$<?=number_format((float)$p['price'],2)?></td><td><?=e($p['duration_days'])?> días</td><td><?=e($p['max_users'])?></td><td><span class="status-pill"><?= $p['is_active']?'Activo':'Inactivo' ?></span></td><td><a class="btn secondary" href="plans.php?edit=<?=$p['id']?>">Editar</a></td></tr><?php endforeach;?></tbody></table></div></section>
<section class="card"><h2>Historial de asignaciones</h2><div class="table-scroll"><table><thead><tr><th>Iglesia</th><th>Plan</th><th>Inicio</th><th>Vencimiento</th><th>Prueba hasta</th><th>Estado</th></tr></thead><tbody><?php foreach($subs as $s):?><tr><td><?=e($s['church_name'])?></td><td><?=e($s['plan_name'])?></td><td><?=e($s['starts_at'])?></td><td><?=e($s['ends_at']??'Sin fecha')?></td><td><?=e($s['trial_ends_at']??'—')?></td><td><span class="status-pill"><?=e($s['status'])?></span></td></tr><?php endforeach;?></tbody></table></div></section>
<?php bottom();
