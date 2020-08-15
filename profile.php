<?php 
	include("includes/header.php");
	

	//when we access someone else profile, its username is passed as parameter in URL
	if (isset($_GET['profile_username'])) { //contain username from url parameter
		$username = $_GET['profile_username'];
		$user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$username'");
		$user_array = mysqli_fetch_array($user_details_query); //access query result as an array
		//counts the number of times a substring occurs in a string. /string - Specifies the string 
		//to check. substr_count(string,substring) /substring - Specifies the string to search for
		$num_friends = (substr_count($user_array['friend_array'], ",")) - 1; //nums of friends it have
	}

	if (isset($_POST['remove_friend'])) { //if remove friend button is pressed
		$user = new User($con, $userLoggedIn);
		$user->removeFriend($username); //remove friend from friend_Array
	}

	if (isset($_POST['add_friend'])) { //if add friend button is pressed
		$user = new User($con, $userLoggedIn);
		$user->sendRequest($username); //send friend request
	}

	if (isset($_POST['respond_request'])) {
		header("Location: requests.php"); //redirect to request page 
	}
?>
	   <style type="text/css">
	   		.wrapper {
				   margin-left: 0px; padding-left: 0px;
			   }
  
	   </style>
       
       <div class="profile_left">
		   <img src="<?php echo $user_array['profile_pic'] ?>">
		   <p class="profile_name"><?php echo $username; ?></p>
		   <div class="profile_info">
			   <p><?php echo "Posts: " . $user_array['num_posts']; ?></p>
			   <p><?php echo "Likes: " . $user_array['num_likes']; ?></p>
			   <p><?php echo "Friends: " . $num_friends; ?></p>
		   </div>
		   <form action="<?php echo $username ?>" method="POST">
		       <!--Check if person profile is close. If they are, must not access them-->
			   <?php 
					$profile_user_obj = new User($con, $username); 
					if ($profile_user_obj->isClosed()) {
						header("Location: user_closed.php");
					}
					//Check if the user who is loggedIn is in its own profile
					$logged_in_user_obj = new User($con, $userLoggedIn);
					//if user loggedIn is not same as url parameter username then...
					if ($userLoggedIn != $username) {
						//check if the userLoggedIn is friends with the person profile its on
						if ($logged_in_user_obj->isFriend($username)) { //if they are friend then...
							echo '<input type="submit" name="remove_friend" class="danger" value="Remove Friend"<br>';
						}
						//isFriend() return false else check if loggedIn user received friend request then...
						else if ($logged_in_user_obj->didReceiveRequest($username)) { //if this function return true
							echo '<input type="submit" name="respond_request" class="warning" value="Respond to Request"<br>';
						}
						//else check if loggedIn user send friendRequest to the friend
						else if ($logged_in_user_obj->didSendRequest($username)) { //if this function return true
							echo '<input type="submit" name="" class="default" value="Request Sent"<br>';
						}
						else { //else add friend
							echo '<input type="submit" name="add_friend" class="success" value="Add Friend"<br>';
						}
					}
			   ?>
		   </form>
		   <input type="submit" class="deep_blue" id="post_form_btn" data-toggle="modal" data-target="#post_form" value="Post Something">

		   <?php
		   		if ($userLoggedIn != $username) { //if user is not in its own profile
					   echo '<div class="profile_info_bottom">';
					   			echo $logged_in_user_obj->getMutualFriends($username) . " Mutual Friends";
					   echo '</div>';
				   } 
		   ?>

	   </div>

		<div class="main_column2 column2">
			<!--Post going to load-->
		<div class="posts_area"></div>
		<img id="loading" src="assets/images/icons/loading.gif">
			
		</div>
		
		<!-- Modal: dialog box/popup -->
		<div class="modal fade" id="post_form" tabindex="-1" role="dialog" aria-labelledby="postModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<!-- Modal content-->
				<div class="modal-content">

					<div class="modal-header">
						<h5 class="modal-title" id="exampleModalLabel">Post Something!</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
						</button>
					</div>

					<div class="modal-body">
						<p>This will appear on user's profile page and also their newsfeed for your friends to see!</p>
						<form class="profile_post" action="" method="POST">
							<div class="form-group">
								<textarea class="form-control" name="post_body"></textarea>
								<input type="hidden" name="user_from" value="<?php echo $userLoggedIn; ?>">
								<input type="hidden" name="user_to" value="<?php echo $username; ?>">
							</div>
						</form>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
						<button type="button" class="btn btn-primary" name="post_button" id="submit_profile_post">Post</button>
					</div>

				</div>

			</div>
		</div>
		<script>
			var userLoggedIn = '<?php echo $userLoggedIn; ?>';
			var profileUsername = '<?php echo $username; ?>';

			$(document).ready(function() {

				$('#loading').show();

				//Original ajax request for loading first posts 
				$.ajax({
				url: "includes/handlers/ajax_load_profile_posts.php",
				type: "POST",
				data: "page=1&userLoggedIn=" + userLoggedIn + "&profileUsername=" + profileUsername,
				cache:false,

				success: function(data) {
					$('#loading').hide();
					$('.posts_area').html(data);
				}
				});

				$(window).scroll(function() {
				var height = $('.posts_area').height(); //Div containing posts
				var scroll_top = $(this).scrollTop();
				var page = $('.posts_area').find('.nextPage').val();
				var noMorePosts = $('.posts_area').find('.noMorePosts').val();

				if ((document.body.scrollHeight == document.body.scrollTop + window.innerHeight) && noMorePosts == 'false') {
					$('#loading').show();

					var ajaxReq = $.ajax({
					url: "includes/handlers/ajax_load_profile_posts.php",
					type: "POST",
					data: "page=" + page + "&userLoggedIn=" + userLoggedIn + "&profileUsername=" + profileUsername,
					cache:false,

					success: function(response) {
						$('.posts_area').find('.nextPage').remove(); //Removes current .nextpage 
						$('.posts_area').find('.noMorePosts').remove(); //Removes current .nextpage 

						$('#loading').hide();
						$('.posts_area').append(response);
					}
					});

				} //End if 

				return false;

				}); //End (window).scroll(function())


			});

        </script>
	</div>
</body>
</html>