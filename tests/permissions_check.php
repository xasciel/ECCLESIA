<?php
/* Ejecutar cada escenario en un proceso nuevo: php tests/permissions_check.php owner */
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE users(id INTEGER PRIMARY KEY,church_id INTEGER,role TEXT); INSERT INTO users VALUES(1,10,'church_admin'),(2,10,'church_admin'),(3,20,'church_admin'),(4,10,'church_admin')");
$db->exec('CREATE TABLE ecclesia_user_permissions(user_id INTEGER PRIMARY KEY,church_id INTEGER,permissions_json TEXT)');
$db->exec("INSERT INTO ecclesia_user_permissions VALUES(2,10,'[\"edit\",\"print\"]'),(4,20,'[\"delete\"]')");
$scenario=$argv[1]??'owner';
$fixtures=['owner'=>[1,10,'church_admin'],'collaborator'=>[2,10,'church_admin'],'other_church_owner'=>[3,20,'church_admin'],'tenant_mismatch'=>[4,10,'church_admin'],'superadmin'=>[9,null,'superadmin']];
if(!isset($fixtures[$scenario]))exit(2);
[$id,$church,$role]=$fixtures[$scenario];
$_SESSION['user']=['id'=>$id,'church_id'=>$church,'role'=>$role];
function me(){return $_SESSION['user']??null;}
require __DIR__.'/../permissions.php';
function expect($condition,$message){if(!$condition){fwrite(STDERR,$message."\n");exit(1);}}
if(in_array($scenario,['owner','other_church_owner'],true)){
 foreach(['users','create','edit','delete','print','templates'] as $p)expect(ecclesia_can($p),'El responsable debe conservar '.$p);
}else{
 expect(!church_owner(),'Un colaborador nunca debe ser responsable');
 expect(!ecclesia_can('users'),'No debe administrar su propio acceso');
 expect(!ecclesia_can('delete'),'No debe heredar eliminación del rol church_admin');
 if($scenario==='collaborator'){
  expect(ecclesia_can('edit')&&ecclesia_can('print'),'Deben respetarse permisos explícitos');
  expect(!ecclesia_can('create')&&!ecclesia_can('templates'),'No debe obtener permisos no concedidos');
 }else{expect(!ecclesia_can('edit')&&!ecclesia_can('print'),'No debe usar permisos de otra parroquia');}
}
echo "OK: $scenario\n";
