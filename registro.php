<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Access-Control-Allow-Methods, Access-Control-Allow-Headers, Allow, Access-Control-Allow-Origin");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, HEAD");
header("Allow: GET, POST, PUT, DELETE, OPTIONS, HEAD");
require_once "conexion.php";
require_once "jwt.php";
if($_SERVER["REQUEST_METHOD"]=="OPTIONS"){
    exit();
}
/***BLOQUE DE ACCESO DE SEGURIDAD */
$header = apache_request_headers();
$tmp = $header['Authorization'];
$jwt = str_replace("Bearer ", "", $tmp);
if(JWT::verify($jwt, Config::SECRET)!=0){
    header("HTT/1.1 401 Unauthorized");
    exit;
}
$user = JWT::get_data($jwt, Config::SECRET)["user"];
/*** BLOQUE WEB SERVICE REST */
$metodo = $_SERVER["REQUEST_METHOD"];
switch($metodo){
    case "GET":
        $c = conexion();
        if(isset($_GET['id'])){
            $s = $c->prepare("SELECT * FROM registros WHERE id=:id");
            $s->bindValue(":id", $_GET['id']); 
        }else{
            $s = $c->prepare("SELECT * FROM registros");
        }
        $s->execute();
        $s->setFetchMode(PDO::FETCH_ASSOC);
        $r = $s->fetchAll();
        header("http/1.1 200 ok");
        echo json_encode($r);
        break;
    case "POST":
        if(isset($_POST['sensor']) && isset($_POST['valor'])){
            $c = conexion();
            $s = $c->prepare("INSERT INTO registros (user, sensor, valor, fecha) VALUES (:u, :s, :v, NOW())");
            $s->bindValue(":u", $user);
            $s->bindValue(":s", $_POST['sensor']);
            $s->bindValue(":v", $_POST['valor']);
            $s->execute();
            if($s->rowCount()>0){
                header("http/1.1 201 created");
                echo json_encode(array("add" => "y", "id" => $c->lastInsertId()));
            }else{
                header("http/1.1 400 bad request");
                echo json_encode(array("add" => "n"));
            }
        }else{
            header("HTTP/1.1 400 Bad Request");
            echo "Faltan datos";
        }
        break;
    case "PUT":
        if(isset($_GET['id'])){
            
            $sql="UPDATE registros SET ";
            (isset($_GET['user'])) ? $sql .="user = :u, ": null;
            (isset($_GET['sensor'])) ? $sql .="sensor = :s, ": null;
            (isset($_GET['valor'])) ? $sql .="valor = :v, ": null;
            (isset($_GET['fecha'])) ? $sql .="fecha = :f, ": null;

            $sql=substr($sql, 0,  -2);
            $sql .= " WHERE id = :id";
            
            $c = conexion();
            $s = $c->prepare($sql);
            (isset($_GET['user'])) ? $s->bindValue(":u", $_GET['user']) : null;
            (isset($_GET['sensor'])) ? $s->bindValue(":s", $_GET['sensor']) : null;
            (isset($_GET['valor'])) ? $s->bindValue(":v", $_GET['valor']) : null;
            (isset($_GET['fecha'])) ?  $s->bindValue(":f", $_GET['fecha']) : null;
            $s->bindValue(":id", $_GET['id']);
            $s->execute();

            if($s->rowCount() > 0){
                header("http/1.1 201 updated");
                echo json_encode (["update" => "y"]);
            }else{
                header("http/1.1 400 bad request");
                echo json_encode (["update" => "n"]);
            }
 
        }else{
            header("HTT/1.1 400 Bad Request");
        }
        break;
    case "DELETE":
        if(isset($_GET['id'])){
            $c = conexion();
            $s = $c->prepare("DELETE FROM registros WHERE  id = :id");
            $s->bindValue(":id", $_GET['id']);
            $s->execute();

            if($s->rowCount() > 0){
                header("http/1.1 201 deleted");
                echo json_encode (["delete" => "y"]);
            }else{
                header("http/1.1 400 bad request");
                echo json_encode (["delete" => "n"]);
            }

        }else{
            header("HTT/1.1 400 Bad Request");
            echo "Faltan datos";
        }
        break;
    default:
        header("HTT/1.1 400 Bad Request");
}