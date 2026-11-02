<?php
function ecclesia_campos_acta(): array {
 return ['iglesia'=>'Nombre de parroquia','direccion'=>'Dirección','tipo'=>'Tipo de acta','numero'=>'Número de acta','folio'=>'Folio','libro'=>'Libro','division'=>'Sublibro','periodo'=>'Periodo','fecha_acta'=>'Fecha del acta','nombre'=>'Nombre completo','nacimiento'=>'Fecha de nacimiento','lugar'=>'Lugar de nacimiento','padre'=>'Padre','madre'=>'Madre','padrinos'=>'Padrinos / testigos','observaciones'=>'Observaciones'];
}
function ecclesia_posiciones($json): array {
 $raw=json_decode((string)$json,true);if(!is_array($raw))return [];
 $out=[];$allowed=ecclesia_campos_acta();
 foreach($raw as $key=>$v){if(!isset($allowed[$key])||!is_array($v))continue;
  $out[$key]=['x'=>max(0,min(98,(float)($v['x']??0))),'y'=>max(0,min(98,(float)($v['y']??0))),'width'=>max(5,min(100,(float)($v['width']??40))),'size'=>max(8,min(40,(int)($v['size']??14)))];
 }
 return $out;
}
