<?php
    include("../../config/config.php");
    include("../classes/User.php");
    include("../classes/Message.php");

    $limit = 7; //how many messages going to load
    //$_REQUEST['userLoggedIn'] request come from ajax call from the demo.js 
    $message =  new Message($con, $_REQUEST['userLoggedIn']); 

    //$_Request contain the data from the ajax method in demo.js 
    echo $message->getConvosDropdown($_REQUEST, $limit);
?>