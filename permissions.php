<?php
/* El primer administrador de cada parroquia conserva la titularidad.
 * Los colaboradores se crean con el rol compatible church_admin, pero nunca
 * heredan sus privilegios: se comprueban titularidad y permisos por separado.
 */
function permission_labels(): array {
    return ['create'=>'Crear libros, actas y personas','edit'=>'Editar libros y actas','delete'=>'Eliminar libros y archivar actas','print'=>'Imprimir actas','templates'=>'Administrar formatos y tipos de acta'];
}
function church_owner(): bool {
    global $db;
    static $ownerId;
    if(!me() || me()['role']!=='church_admin')return false;
    if($ownerId===null){
        $s=$db->prepare("SELECT id FROM users WHERE church_id=? AND role='church_admin' ORDER BY id LIMIT 1");
        $s->execute([(int)me()['church_id']]);$ownerId=(int)$s->fetchColumn();
    }
    return $ownerId===(int)me()['id'];
}
function permissions_ready(): bool {
    global $db;
    try{$db->query('SELECT user_id FROM ecclesia_user_permissions LIMIT 1');return true;}
    catch(PDOException $ex){if(($ex->errorInfo[1]??null)===1146 || $ex->getCode()==='42S02')return false;throw $ex;}
}
function ecclesia_can(string $permission): bool {
    global $db;
    if(!me())return false;
    if(me()['role']==='superadmin')return false;
    if(church_owner())return true;
    if($permission==='users')return false;
    if($permission==='read')return true;
    if(!isset(permission_labels()[$permission]))return false;
    static $permissions;
    if($permissions===null){
        $permissions=[];
        if(permissions_ready()){
            $s=$db->prepare('SELECT permissions_json FROM ecclesia_user_permissions WHERE user_id=? AND church_id=?');
            $s->execute([(int)me()['id'],(int)me()['church_id']]);
            $permissions=json_decode($s->fetchColumn()?:'[]',true);
            if(!is_array($permissions))$permissions=[];
        }
    }
    return in_array($permission,$permissions,true);
}
function require_permission(string $permission): void {
    require_church();
    if(ecclesia_can($permission))return;
    http_response_code(403);
    if(strpos($_SERVER['HTTP_ACCEPT']??'', 'application/json')!==false || in_array(basename($_SERVER['SCRIPT_NAME']),['crear_persona.php','guardar_fondo.php'],true)){
        header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>'No tienes permiso para realizar esta acción.']);
    }else{echo 'No tienes permiso para realizar esta acción. Solicita acceso al responsable de tu parroquia.';}
    exit;
}
