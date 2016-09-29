<?php 
ob_start("ob_gzhandler"); // GZIP
header('Content-Type: application/javascript');
header("Cache-Control: max-age=".(60 * 60 * 24 * 30 * 12)); // 1 ano
header("Cache-Control: public", false); 
echo file_get_contents("ksig.js");
 ?>