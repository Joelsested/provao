<?php

 

 
 
 $ch = curl_init("https://sestedcursosvirtual.com/ralusi.txt"); // such as http://example.com/example.xml
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
$data = curl_exec($ch);
curl_close($ch);

 echo json_encode($data);
