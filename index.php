<?php

/*****
 *
 * NOTA IMPORTANTE
 *
 * El fichero base de carga de contenidos debe estar en UTF-8 sin BOM
 *
 *
 */

 /* Format in articolo.txt
    
    SET_TAG| id_article
    TAG_TITLE| tag_name
    TAG_TITLE| tag_name2

 */
/****  VARs  *******/

$response_python["error"]=array(); 
$response_python["response"]=array(); 
$tags_ids=[];
$tag_id=0;
$bool=true;
$date = date ('Y-m-d H:m:s');
$recortes = file("articolo.txt",  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$base_de_datos = file("base_de_datos.txt",  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

error_reporting(E_ERROR);

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
        case preg_match("/^URL/", $line1[0]):
            $URL = trim($line1[1]);
            break;
        case preg_match("/^PREFIJO/", $line1[0]):
            $prefix_table = trim($line1[1]);
            break;
        default;
            break;
    }
}

//Search instructions from txt File
foreach ($recortes as $ind=>$value) {
    $line = explode("|", $value);
    $line[0] = remove_utfo_bom($line[0]);
    switch (true) {
        case preg_match("/^SET_TAG/", $line[0]):
            $tag_id = trim($line[1]);
            $tags_ids[$tag_id]=array();
            $bool=true;
        case preg_match("/^TAG_TITLE/", $line[0]):
            if ($bool==false)
                array_push($tags_ids[$tag_id], trim($line[1]));
            else
                $bool=false;
            
    }
}
//Fill Tables Prefix
$table_users = $prefix_table.'users';
$table_tags = $prefix_table.'tags';
$table_contentitem_tag_map = $prefix_table.'contentitem_tag_map';
$table_categories = $prefix_table.'categories';
$table_content = $prefix_table.'content';

//Init Connection DB
$mysqli = new mysqli($host,$user,$password,$database);

if ($mysqli->connect_error) 
{
    $response_python["error"] = 'Error de coneccion a la base de datos: ('. $mysqli->connect_error .') '. $mysqli->connect_error;
    die(json_encode($response_python));
}

foreach ($tags_ids as $key => $value) 
{   
    //Verify article exists
    $article = $mysqli->query("SELECT id FROM $table_content WHERE id = $key ");
    if ($article->num_rows <= 0) 
    {   
        //No exist article
        $error="Error, contenido id:'$key' no existente";
        array_push($response_python["error"], $error );
        continue;
    }
    //Save Article ID 
    $article_id=$article->fetch_object()->id;
    //Find Tags ID into tags_ids
    foreach ($value as $key1 => $value1)
    {
        //Get ID form db by Title
        $tag = $mysqli->query("SELECT id FROM $table_tags WHERE title='$value1'");
        if ($tag->num_rows <= 0) 
        {   
            //If no exist tag, create it              
            //Get Max lft & rgt
            $result=$mysqli->query("SELECT MAX(rgt) AS rgt FROM $table_tags");
            if ($result->num_rows > 0) 
                $max=$result->fetch_object()->rgt+1;
            else
            {
                //If no selected Tag info, Error
                $error="Error 0, no se ha creado el tag:'$value1'";
                array_push($response_python["error"], $error );
                continue;
            }
            $result=$mysqli->query("INSERT INTO $table_tags (title,parent_id,lft,rgt,level,path,alias,published,access,params,metadata,urls,language,created_time,modified_time) 
                                    VALUES ('$value1',1,$max,$max+1,1,'$value1','$value1',1,1,'{}','{}','{}','*','$date','$date'); ");
            if(!$result)
            {
                //No crated Tag
                $error="Error 1, no se ha creado el tag:'$value1'";
                array_push($response_python["error"], $error );
                continue;
            }
            //Get ID form db by Title again 
            $tag = $mysqli->query("SELECT id FROM $table_tags WHERE title='$value1'");
            if ($tag->num_rows <= 0) 
            {
                //If no exist tag, Error
                $error="Error 2 , no se ha creado correctamente el tag:'$value1'";
                array_push($response_python["error"], $error );
                continue;
            } 
            array_push($response_python["response"], "Tag '$value1' Creado" );
            
            
        }
        //Save Tag ID 
        $tag_id=$tag->fetch_object()->id;
        //Verify tag exists
        $tag_map = $mysqli->query("SELECT * FROM $table_contentitem_tag_map WHERE core_content_id = $article_id AND tag_id = $tag_id ");
        if ($tag_map->num_rows <= 0) 
        {   
            //No exist tag_map, create it              
            $result=$mysqli->query("INSERT INTO $table_contentitem_tag_map (type_alias,core_content_id,content_item_id,tag_id,tag_date,type_id) 
                                    VALUES ('com_content.article',$article_id,$article_id,$tag_id,'$date',1); ");
            if(! $result)
            {
                //If no exist tag, Error
                $error="Error, no se ha creado el tag_map para unir contenido de id:'$article_id' con el tag de id:'$tag_id' ";
                array_push($response_python["error"], $error );
            }                
            array_push($response_python["response"], "Asignado tag de id:'$tag_id' al conenido de id:'$article_id'" );
        }
    }
}
//Stop Connection DB
$mysqli->close();

print(json_encode($response_python));

exit;

?>