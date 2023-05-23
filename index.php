<?php
/* ====== v2.0.1 ====== /*
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
$date = date ('Y-m-d H:m:s');
$recortes = file("articolo.txt",  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$base_de_datos = file("base_de_datos.txt",  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$article_id=null;
$article_title=null;
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
    $response_python["error"] = "Error al conectarse a la base de datos: $mysqli->connect_error";
    die(json_encode($response_python));
}

//Search instructions from txt File
foreach ($recortes as $ind=>$value) {
    $line = explode("|", $value);
    $line[0] = remove_utfo_bom($line[0]);
    switch (true) {
        case preg_match("/^SET_TAG/", $line[0]):
            $article_id = trim($line[1]);

            $result=$mysqli->query("SELECT title FROM $table_content WHERE id = $article_id ");
            
            if ($result->num_rows <= 0) 
            {   
               //No exist article
               array_push($response_python["error"], "Error, id $article_id no existente!");
               $article_id=null;
               $article_title=null;
               break;
            }
            
            $article_title=$result->fetch_object()->title;
            
            break;
        case preg_match("/^TAG_TITLE/", $line[0]):
            if($article_id != null)
            {
                $tag_title=trim($line[1]);
                
                //Verify tag exists    
                $result = $mysqli->query("SELECT id FROM $table_tags WHERE title='$tag_title'");
                
                if ($result->num_rows <= 0) 
                {   
                    //If no exist tag, create it     

                    //Get Max rgt from ROOT Info 
                    $result=$mysqli->query("SELECT rgt FROM $table_tags WHERE level = 0 AND title = 'ROOT'");
                    
                    if ($result->num_rows <= 0) 
                    {
                        array_push($response_python["error"], "Error, El parametro RGT de la fila ROOT no existe en la base de datos, se ha detenido el proceso" );
                        die(json_encode($response_python));
                        //Code for Create ROOT row into tags_table, is necesary find MAX and update rgt field 
                        //$mysqli->query("INSERT INTO $table_tags (`id`, `parent_id`, `lft`, `rgt`, `level`, `path`, `title`, `alias`, `note`, `description`, `published`, `checked_out`, `checked_out_time`, `access`, `params`, `metadesc`, `metakey`, `metadata`, `created_user_id`, `created_time`,`created_by_alias`, `modified_user_id`, `modified_time`, `images`, `urls`, `hits`, `language`, `version`)
                        //VALUES (1, 0, 0, 1, 0, '', 'ROOT', 'root', '', '', 1, 0, '0000-00-00 00:00:00', 1, '{}', '', '', '', '', '2011-01-01 00:00:01','', 0, '0000-00-00 00:00:00', '', '', 0, '*', 1);");
                    }   

                    $rgt_root_lv0=$result->fetch_object()->rgt;
                    
                    //Set manualy new Max rgt ROOT Info
                    $result=$mysqli->query("UPDATE $table_tags SET rgt = $rgt_root_lv0 + 2  WHERE level = 0 AND title = 'ROOT'");
                    
                    if ( ! $result) 
                    {
                        array_push($response_python["error"], "Error, El parametro RGT de la fila ROOT no se ha podido modificar en la base de datos, se ha detenido el proceso" );
                        
                        die(json_encode($response_python));
                    }   

                    //Create tag with rgt 
                    $result=$mysqli->query("INSERT INTO $table_tags (title,parent_id,lft,rgt,level,path,alias,published,access,params,metadata,urls,language,created_time,modified_time) 
                                            VALUES ('$tag_title',1,$rgt_root_lv0,$rgt_root_lv0+1,1,'$tag_title','$tag_title',1,1,'{}','{}','{}','*','$date','$date'); ");
                    
                    if( ! $result)
                    {
                        //No crated Tag
                        array_push($response_python["error"], "Error, no se ha creado el tag:'$tag_title'" );
                        
                        break;
                    }
                    
                    //Get ID form db by title again 
                    $result = $mysqli->query("SELECT id FROM $table_tags WHERE title='$tag_title'");
                    
                    if ($result->num_rows <= 0) 
                    {
                        //If no exist tag again, Error
                        array_push($response_python["error"], "Error, no se ha creado correctamente el tag:'$tag_title'" );
                        
                        break;
                    } 

                    array_push($response_python["response"], "Tag '$tag_title' creado" );
                }
                
                //Get Tag ID 
                $tag_id=$result->fetch_object()->id;

                //Verify tag in tag_map exists        
                $tag_map = $mysqli->query("SELECT * FROM $table_contentitem_tag_map WHERE core_content_id = $article_id AND tag_id = $tag_id ");
    
                if ($tag_map->num_rows <= 0) 
                {   
                    //No exist tag_map, create it              
                    $result=$mysqli->query("INSERT INTO $table_contentitem_tag_map (type_alias,core_content_id,content_item_id,tag_id,tag_date,type_id) 
                                            VALUES ('com_content.article',$article_id,$article_id,$tag_id,'$date',1); ");
                    if( ! $result)
                    {
                        //If no created tag_map, Error                    
                        array_push($response_python["error"], "Error, no se ha creado el tag_map para unir '$article_title (id:$article_id)' con el tag '$tag_title(id:$tag_id)' " );
                        
                        break;
                    }     

                    array_push($response_python["response"], "Asignado tag '$tag_title (id:$tag_id)' a '$article_title(id:$article_id)'");
                }
            }
            break;
    }
}

$mysqli->close();

exit(json_encode($response_python));

?>