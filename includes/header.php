<?php  
require 'config/config.php';
include("includes/classes/User.php");
include("includes/classes/Post.php");
include("includes/classes/Message.php");
include("includes/classes/Notification.php");

if (isset($_SESSION['username'])) {
	$userLoggedIn = $_SESSION['username'];
	$user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$userLoggedIn'");
	$user = mysqli_fetch_array($user_details_query);
}
else { //Redirecting users not logged in (users cannot access page from URL)
	header("Location: register.php");
}

?>

<html>
<head>
	<title>Welcome to CookieBook</title>

	<!-- Javascript -->
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

	<script src="assets/js/bootstrap.js"></script>
	<script src="assets/js/demo.js"></script>
	<script src="../SocialNet/assets/js/main.js"></script>
	<script src="assets/js/emojionearea.js"></script>
	<script src="assets/js/bootbox.min.js"></script>
	<script src="assets/js/jquery.jcrop.js"></script>
	<script src="assets/js/jcrop_bits.js"></script>
	

	<!-- CSS -->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" media="all">
	
	<link rel="stylesheet" type="text/css" href="assets/css/bootstrap.css" media="all">
	
	<link rel="stylesheet" href="assets/css/jquery.Jcrop.css" type="text/css" >

	<link rel="stylesheet" href="assets/css/emojionearea.css">
	
	<link href="assets/css/style.css"  rel="stylesheet" type="text/css" >

</head>
<body>
		<div class="top_bar"> 

			<div class="logo">
				<a href="index.php">CookieBook</a>
			</div>

			<div class="search"> <!--GET- access passed parameter in URL-->
				<form action="search.php" method="GET" name="search_form">
					<!---this.val: value of whatever user is typing AND name=q: the variable in URL-->
					<input type="text" onkeyup="getLiveSearchUsers(this.value, '<?php echo $userLoggedIn; ?>')" name="q" 
						 placeholder="Search For Friend..." autocomplete="off" id="search_text_input">
					<div class="button_holder">
						<img src="assets/images/icons/magnifying_glass.png" alt="">
					</div>	 
				</form>
				<div class="search_results">

				</div>
				<div class="search_results_footer_empty">

				</div>
			</div>

			<nav>
				<?php
				    //Unread messages
					$messages = new Message($con, $userLoggedIn); 
					$num_messages = $messages->getUnreadNumber();

					 //Unread notifications
					$notification = new Notification($con, $userLoggedIn);
					$num_notification = $notification->getUnreadNumber();

					 //Unread friend_requests
					 $user_obj = new USER($con, $userLoggedIn);
					 $num_requests = $user_obj->getNumberOfFriendRequest();
				?>

				<a href="<?php echo $userLoggedIn; ?>" title="Profile">
					<?php echo $user['first_name']; ?>
				</a>
				<a href="index.php" title="Home">
					<i class="fa fa-home fa-lg"></i>
				</a>
				<a href="javascript:void(0);" title="Message Box" onclick="getDropdownData('<?php echo $userLoggedIn; ?>', 'message')">
					<i class="fa fa-envelope fa-lg"></i>
					<?php
						if($num_messages > 0)
							echo '<span class="notification_badge" id="unread_message">' . $num_messages . '</span>';
					?>
				</a>
				<a href="messages.php" title="Messenger">
				   <i class="fa fa-comments fa-lg"></i>
				</a>
				<a href="javascript:void(0);" title="Notification" onclick="getDropdownData('<?php echo $userLoggedIn; ?>', 'notification')">
					<i class="fa fa-bell fa-lg"></i>
					<?php
						if($num_notification > 0)
							echo '<span class="notification_badge" id="unread_notification">' . $num_notification . '</span>';
					?>
				</a>
				<a href="requests.php" title="Friend Request">
					<i class="fa fa-users fa-lg"></i>
					<?php
						if($num_requests > 0)
							echo '<span class="notification_badge" id="unread_requests">' . $num_requests . '</span>';
					?>
				</a>			
				<a href="settings.php" title="Profile setting">
					<i class="fa fa-cog fa-lg"></i>
				</a>
				<a href="includes/handlers/logout.php" title="Log Out">
					<i class="fa fa-sign-out fa-lg"></i>
				</a>				
			</nav>
			
			<div class="dropdown_data_window" style="height: 0px; border:none;"></div>
			<input type="hidden" id="dropdown_data_type" value="">
		</div>
	
		<script>
			var userLoggedIn = '<?php echo $userLoggedIn; ?>';
            //document object is your html that will be loaded into the browser
			$(document).ready(function() { //to make a function available after the document is loaded
				/*window is the first thing that gets loaded into the browser, has the majority of the properties like length, 
				innerWidth, innerHeight, if it has been closed, its parents, and more. if you want to access a property for the 
				window it is window.property. */
				$('.dropdown_data_window').scroll(function() { //Specifies the function to run when the scroll event is triggered
					//innerHeight() method returns the inner height of the FIRST matched element (include padding)
					var inner_height = $('.dropdown_data_window').innerHeight(); //DIV containing Data
					//returns the vertical scrollbar position for .drop down window
					var scroll_top = $('.dropdown_data_window').scrollTop();
					/* find() method returns descendant elements of the selected element contain (page + 1). load next page*/
					var page = $('.dropdown_data_window').find('.nextPageDropdownData').val(); //val return value attribute of element
					var noMoredata = $('.dropdown_data_window').find('.noMoreDropdownData').val(); //contain value false/true
					/*position 0 mean it get the first attribute of .dropdown_data_windoW. if scroll top & height is greater then 
					dropdown_data_window height and noMoreData equal false(means there is more data to load) 
					 So $('.dropdown_data_window') is the jquery object but we need to use the .scrollHeight property which is a javascript 
					 property. So by adding the [0] we are accessing the javascript object of the $('.dropdown_data_window') object.*/
					if((scroll_top + inner_height >= $('.dropdown_data_window')[0].scrollHeight) && noMoredata == 'false') {
						var pageName; //Holds name of page to send ajax request to
						var type = $('#dropdown_data_type').val(); //at moment the value is empty

						if(type == 'notification')
							pageName = "ajax_load_notification.php";
						else if(type == 'message')
							pageName = "ajax_load_message.php";

						var ajaxReq = $.ajax({
							url: "includes/handlers/" + pageName,
							type: "POST",
							data: "page=" + page + "&userLoggedIn=" + userLoggedIn,
							cache: false,

							success: function(response) {
								$('.dropdown_data_window').find('.nextPageDropdownData').remove(); //removes current page . nextpage
								$('.dropdown_data_window').find('.noMoreDropdownData').remove(); //removes current .nextpage
								$('.dropdown_data_window').append(response); //insert data content
							}
						}); //end of ajax()

					} //end of if statement
				}) //end of (window).scroll(function())
			}); //end of ready(function()) 
		</script>

	<div class="wrapper">