<?php
    require '../../config/config.php';
    include("../classes/User.php");
    include("../classes/Post.php");
    include("../classes/Notification.php");
    //Receives the request
    if (isset($_POST['post_body'])) {
        $post = new Post($con, $_POST['user_from']); //the user who sent the post
        $post->submitPost($_POST['post_body'], $_POST['user_to']); 
    }
?>