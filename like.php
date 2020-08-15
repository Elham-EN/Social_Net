<html>
<head>
   <title></title>
</head>
<body>
    <style type="text/css">
        body {
            background-color: #fff;
        }

        form {
            
            position: absolute; top: 1.3;
        }

        .comment_like {
            background-color: transparent; border: none; font-size: 16px; color: #3498db; padding: 0;
            height: auto; width: auto; margin: 0;
        }

        .like_value {
	        display: inline; font-size: 16px; font-family: Arial, Helvetica, sans-serif;
        }
    
    </style>
<?php  
    require 'config/config.php';
    include("includes/classes/User.php");
    include("includes/classes/Post.php");
    include("includes/classes/Notification.php");

    if (isset($_SESSION['username'])) {
        $userLoggedIn = $_SESSION['username'];
        $user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$userLoggedIn'");
        $user = mysqli_fetch_array($user_details_query);
    }
    else { //Redirecting users not logged in (users cannot access page from URL)
        header("Location: register.php");
    }

    //get id of post (post id will be sent to this page as a variable)
    if(isset($_GET['post_id'])) { //check if post_id is a variable
        $post_id = $_GET['post_id']; //this get variable is sent as url parameter
    }

    //get number of likes and and post added by user (e.g tom_jerry)
    $get_likes = mysqli_query($con, "SELECT * FROM posts WHERE id='$post_id'");
    $row = mysqli_fetch_array($get_likes); //fetch the result based on the query
    $user_liked = $row['added_by']; //(person who posted this post)
    $total_likes = $row['likes']; //number of likes on user who posted

    //get all information about user who posted it (e.g username = tom_jerry)
    $user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$user_liked'");
    $row = mysqli_fetch_array($user_details_query); //fetch all result based on the query
    $total_user_likes = $row['num_likes']; //total numbers of likes for this user
    //Like button
    if (isset($_POST['like_button'])) {
        $total_likes++;
        $query = mysqli_query($con, "UPDATE posts SET likes='$total_likes' WHERE id='$post_id'");
        $total_user_likes++;
        $user_likes = mysqli_query($con, "UPDATE users SET num_likes='$total_user_likes' WHERE username='$user_liked'");    
        $insert_user = mysqli_query($con, "INSERT INTO likes VALUES(NULL, '$userLoggedIn', '$post_id')");
        //insert notification - notification for user for liking its post
        if($user_liked != $userLoggedIn) { //if user not liking its own post when loggedIn
            $notification = new Notification($con, $userLoggedIn);
			//contain id of new post
			$notification->insertNotification($post_id, $user_liked, "like");
        }
    }

    //Unlike button
    if (isset($_POST['unlike_button'])) {
        $total_likes--;
        $query = mysqli_query($con, "UPDATE posts SET likes='$total_likes' WHERE id='$post_id'");
        $total_user_likes--;
        $user_likes = mysqli_query($con, "UPDATE users SET num_likes='$total_user_likes' WHERE username='$user_liked'");
        $insert_user = mysqli_query($con, "DELETE FROM likes WHERE username='$userLoggedIn' AND post_id='$post_id'");
    }

    //check for previous likes - get the details whether the user like that post or not
    $check_query = mysqli_query($con, "SELECT * FROM likes WHERE username='$userLoggedIn' AND post_id='$post_id'");
    $num_rows = mysqli_num_rows($check_query);
    if ($num_rows > 0) { //if number of likes is greater then zero, display unlike button
        echo ' <form action="like.php?post_id=' . $post_id . '" method="POST">
                  <input type="submit" class="comment_like" name="unlike_button" value="Unlike">
                  <div class="like_value">
                      '. $total_likes .' Likes
                  </div>
               </form>
        ';
    }
    else { 
        echo ' <form action="like.php?post_id=' . $post_id . '" method="POST">
                  <input type="submit" class="comment_like" name="like_button" value="Like">
                  <div class="like_value">
                      '. $total_likes .' Likes
                  </div>
               </form> 
        ';
    }
?>
</body>
</html>