<?php
    include("includes/header.php");
?>

<style type="text/css">
     #accept_button {
	    width: 20%; height: 28px; border-radius: 5px; margin: 5px; border: none; color: #fff; background-color: #2ecc71;
     }

    #ignore_button {
	    width: 20%; height: 28px; border-radius: 5px; margin: 5px; border: none; color: #fff; background-color: #e74c3c;
    }
</style>

<div class="main_column column" id="main_column">
    <h4>Friend Request</h4>
    <?php
        $query = mysqli_query($con, "SELECT * FROM friend_requests WHERE user_to='$userLoggedIn'");
        if (mysqli_num_rows($query) == 0) 
             echo "You have no friend request at this time!";
        else {
            while ($row = mysqli_fetch_array($query)) {
                $user_from = $row['user_from']; //user who sent friend request to you

                $user_from_obj = new User($con, $user_from);
                echo $user_from_obj->getFirstAndLastName() . " sent you a friend request!";
                $user_from_friend_array = $user_from_obj->getFriendArray();
                
                //for every single friend request user has
                if (isset($_POST['accept_request' . $user_from])) { //specific user sent friend request
                    //CONCAT() - adds two expressions together.
                    //Add friend to friend array of user who is loggedIn
                    $add_friend_query = mysqli_query($con, "UPDATE users SET friend_array=CONCAT(friend_array, '$user_from,')
                    WHERE username='$userLoggedIn'");

                    //Add friend to friend array of user who sent friend request
                    $add_friend_query = mysqli_query($con, "UPDATE users SET friend_array=CONCAT(friend_array, '$userLoggedIn,')
                    WHERE username='$user_from'");

                    $delete_query = mysqli_query($con, "DELETE FROM friend_requests WHERE user_to='$userLoggedIn' AND 
                    user_from='$user_from'");
                    echo "You are now friends!";
                    header("Location: requests.php");
                }

                if (isset($_POST['ignore_request' . $user_from])) {
                    $delete_query = mysqli_query($con, "DELETE FROM friend_requests WHERE user_to='$userLoggedIn' AND 
                    user_from='$user_from'");
                    echo "Request ignored!";
                    header("Location: requests.php");
                }
    ?>
                <form action="requests.php" method="POST">
                    <input type="submit" name="accept_request<?php echo $user_from ?>" id="accept_button" value="Accept">
                    <input type="submit" name="ignore_request<?php echo $user_from ?>" id="ignore_button" value="Ignore">
                </form>
    <?php
            } //end of while loop
        } //end of else statement
    ?>
</div>                                                                                                                   