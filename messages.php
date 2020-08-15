<?php 
    include("includes/header.php");

    $message_obj = new Message($con, $userLoggedIn);
    
    //This messages page is going to take username passes a variable through URL
    if (isset($_GET['u'])) //not null (u - username)
        $user_to = $_GET['u'];
    else { //else if null
        //going to retrieve most recent user the person have interaction with 
        $user_to = $message_obj->getMostRecentUser();
        if($user_to == false) //if it does not find anybody you have interaction with
            $user_to = 'new'; //sending a new message
    }

    if($user_to != "new") //if user is not sending new message
        $user_to_obj = new User($con, $user_to); //create user object
    
    if (isset($_POST['post_message'])) { //if send button been pressed
         header("Location: messages.php");
         if (isset($_POST['message_body'])) { //if message body is not null
            //e.g ignore doublr or single quote (''/"")
            $body = mysqli_real_escape_string($con, $_POST['message_body']); 
            $date = date("Y-m-d H:i:s");
            $message_obj->sendMessage($user_to, $body, $date);
        }
    }
?>

<div class="user_details column">
		<a href="<?php echo $userLoggedIn; ?>">  <img src="<?php echo $user['profile_pic']; ?>"> </a>
		<div class="user_details_left_right">
			<a href="<?php echo $userLoggedIn; ?>">
			<?php 
			echo $user['first_name'] . " " . $user['last_name'];?>
			</a>
			<br>
			<?php echo "Posts: " . $user['num_posts']. "<br>"; 
			echo "Likes: " . $user['num_likes']; ?>
		</div>
</div>

<div class="main_column3 column3" id="main_column">
    <?php
        if($user_to != "new") {
            echo "<h4>You and <a href ='$user_to'>" . $user_to_obj->getFirstAndLastName() . "</a></h4><hr><br>";
            echo "<div class='loaded_messages' id='scroll_messages'>";
                echo $message_obj->getMessages($user_to); //retrieve messages
            echo "</div>";
        } else {
            echo "<h4>New Message</h4>";
        }
    ?>
    <div class="message_post">
        <form action="" method="POST">
            <?php
                if ($user_to == "new") {
                    echo "Select the friend you would like to message <br><br>";
                    /*onkeyup -A function is triggered when the user releases a key in the input field.
                    this.value - is the value of input field*/
                    ?> 
                    To: <input type='text' onkeyup='getUser(this.value, "<?php echo $userLoggedIn; ?>")'
                               name='q' placeholder='Name' autocomplete='off' id='search_text_input' >
                    <?php
                    echo "<div class='results'></div>";
                } 
                else {
                    echo "<textarea name='message_body' id='message_textarea' placeholder='Write you message...'></textarea>";
                    echo "<input type='submit' name='post_message' class='info' id='message_submit' value='Send'>";
                }
            ?>
        </form>
        <Script>
            $(document).ready(function() {
		        $("#message_textarea").emojioneArea({
			        pickerPosition: "top"
		         });
	        });
        </Script>
    </div>

    <script>
        var div = document.getElementById("scroll_messages");
        div.scrollTop = div.scrollHeight;
    </script>
     
</div>
<div class="user_details4 column4" id="conversations">
        <h4>Latest Conversations</h4>
        <div class="loaded_conversations">
            <?php echo $message_obj->getConvos(); ?>
        </div>
        <br>
        <a href="messages.php?u=new">New Message</a>
</div>