<?php 
	include("includes/header.php");

	if(isset($_POST['post'])){
		$uploadOk = 1; //upload
		//Retrieve the name of our file  ('dog.png')
		$imageName = $_FILES['fileToUpload']['name'];
		$errorMessage = "";
		if ($imageName != "") { //if not empty
			$targetDir = "assets/images/posts/"; //path to our images
			//genarate random id. assets/images/posts/45fhffdog.png
			$imageName = $targetDir . uniqid() . basename($imageName); //basename contain dog.png
			//PATHINFO_EXTENSION - allow us to retrieve png or jpeg or other extensions
			$imageFileType = pathinfo($imageName, PATHINFO_EXTENSION); //return information about a file path
			//check file size in byte. if file size greater than max size, then...
			if($_FILES['fileToUpload']['size'] > 90000000) { //maximum size allow!
				$errorMessage = "Sorry your file is too large";
				$uploadOk = 0; //not upload
			}
			//three file type allow t0 upload. if not equal to these file extension type, then...
			if(strtolower($imageFileType) != "jpeg" && strtolower($imageFileType) != "png" && strtolower($imageFileType) != "jpg") {
				$errorMessage = "Sorry only jpeg, jpg and png files are allowed";
				$uploadOk = 0; //not upload
			}
			//Now actually we are going to upload the image
			if($uploadOk) { //if $uploadOk is still 1 then...  
				//move an uploaded file to a new location. tmp_name - its a temporary name that it gives the file while it is uploading
				if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $imageName)) { //destination - $imageName (PATH)
					//image uploaded okay
				}
				else {
					//image did not upload
					$uploadOk = 0;
				}
			}
		}

		if($uploadOk) { //if still equal 1
			$post = new Post($con, $userLoggedIn);
		    $post->submitPost($_POST['post_text'], 'none', $imageName);
		}
		else {
			echo "<div style='text-align:center;' class='alert alert-danger'>
					   $errorMessage
			      </div>";
		}
		
		header("Location: index.php"); //stop the form resubmitting on refresh 
	}
?>
    
	<div class="user_details column">
		<a href="<?php echo $userLoggedIn; ?>">  <img src="<?php echo $user['profile_pic']; ?>"> </a>
		<div class="user_details_left_right">
			<a href="<?php echo $userLoggedIn; ?>">
				<?php 
					echo $user['first_name'] . " " . $user['last_name'];
				?>
			</a>
			<br>
			<?php 
				echo "Posts: " . $user['num_posts']. "<br>"; 
				echo "Likes: " . $user['num_likes'];
			?>
		</div>
	</div>

	<div class="user_details8 column8">
		<h4><strong>Popular Keywords:</strong></h4>
		<div class="trends">
			<?php 
				$query = mysqli_query($con, "SELECT * FROM trends ORDER BY hits DESC LIMIT 9");

				foreach ($query as $row) {
					$word = $row['title'];
					$word_dot = strlen($word) >= 14 ? "..." : "";
					$trimmed_word = str_split($word, 14);
					$trimmed_word = $trimmed_word[0];
					echo "<div style'padding: 1px'>";
					echo $trimmed_word . $word_dot;
					echo "<br></div><br>";
				}
			?>
		</div>
	</div>

	<div class="main_column column">
		<!--enctype="multipart/form-data - allows the form to process the file uploads-->
		<form class="post_form" action="index.php" class="emojioneArea" method="POST" enctype="multipart/form-data">
			<input type="file" name="fileToUpload"  id="fileToUpload" class="custom-file-input">
			<textarea name="post_text" id="post_text"  placeholder="Got something to say? Want to share video Put Youtube URL link here"></textarea>
			<input type="submit" name="post" id="post_button" value="Post">
			<hr>
		</form>
        
		<!--Post going to load-->
		<div class="posts_area"></div>
		<img id="loading" src="assets/images/icons/loading.gif">

	</div>

	
	

	<script>
		$(document).ready(function() {

			$("#post_text").emojioneArea({
				pickerPosition: "right"
			}) 
		})

	</script>

	<script>
		
		$(function(){  //document to be fully loaded and ready
			var userLoggedIn = '<?php echo $userLoggedIn; ?>';
			var inProgress = false;

			loadPosts(); //Load first posts

			$(window).scroll(function() {
				var bottomElement = $(".status_post").last();//returns the last element of the selected elements.
				var noMorePosts = $('.posts_area').find('.noMorePosts').val();

				// isElementInViewport uses getBoundingClientRect(), which requires the HTML DOM object, not the 
				//jQuery object. The jQuery equivalent is using [0] as shown below.
				if (isElementInView(bottomElement[0]) && noMorePosts == 'false') {
					loadPosts();
				}
			});

			function loadPosts() {
				if(inProgress) { //If it is already in the process of loading some posts, just return
					return;
				}
				
				inProgress = true;
				$('#loading').show();

				var page = $('.posts_area').find('.nextPage').val() || 1; //If .nextPage couldn't be found, it must 
				//not be on the page yet (it must be the first time loading posts), so use the value '1'

				$.ajax({
					url: "includes/handlers/ajax_load_posts.php",
					type: "POST",
					data: "page=" + page + "&userLoggedIn=" + userLoggedIn, //the request to the defined URL
					cache:false,

					success: function(response) {
						$('.posts_area').find('.nextPage').remove(); //Removes current .nextpage 
						$('.posts_area').find('.noMorePosts').remove(); //Removes current .nextpage 
						$('.posts_area').find('.noMorePostsText').remove(); //Removes current .nextpage 

						$('#loading').hide();
						$(".posts_area").append(response);

						inProgress = false;
					}
				});
			}

			//isElementInView to detect whether the element is visible rather than trying to detect when the user 
			//scrolls to the bottom Check if the element is in view
			function isElementInView (el) { //parameter - body post
				//returns the size of an element and its position relative to the viewport.
				var rect = el.getBoundingClientRect(); //(left, top, right, bottom, x, y, width, height.)

				return (
					rect.top >= 0 &&
					rect.left >= 0 &&
					rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && //* or $(window).height()
					rect.right <= (window.innerWidth || document.documentElement.clientWidth) //* or $(window).width()
				);
			}
			});
		//we now check if an element is on the screen rather checking if they are at the bottom.In order to know if they are 
		//at the bottom of the page, we check if an element at the bottom of the view is on the screen or not. More 
		//specifically I check if the last post is on the screen: var bottomElement = $(".status_post").last(); So basically, 
		//all the code is doing is, when the user scrolls, check if the last post is on the screen. If it is on the screen, load more posts
	</script>

	</div>
</body>
</html>