<?php

function u4crypto_callback(){
    $calls = array();
    $urlcallback = explode('/',$_SERVER["REQUEST_URI"]);
    for ($i=0; $i < count($urlcallback); $i++) { 
        ($urlcallback[$i] === 'callback') ? $calls[0] = $urlcallback[$i] : false;
        if($urlcallback[$i] === 'u4crypto'){
            $calls[1] = $urlcallback[$i]; 
            $calls[2] = $urlcallback[$i+1];
        } 
    }
    return $calls;
}


