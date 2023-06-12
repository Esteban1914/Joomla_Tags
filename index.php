<?php

/* ====== v2.5.4 ====== /*

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
$response_python["error_id"]=array();
$response_python["error"]=array(); 
$response_python["response"]=array(); 
$date = date ('Y-m-d H:m:s');
$recortes = file("articolo.txt",  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$base_de_datos = file("base_de_datos.txt",  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$data=null;
$article_id=null;
$article_title=null;
$article_body=null;
$article_alias=null;
$article_catid=null;
$tag_id=null;
$tag_title=null;
$tag_title_lnh=null;
$core_content_id=null;
$result=null;
$created=null;
$rgt_root_lv0=null;

error_reporting(E_ERROR);

function remove_utfo_bom($text){
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/",'',$text);
    return $text;
}
function quitar_acentos($cadena){
    $originales = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ';
    $modificadas = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyyby';
    $cadena = utf8_decode($cadena);
    $cadena = strtr($cadena, utf8_decode($originales), $modificadas);
    $textoLimpio = preg_replace('([^A-Za-z0-9 -])', '', $cadena);
    return utf8_encode($textoLimpio);
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
$table_ucm_content = $prefix_table.'ucm_content';   
$table_ucm_base = $prefix_table.'ucm_base';  
$table_assets = $prefix_table.'assets';   

//Init Connection DB
$mysqli = new mysqli($host,$user,$password,$database); 

if ($mysqli->connect_error)
{
    $response_python["error"] = "Error al conectarse a la base de datos: $mysqli->connect_error";
    die(json_encode($response_python));
}

//Search instructions from txt File
foreach ($recortes as $ind=>$value) 
{
    $line = explode("|", $value);
    $line[0] = remove_utfo_bom($line[0]);
    switch (true) 
    {
        case preg_match("/^SET_TAG/", $line[0]):
            $article_id = trim($line[1]);
            $result=$mysqli->query("SELECT title,introtext,alias,catid FROM $table_content WHERE id = $article_id ");
            if ($result->num_rows <= 0) 
            {   
                //No exist article
                array_push($response_python["error_id"],$article_id);
                $article_id=null;
                $article_title=null;
                $article_body=null;
                $article_intro=null;
                $article_alias=null;
                $article_catid=null;
                break;
            } 
            $data=$result->fetch_object();
            $article_title=$data->title;
            $article_intro=$data->introtext;
            $article_alias=$data->alias;
            $article_catid=$data->catid;
        break;

        case preg_match("/^TAG_TITLE/", $line[0]):
        
            if($article_id != null)
            {
                $tag_title=trim($line[1]);

                
                //Verify tag exists    
                $result = $mysqli->query("SELECT id FROM $table_tags WHERE title='$tag_title'");
                
                $created=False;
                if ($result->num_rows <= 0) 
                {   
                    //If no exist tag, create it     
                    
                    //Format to alias and path
                    $tag_title_lnh=quitar_acentos($tag_title);
                    $tag_title_lnh=strtolower($tag_title_lnh);
                    $tag_title_lnh= str_replace(' ', '-', $tag_title_lnh);               
                    
                    //**For Segurity Find Max, not inc+2 Root rgt***///

                        // //Get Max rgt from ROOT Tag Info 
                        // $result=$mysqli->query("SELECT rgt FROM $table_tags WHERE level = 0 AND title = 'ROOT'");
                        
                        // if ($result->num_rows <= 0) 
                        // {
                        //     array_push($response_python["error"], "Error, El parametro RGT de la fila ROOT de Tags no existe en la base de datos, se ha detenido el proceso" );
                        //     die(json_encode($response_python));
                        //     //Code for Create ROOT row into tags_table
                        //     //$mysqli->query("INSERT INTO $table_tags (`id`, `parent_id`, `lft`, `rgt`, `level`, `path`, `title`, `alias`, `note`, `description`, `published`, `checked_out`, `checked_out_time`, `access`, `params`, `metadesc`, `metakey`, `metadata`, `created_user_id`, `created_time`,`created_by_alias`, `modified_user_id`, `modified_time`, `images`, `urls`, `hits`, `language`, `version`)
                        //     //VALUES (1, 0, 0, 1, 0, '', 'ROOT', 'root', '', '', 1, 0, '0000-00-00 00:00:00', 1, '{}', '', '', '', '', '2011-01-01 00:00:01','', 0, '0000-00-00 00:00:00', '', '', 0, '*', 1);");
                        //     //exit();
                        // }   
                        // $data=$result->fetch_object();
                        // $rgt_root_lv0=$data->rgt;
                        
                        // //Set manualy new Max rgt ROOT Tag Info
                        // $result=$mysqli->query("UPDATE $table_tags SET rgt = $rgt_root_lv0 + 2  WHERE level = 0 AND title = 'ROOT'");
                        
                        // if ( ! $result) 
                        // {
                        //     array_push($response_python["error"], "Error, El parametro RGT de la fila ROOT de Tags no se ha podido modificar en la base de datos, se ha detenido el proceso" ); 
                        //     die(json_encode($response_python));
                        // }   
                    
                    $result=$mysqli->query("SELECT MAX(rgt) as rgt FROM $table_tags;");
                    
                    if ($result->num_rows <= 0) 
                    {
                        array_push($response_python["error"], $tag_title." (0)");
                        break;
                    }

                    $data=$result->fetch_object();
                    $rgt_root_lv0=$data->rgt;
                    
                    if ($rgt_root_lv0 == null)
                        $rgt_root_lv0=0;
                    else
                        $rgt_root_lv0+=1;
                    //Create tag with rgt 
                    $param='{"tag_layout":"","tag_link_class":"label label-info"}';
                    
                    $result=$mysqli->query("INSERT INTO $table_tags (title,lft,rgt,path,alias,level,parent_id,published,access,params,language,created_time,modified_time) 
                                            VALUES ('$tag_title',$rgt_root_lv0,$rgt_root_lv0+1,'$tag_title_lnh','$tag_title_lnh',1,1,1,1,'$param','*','$date','$date'); ");
                    
                    if( ! $result)
                    {
                        //No crated Tag
                        //print($mysqli -> error);
                        array_push($response_python["error"], $tag_title."(1)");
                        break;
                    }
                    
                    //Get Tag ID form db 
                    $result = $mysqli->query("SELECT id FROM $table_tags WHERE title='$tag_title';");
                    
                    if ($result->num_rows <= 0) 
                    {
                        //If no exist tag again, Error
                        print($mysqli -> error);
                        array_push($response_python["error"], $tag_title." (2)");  
                        break;
                    } 
                    
                    //Get Tag ID 
                    $data=$result->fetch_object();
                    $tag_id=$data->id;

                    $created=true;
                    #array_push($response_python["response"],$tag_title);
                }
                else
                {
                    
                    //Get Tag ID 
                    $data=$result->fetch_object();
                    $tag_id=$data->id;
                    
                    //Publish Tags
                    $result=$mysqli->query("UPDATE $table_tags SET published = 1  WHERE id = $tag_id; ");
                    if ( ! $result) 
                    {
                        print($mysqli -> error);
                        array_push($response_python["error"], $tag_title." (3)");
                        break;
                    } 
                }
                //Verify tag in tag_map exists        
                $result = $mysqli->query("SELECT * FROM $table_contentitem_tag_map WHERE content_item_id = $article_id AND tag_id = $tag_id ;");
                
                if ($result->num_rows <= 0 ) 
                {   
                    //No exist tag_map, create it
                    
                    //Find core_content_id into ucm_base
                    $result = $mysqli->query("SELECT ucm_id  FROM $table_ucm_base WHERE ucm_item_id = $article_id ;");
                    
                    if ($result->num_rows <= 0) 
                    {
                        //No exist ucm, create it

                        
                        //**For Segurity Find Max, not inc+2 Root rgt***///

                            //Get Max rgt from ROOT Asset Info 
                            // $result=$mysqli->query("SELECT rgt FROM $table_assets WHERE level = 0 AND title = 'Root Asset'");
                            
                            // if ($result->num_rows <= 0) 
                            // {
                            //     array_push($response_python["error"], "Error, El parametro RGT de la fila ROOT de Asset no existe en la base de datos, se ha detenido el proceso" );
                            //     die(json_encode($response_python));
            
                            // }   
                            // $data=$result->fetch_object();
                            // $rgt_root_lv0=$data->rgt;
                            // //Set manualy new Max rgt ROOT Asset Info
                            // $result=$mysqli->query("UPDATE $table_assets SET rgt = $rgt_root_lv0 + 2  WHERE level = 0 AND title = 'Root Asset'");
                            
                            // if ( ! $result) 
                            // {
                            //     array_push($response_python["error"], "Error, El parametro RGT de la fila ROOT de Asset no se ha podido modificar en la base de datos, se ha detenido el proceso" ); 
                            //     die(json_encode($response_python));
                            // }

                        $result=$mysqli->query("SELECT MAX(rgt) as rgt FROM $table_assets");
                        
                        if ($result->num_rows <= 0) 
                        {
                            array_push($response_python["error"], $tag_title." (4)");
                            break;
        
                        }   

                        
                        $data=$result->fetch_object();
                        $rgt_root_lv0=$data->rgt;
                        
                        if ($rgt_root_lv0 == null)
                            $rgt_root_lv0=0;
                        else
                            $rgt_root_lv0+=1;
                        
                        //Insert assets
                        $result=$mysqli->query("INSERT INTO $table_assets (parent_id,lft,rgt,level,name,title,rules) 
                                                VALUES (1,$rgt_root_lv0,$rgt_root_lv0+1,1,'NullValue$rgt_root_lv0','NullValue$rgt_root_lv0','{}'); ");
                    
                        if( ! $result)
                        {
                            //No inserted asset
                            print($mysqli -> error);
                            array_push($response_python["error"], $tag_title." (5)");
                            break;
                        }
                        //Get new assert ID
                        $result=$mysqli->query("SELECT MAX(id) as id FROM $table_assets;");
                        
                        if ($result->num_rows <= 0) 
                        {
                            //No selected assets id
                            array_push($response_python["error"], $tag_title." (6)");
                            break;
                        }

                        $data=$result->fetch_object();
                        $assets_id=$data->id;

                        $param='{"article_layout":"","show_title":"","link_titles":"","show_tags":"","show_intro":"","info_block_position":"","info_block_show_title":"","show_category":"","link_category":"","show_parent_category":"","link_parent_category":"","show_associations":"","show_author":"","link_author":"","show_create_date":"","show_modify_date":"","show_publish_date":"","show_item_navigation":"","show_icons":"","show_print_icon":"","show_email_icon":"","show_vote":"","show_hits":"","show_noauth":"","urls_position":"","alternative_readmore":"","article_page_title":"","show_publishing_options":"","show_article_options":"","show_urls_images_backend":"","show_urls_images_frontend":""}';
                        $metadata='{"robots":"","author":"","rights":"","xreference":""}';
                        $iamges='{"image_intro":"","float_intro":"","image_intro_alt":"","image_intro_caption":"","image_fulltext":"","float_fulltext":"","image_fulltext_alt":"","image_fulltext_caption":""}';
                        $urls='{"urla":false,"urlatext":"","targeta":"","urlb":false,"urlbtext":"","targetb":"","urlc":false,"urlctext":"","targetc":""}';
                        //Set core to ucm_content table
                        $result=$mysqli->query("INSERT INTO $table_ucm_content (core_type_alias,core_title,core_alias,core_state,core_body,core_access,core_params,core_metadata,core_created_time,core_language,core_content_item_id,asset_id,core_images,core_urls,core_metakey,core_metadesc,core_catid,core_type_id) 
                                            VALUES ('com_content.article','$article_title','$article_alias',1,'$article_intro',1,'$param','$metadata','$date','*',$article_id,$assets_id,'$iamges','$urls','','',$article_catid,1); ");
                        if( ! $result)
                        {              
                            //No Inserted ucm_content  
                            print($mysqli -> error);
                            array_push($response_python["error"],$tag_title." (7)");
                            break;
                        } 
                        //get new core_content id
                        $result=$mysqli->query("SELECT MAX(core_content_id) as core_content_id FROM $table_ucm_content;");
                        
                        if ($result->num_rows <= 0) 
                        {
                            //No selected core_content_id
                            array_push($response_python["error"], $tag_title." (8)");
                            break;
                        }

                        $data=$result->fetch_object();
                        $core_content_id=$data->core_content_id;

                        //Update name and title table_assets
                        $result=$mysqli->query("UPDATE $table_assets SET name = '#__ucm_content.$core_content_id', title='#__ucm_content.$core_content_id'  WHERE id=$assets_id");
                        if( ! $result)
                        {                
                            print($mysqli -> error);
                            array_push($response_python["error"],$tag_title." (9)");
                            break;
                        } 

                        //Set core to ucm_base table
                        $result=$mysqli->query("INSERT INTO $table_ucm_base (ucm_id,ucm_item_id,ucm_type_id,ucm_language_id) 
                                            VALUES ($core_content_id,$article_id,1,1); ");
                        if( ! $result)
                        {                
                            print($mysqli -> error);
                            array_push($response_python["error"],$tag_title." (10)");
                            break;
                        } 
                    }
                    else
                    {
                        $data=$result->fetch_object();
                        $core_content_id=$data->ucm_id;
                    }
                    //Create tag_map
                    $result=$mysqli->query("INSERT INTO $table_contentitem_tag_map (type_alias,core_content_id,content_item_id,tag_id,tag_date,type_id) 
                                            VALUES ('com_content.article',$core_content_id,$article_id,$tag_id,'$date',1); ");
                    if( ! $result)
                    {
                        //If no created tag_map, Error                    
                        print($mysqli -> error);
                        array_push($response_python["error"],$tag_title." (11)");
                        break;
                    }     

                    array_push($response_python["response"],$tag_title .($created==true ? " (+)" : " (-)"));
                }
            }

            break;
    }
}

$mysqli->close();

if (count($response_python['response']) > 0)
    $response_python['response'] = "Tags Correctos: ".implode(',',$response_python['response']);
if (count($response_python['error']) > 0)
    $response_python['error'] = "Tags Fallidos: ".implode(',',$response_python['error']);
if (count($response_python['error_id']) > 0)
    $response_python['error_id'] = "Contenidos Inexistente: ".implode(',',$response_python['error_id']);

exit(json_encode($response_python));

?>