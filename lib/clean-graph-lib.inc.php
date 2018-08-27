<?php
/*SERMO
 * clean tmp graphs
 *
 
 */
function clean_tmp_graph($query_name){
	$directory="tmp/graph/";
	
	$images = glob($directory.$query_name."*.png");
	
	foreach ($images as $image){
		if(file_exists($image)){
			chmod($image, 0644);
			unlink($image);
		}
	}
	
	
	
	
};

?>