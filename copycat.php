<?php     

if(!is_numeric($_GET['id']))die('Please specify a category ID');

$catId = $_GET['id'];   

$xml = simplexml_load_file('app/etc/local.xml'); 
$host = $xml->global->resources->default_setup->connection->host; 
$username = $xml->global->resources->default_setup->connection->username; 
$password = $xml->global->resources->default_setup->connection->password; 
$dbname = $xml->global->resources->default_setup->connection->dbname; 
$res = mysql_pconnect($host, $username, $password); 
mysql_select_db($dbname);         

$catsDone = 0; 
duplicate_entity($catId); 
echo $catsDone . ' Categories duplicated.';   

function duplicate_entity($id, $parent_id = null)
	{ global $catsDone;     
		
	// Grab category to copy
	$sql = "SELECT * FROM catalog_category_entity WHERE entity_id = " . $id; 
	$query_entity = mysql_query($sql); 
	  
	$entity = mysql_fetch_object($query_entity);     
	
	if(!$parent_id)$parent_id = $entity->parent_id;         
	
	mysql_query("INSERT INTO catalog_category_entity (entity_type_id, attribute_set_id, parent_id, created_at, updated_at, path, position, level, children_count) 
	VALUES ({$entity->entity_type_id}, {$entity->attribute_set_id}, {$parent_id}, NOW(), NOW(), '', {$entity->position}, {$entity->level}, {$entity->children_count})"); 
	
	$newEntityId = mysql_insert_id();   
	
	$query = mysql_query("SELECT path FROM catalog_category_entity WHERE entity_id = " . $parent_id); 
	$parent = mysql_fetch_object($query); 
	$path = $parent->path . '/' . $newEntityId;   
	
	mysql_query("UPDATE catalog_category_entity SET path='". $path."' WHERE entity_id=". $newEntityId);     
	
	foreach(array('datetime', 'decimal', 'int', 'text', 'varchar') as $dataType){ 
		$sql = "SELECT * FROM catalog_category_entity_".$dataType." 
		WHERE entity_id=" . $entity->entity_id; 
		//die($sql); 
		$query = mysql_query($sql); 
		while ($value = mysql_fetch_object($query)){ 
			mysql_query("INSERT INTO catalog_category_entity_".$dataType." (entity_type_id, attribute_id, store_id, entity_id, value) 
			VALUES ({$value->entity_type_id}, {$value->attribute_id}, {$value->store_id}, {$newEntityId}, '{$value->value}')"); 
			} 
		}     
		
		$sql = "SELECT entity_id FROM catalog_category_entity WHERE parent_id = " . $id; 
		$query = mysql_query($sql);   
		
		while ($entity = mysql_fetch_object($query)){ duplicate_entity($entity->entity_id, $newEntityId); 
			} 
		$catsDone++; 
	}        
?>