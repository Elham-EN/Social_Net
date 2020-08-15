<?php
    include("../../config/config.php");
    include("../classes/User.php");
    include("../classes/Notification.php");

    $limit = 4; //how many messages going to load
    //$_REQUEST['userLoggedIn'] request come from ajax call from the demo.js 
    $notification =  new Notification($con, $_REQUEST['userLoggedIn']); 

    //$_Request contain the data from the ajax method in demo.js 
    echo $notification->getNotification($_REQUEST, $limit);
?>