<?php



function remove_utfo_bom($text){
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/",'',$text);
    return $text;
}
//Search info DB from txt File 
foreach ($base_de_datos as $ind=>$value) {
    
    $line1 = explode("^/^^/^", $value);

    $line1[0] = remove_utfo_bom($line1[0]);
    switch (true) {
        case preg_match("/^HOST/", $line1[0]):
            $host = trim($line1[1]);
            break;
        case preg_match("/^USER/", $line1[0]):
            $user = trim($line1[1]);
            break;
        case preg_match("/^PASSWORD/", $line1[0]):
            $password  = trim($line1[1]);
            break;
        case preg_match("/^DATABASE/", $line1[0]):
            $database = trim($line1[1]);
            break;
        case preg_match("/^PREFIJO/", $line1[0]):
            $prefix_table = trim($line1[1]);
            break;
        default;
            break;
    }
}

$cn = mysqli_connect($host,$user,$password,$database);
$data =$_FILES['txt']['tmp_name'];

$recortes = file("$data",  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$parse = [];
$i = 0;
$j= 1;
$k=1;
$a=1;
$b=1;
$c=1;
$d=1;

$param='{"tag_layout":"","tag_link_class":"label label-info"}';
$alias ='com_content.article';
$lenguaje = '*';
foreach ($recortes as $recorte) {

	$temp = explode(':', $recorte);


	$parse[$i]["texto1"] = (isset($temp[0])) ? $temp[0] : "";
	$parse[$i]["texto2"] = (isset($temp[1])) ? $temp[1] : "";
	$parse[$i]["texto3"] = (isset($temp[2])) ? $temp[2] : "";
	$parse[$i]["texto4"] = (isset($temp[3])) ? $temp[3] : "";
	$parse[$i]["texto5"] = (isset($temp[4])) ? $temp[4] : "";
	$i++;
}

for ($i=0; $i < count($parse) ; $i++) 
{ 
	$query = "INSERT INTO  iubj8_tags (path, title, alias, description, metadesc,published,access,language,parent_id,lft,rgt,level,params) 
								VALUES ('".$parse[$i]["texto1"]."','".$parse[$i]["texto1"]."','".$parse[$i]["texto3"]."','".$parse[$i]["texto2"]."','".$parse[$i]["texto2"]."','$j','$k','$lenguaje','$a','$b','$c','$d','$param')";//aca cambiar el nombre de la tabla al conectar

    $insertar = mysqli_query($cn,$query);

    $indice = "SELECT max(id) FROM iubj8_tags";
    $res = mysqli_query($cn,$indice);
    $marcador=mysqli_fetch_row($res);
    //var_dump($marcador);
    //die();

    //$queryid="SELECT id FROM iubj8_tags WHERE id= ".$marcador[0]."" ;
    //$result = mysqli_query($cn,$queryid);
    $fecha  = date ('Y-m-d H:m:s');

    //while ($row=mysqli_fetch_array($result,MYSQLI_BOTH)){


        $query3 ="INSERT INTO iubj8_contentitem_tag_map (type_alias ,core_content_id ,content_item_id ,type_id ,tag_date ,tag_id )  
                                                VALUES ('$alias','".$parse[$i]["texto4"]."','".$parse[$i]["texto5"]."','$a','$fecha','".$marcador[0]."')";
        $ab = mysqli_query($cn,$query3);	
    //}
}

mysqli_close($cn);
	
	echo"<div>Se importo correctamente</div>";
	echo "<p><a href = \"index.htm\">Regresar</a></p>";

?>


