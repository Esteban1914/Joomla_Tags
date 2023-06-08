ip 194.163.45.70
user: chang
pass: EntrandoAlServidor
mysql -h 176.31.1.206 -u abogadoenitalia_Mj8HU -p
Jg-T[5F6F(3/D|9H_8
use abogadoenitalia_abogadoenitaliacom;
show tables;
select title from nombre_tabla_tags;


SHOW COLUMNS FROM database_Name.table_name;

select title,path,alias,id from qri8u_tags where id in (select tag_id from qri8u_contentitem_tag_map where content_item_id in (select id from qri8u_content where catid=(select id from qri8u_categories where title="Derechos de presos en extranjero ")));
select core_content_id,content_item_id,tag_id from qri8u_contentitem_tag_map where content_item_id in (select id from qri8u_content where catid=(select id from qri8u_categories where title="Derechos de presos en extranjero "));
select core_content_id,core_title from qri8u_ucm_content where core_content_id in (select core_content_id from qri8u_contentitem_tag_map where content_item_id in (select id from qri8u_content where catid=(select id from qri8u_categories where title="Derechos de presos en extranjero")));
select ucm_id,ucm_item_id from qri8u_ucm_base where ucm_id in (select core_content_id from qri8u_contentitem_tag_map where content_item_id in (select id from qri8u_content where catid=(select id from qri8u_categories where title="Derechos de presos en extranjero ")));

 SHOW COLUMNS FROM qri8u_contentitem_tag_map; 
 select core_content_id,content_item_id,tag_id,tag_date from qri8u_contentitem_tag_map where content_item_id in (select id from qri8u_content where catid=(select id from qri8u_categories where title="Derechos de presos en extranjero ")) ORDER BY tag_date;

  delete from qri8u_tags where title <> "ROOT";
  -- update qri8u_tags set rgt=1 where title="ROOT"; 
  delete from qri8u_contentitem_tag_map;
  delete from qri8u_ucm_base;
  delete from qri8u_ucm_content;
  select * from  qri8u_tags;
  select * from  qri8u_contentitem_tag_map;
  select * from  qri8u_ucm_base;
  select * from  qri8u_ucm_content;
