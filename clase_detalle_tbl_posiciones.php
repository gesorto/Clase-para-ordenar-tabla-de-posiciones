<?php
require_once("conexion.php"); 
require_once("claseGrupo.php");

class detalle_tbl_posiciones{
    private $array_ordenado = array();//Aquí guardamos la data ordenada
    private $array_desordenado = array();//Con este trabaja el metodo ordenar_por_pts
    private $array_desordenado_2 = array();//Con este trabaja el metodo ordenar_por_dg  
    private $array_desordenado_3 = array();//Con este trabaja el metodo ordenar_por_gf
    private $array_desordenado_4 = array();//Con este trabaja el metodo ordenar_por_gc
    private $array_ordenado_final = array();

    function obtener_tbl_posiciones(){
        $grupo = new grupo();
        $resultado_grupo = $grupo -> consultarGrupo();
        $info_grupo = array();
        while ($row = mysqli_fetch_array($resultado_grupo,MYSQLI_ASSOC)) {
            $info_grupo[] = $row;
        }
        $conexion = new conexion();
        for ($n=0; $n < count($info_grupo); $n++) {
            $query = "SELECT `ID_TABLA_POSICIONES`, `NOMBRE_EQUIPO`, `PARTIDOS_JUGADOS`, `PARTIDOS_GANADOS`, `PARTIDOS_EMPATADOS`, `PARTIDOS_PERDIDOS`, `GOLES_FAVOR`, `GOLES_CONTRA` FROM `DETALLE_TABLA_POSICIONES` INNER JOIN `EQUIPO` ON `EQUIPO`.`ID_EQUIPO` = `DETALLE_TABLA_POSICIONES`.`ID_EQUIPO` WHERE `ID_TABLA_POSICIONES` = (SELECT `ID_TABLA_POSICIONES` FROM `TABLA_POSICIONES` WHERE `ID_GRUPO` = " . $info_grupo[$n]["ID_GRUPO"] . ");";
            $resultado = $conexion->consultaCompuesta($query);
            while ($row = mysqli_fetch_array($resultado,MYSQLI_ASSOC)) {
                $this -> array_desordenado[] = $row;
            }
            if (count($this -> array_desordenado) > 0) {
                $this -> agregar_diferencia();
                if ($info_grupo[$n]["GENERO_GRUPO"] == 1) {
                    $genero = "M";
                    $genero_final = $genero . $info_grupo[$n]["IDENTIFICADOR_GRUPO"];
                    $this -> array_ordenado_final[][$genero_final] = $this -> array_ordenado;
                }elseif($info_grupo[$n]["GENERO_GRUPO"] == 0){
                    $genero = "F";
                    $genero_final = $genero.$info_grupo[$n]["IDENTIFICADOR_GRUPO"];
                    $this -> array_ordenado_final[][$genero_final] = $this -> array_ordenado;
                }
                unset($this->array_ordenado);
                $this->array_ordenado = array();
            }
        }
        return $this -> array_ordenado_final;
    }

    //Se construyó otra función para poder generar el PDF
    function obtener_tbl_posiciones_por_grupo($id){
        $conexion = new conexion();
        $query = "SELECT `NOMBRE_EQUIPO`, `PARTIDOS_JUGADOS`, `PARTIDOS_GANADOS`, `PARTIDOS_EMPATADOS`, `PARTIDOS_PERDIDOS`, `GOLES_FAVOR`, `GOLES_CONTRA` FROM `DETALLE_TABLA_POSICIONES` INNER JOIN `EQUIPO` ON `EQUIPO`.`ID_EQUIPO` = `DETALLE_TABLA_POSICIONES`.`ID_EQUIPO` WHERE `ID_TABLA_POSICIONES` = " . $id . ";";
        $resultado = $conexion->consultaCompuesta($query);
        while ($row = mysqli_fetch_array($resultado,MYSQLI_ASSOC)) {
            $this -> array_desordenado[] = $row;
        }
        if (count($this -> array_desordenado) > 0) {
            $this -> agregar_diferencia();
        }
        return $this -> array_ordenado;
    }
    
	private function agregar_diferencia(){
        for ($i=0; $i < count($this -> array_desordenado); $i++) { 
            $this -> array_desordenado[$i]["DIFERENCIA_GOLES"] = $this -> array_desordenado[$i]["GOLES_FAVOR"] - $this -> array_desordenado[$i]["GOLES_CONTRA"]; 
        }
        $this -> agregar_puntos();
    }

    private function agregar_puntos(){
        for ($i=0; $i < count($this -> array_desordenado); $i++) { 
            $pts_por_pg = 0;
            $pts_por_pe = 0;
            $pts_por_pg = $this -> array_desordenado[$i]["PARTIDOS_GANADOS"] * 3;
            $pts_por_pe = $this -> array_desordenado[$i]["PARTIDOS_EMPATADOS"] * 1;
            $this -> array_desordenado[$i]["PUNTOS"] = $pts_por_pg + $pts_por_pe;        
        }    
        $this -> ordenar_por_pts();
    }

    private function ordenar_por_pts(){
        $variable_control = 0;
        do{
            $alto = count($this -> array_desordenado);
            $valor_identificador = $alto -1;
            $puntos = $this -> array_desordenado[$valor_identificador]["PUNTOS"];
            if ($alto != 1) { //Si alto es igual a 1 no ejecutamos este bloque e ingresamos de un solo la fila al array ordenado
                for ($x=0; $x < $alto; $x++) {
                    if ($puntos < $this -> array_desordenado[$x]["PUNTOS"] and $valor_identificador != $x) {
                        $puntos = $this -> array_desordenado[$x]["PUNTOS"];
                        $valor_identificador = $x;
                    }
                }
                for ($y=0; $y < $alto; $y++){
                    if ($puntos == $this -> array_desordenado[$y]["PUNTOS"] and $y != $valor_identificador) {
                        $this -> array_desordenado_2[] = $this -> array_desordenado[$y];
                        $last_id = count($this -> array_desordenado_2) - 1;
                        $this -> array_desordenado_2[$last_id]["VALOR_IDENTIFICADOR_1"] = $y;
                    }
                    if ($y == $alto - 1 and count($this -> array_desordenado_2) > 0) {
                        $this -> array_desordenado_2[] = $this -> array_desordenado[$valor_identificador];
                        $indice = count($this -> array_desordenado_2) - 1;
                        $this -> array_desordenado_2[$indice]["VALOR_IDENTIFICADOR_1"] = $valor_identificador;
                    }
                }
            }            
            if (count($this -> array_desordenado_2) > 0) {
                $this -> ordenar_por_dg();
                $this-> array_desordenado = array_values($this-> array_desordenado);                 
            } else {
                $this -> array_ordenado[] = $this -> array_desordenado[$valor_identificador];
                unset($this -> array_desordenado[$valor_identificador]);
                $this-> array_desordenado = array_values($this-> array_desordenado);                
            }
            if (count($this -> array_desordenado) == 0) {
                $variable_control = 1;
            }
        }while ($variable_control == 0);   
    }

    //Está función trabaja con el array: array_desordenado_2
    private function ordenar_por_dg(){
        $variable_control = 0;
        do{
            $alto = count($this -> array_desordenado_2);
            $valor_identificador = $alto -1;
            $diferencia_goles = $this -> array_desordenado_2[$valor_identificador]["DIFERENCIA_GOLES"];            
            if ($alto != 1) {
                for ($x=0; $x < $alto; $x++) {
                    if ($diferencia_goles < $this -> array_desordenado_2[$x]["DIFERENCIA_GOLES"] and $valor_identificador != $x) {
                        $diferencia_goles = $this -> array_desordenado_2[$x]["DIFERENCIA_GOLES"];
                        $valor_identificador = $x;
                    }
                }
                for ($y=0; $y < $alto; $y++){
                    if ($diferencia_goles == $this -> array_desordenado_2[$y]["DIFERENCIA_GOLES"] and $y != $valor_identificador) {//Cuando llegue a 0 va a pensar que son iguales y se mandara a llamar la funcion ordenar por DG
                        $this -> array_desordenado_3[] = $this -> array_desordenado_2[$y];
                        $last_id = count($this -> array_desordenado_3) - 1;
                        $this -> array_desordenado_3[$last_id]["VALOR_IDENTIFICADOR_2"] = $y;
                    }
                    if ($y == ($alto - 1) and count($this -> array_desordenado_3) > 0) {
                        $this -> array_desordenado_3[] = $this -> array_desordenado_2[$valor_identificador];
                        $indice = count($this -> array_desordenado_3) - 1;
                        $this -> array_desordenado_3[$indice]["VALOR_IDENTIFICADOR_2"] = $valor_identificador;
                    }
                }
            }
            if (count($this -> array_desordenado_3) > 0) {
                $this -> ordenar_por_gf();
                $this-> array_desordenado_2 = array_values($this-> array_desordenado_2);                     
            } else {
                $this -> array_ordenado[] = $this -> array_desordenado_2[$valor_identificador];
                $identificador_1 = $this -> array_desordenado_2[$valor_identificador]["VALOR_IDENTIFICADOR_1"];
                unset($this -> array_desordenado[$identificador_1]);
                unset($this -> array_desordenado_2[$valor_identificador]);
                $this-> array_desordenado_2 = array_values($this-> array_desordenado_2);                
            }
            if (count($this -> array_desordenado_2) == 0) {
                $variable_control = 1;
            }
        }while ($variable_control == 0);
    }

    //Está función trabaja con el array: array_desordenado_3
    private function ordenar_por_gf(){
        $variable_control = 0;
        do{
            $alto = count($this -> array_desordenado_3);
            $valor_identificador = $alto -1;
            $goles_favor = $this -> array_desordenado_3[$valor_identificador]["GOLES_FAVOR"];            
            if ($alto != 1) {
                for ($x=0; $x < $alto; $x++) {
                    if ($goles_favor < $this -> array_desordenado_3[$x]["GOLES_FAVOR"] and $valor_identificador != $x) {
                        $goles_favor = $this -> array_desordenado_3[$x]["GOLES_FAVOR"];
                        $valor_identificador = $x;
                    }
                }
                for ($y=0; $y < $alto; $y++){
                    if ($goles_favor == $this -> array_desordenado_3[$y]["GOLES_FAVOR"] and $y != $valor_identificador) {//Cuando llegue a 0 va a pensar que son iguales y se mandara a llamar la funcion ordenar por DG
                        $this -> array_desordenado_4[] = $this -> array_desordenado_3[$y];
                        $last_id = count($this -> array_desordenado_4) - 1;
                        $this -> array_desordenado_4[$last_id]["VALOR_IDENTIFICADOR_3"] = $y;
                    }
                    if ($y == ($alto - 1) and count($this -> array_desordenado_4) > 0) {
                        $this -> array_desordenado_4[] = $this -> array_desordenado_3[$valor_identificador];
                        $indice = count($this -> array_desordenado_4) - 1;
                        $this -> array_desordenado_4[$indice]["VALOR_IDENTIFICADOR_3"] = $valor_identificador;
                    }
                }
            }
            if (count($this -> array_desordenado_4) > 0) {
                $this -> ordenar_por_gc();
                $this-> array_desordenado_3 = array_values($this-> array_desordenado_3);                     
            } else {
                $this -> array_ordenado[] = $this -> array_desordenado_3[$valor_identificador];
                $identificador_1 = $this -> array_desordenado_3[$valor_identificador]["VALOR_IDENTIFICADOR_1"];
                $identificador_2 = $this -> array_desordenado_3[$valor_identificador]["VALOR_IDENTIFICADOR_2"];
                unset($this -> array_desordenado[$identificador_1]);
                unset($this -> array_desordenado_2[$identificador_2]);
                unset($this -> array_desordenado_3[$valor_identificador]);
                $this-> array_desordenado_3 = array_values($this-> array_desordenado_3);                
            }
            if (count($this -> array_desordenado_3) == 0) {
                $variable_control = 1;
            }
        }while ($variable_control == 0);
    }

    private function ordenar_por_gc(){
        $variable_control = 0;
        do{
            $alto = count($this -> array_desordenado_4);
            $valor_identificador = $alto -1;
            $goles_contra = $this -> array_desordenado_4[$valor_identificador]["GOLES_CONTRA"];            
            if ($alto != 1) {
                for ($x=0; $x < $alto; $x++) {
                    if ($goles_contra > $this -> array_desordenado_4[$x]["GOLES_CONTRA"] and $valor_identificador != $x) {
                        $goles_contra = $this -> array_desordenado_4[$x]["GOLES_CONTRA"];
                        $valor_identificador = $x;
                    }
                }
            }
            $this -> array_ordenado[] = $this -> array_desordenado_4[$valor_identificador];
            $identificador_1 = $this -> array_desordenado_4[$valor_identificador]["VALOR_IDENTIFICADOR_1"];
            $identificador_2 = $this -> array_desordenado_4[$valor_identificador]["VALOR_IDENTIFICADOR_2"];
            $identificador_3 = $this -> array_desordenado_4[$valor_identificador]["VALOR_IDENTIFICADOR_3"];
            unset($this -> array_desordenado[$identificador_1]);
            unset($this -> array_desordenado_2[$identificador_2]);
            unset($this -> array_desordenado_3[$identificador_3]);
            unset($this -> array_desordenado_4[$valor_identificador]);
            $this-> array_desordenado_4 = array_values($this-> array_desordenado_4);  
            if (count($this -> array_desordenado_4) == 0) {
                $variable_control = 1;
            }
        }while ($variable_control == 0);
    }
}
?>
    