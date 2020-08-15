<?php
class Post {
	private $user_obj;
	private $con;
	
	//This function create object of the User class
	public function __construct($con, $user){
		//The $this keyword refers to the current object, and is only available inside methods
		$this->con = $con; //contain database connection information
		$this->user_obj = new User($con, $user);
	}

	//simply call this method everytime user want to post something on main column
    //$body - which is the text what user posted and $user_to - user to posted to.
	public function submitPost($body, $user_to, $imageName) {
		$body = strip_tags($body); //removes html tags
		//This escape any special chars on the posted text in DB 
		$body = mysqli_real_escape_string($this->con, $body);
		 //Replace string with another string
		 $body = str_replace('\r\n', "\n", $body);
		 //replace new line with line break
		 //$body = nl2br($body);
		 //check if the post is empty. forward slash are just sourrounding the text we want to replace.
		 //any space in it we will replace it with nothing. where it find any spaces and replace it will noting
		$check_empty = preg_replace('/\s+/', '', $body); //Deltes all spaces 
      
		if($check_empty != ""  || $imageName != "") { //then it can continue to do the post
			//We want the youtube link as an array element not whole part of post
			//check out this link https://www.youtube.com/watch?v=ffdfs-erer
			$body_array = preg_split("/\s+/", $body); //split spaces
			
            //it iterates the body_array and keep track element number in array (e.g postion 0,1,2...)
			foreach ($body_array as $key => $value) {
				//going to search in $value, element in array each time we go around
				if(strpos($value, "www.youtube.com/watch?v=") !== false ) { //if url found then...
					//https://www.youtube.com/watch?v=YgFyi74DVjc  [0] give first index & 
					//list=RDYgFyi74DVjc   &
					//start_radio=1   &
					//t=0
					$link = preg_split("!&!", $value); //search for & (anohter array)
					// '!' -where it start and ends. the backslash will cancel out the question mark character. 
					//so it going to the first parameter and replace it with second parameter "embed/"
					$value = preg_replace("!watch\?v=!", "embed/", $link[0]);
					$value = "<br><iframe width=\'590\' height=\'450\' src=\'" . $value ."\'></iframe><br>";
					$body_array[$key] = $value; //contain the new modified link in the specifc postion 
				}
			}
			$body = implode(" ", $body_array); //separate each element with space

			//Current date and time
			$date_added = date("Y-m-d H:i:s");

			//Get username from User class method 
			$added_by = $this->user_obj->getUsername();

			//If user is on own profile, user_to is 'none'
			if($user_to == $added_by) {
				$user_to = "none";
			}

			//insert post to database 
			$query = mysqli_query($this->con, "INSERT INTO posts VALUES(NULL, '$body', '$added_by', '$user_to', '$date_added', 
			                      'no', 'no', '0', '$imageName')");
			$returned_id = mysqli_insert_id($this->con);

			//Insert notification - giving notification only if we posting to someelse profile
			if($user_to != 'none') { //not posting in main post in index.php
				$notification = new Notification($this->con, $added_by);
				//contain id of new post
				$notification->insertNotification($returned_id, $user_to, "profile_post");
			} 

			//Update post count for specific user - that will return number of posts
			$num_posts = $this->user_obj->getNumPosts();
			$num_posts++;
			$update_query = mysqli_query($this->con, "UPDATE users SET num_posts='$num_posts' WHERE username='$added_by'");

			$stopWords = "a about above across after again against all almost alone along already
			 also although always among am an and another any anybody anyone anything anywhere are 
			 area areas around as ask asked asking asks at away b back backed backing backs be became
			 because become becomes been before began behind being beings best better between big 
			 both but by c came can cannot case cases certain certainly clear clearly come could
			 d did differ different differently do does done down down downed downing downs during
			 e each early either end ended ending ends enough even evenly ever every everybody
			 everyone everything everywhere f face faces fact facts far felt few find finds first
			 for four from full fully further furthered furthering furthers g gave general generally
			 get gets give given gives go going good goods got great greater greatest group grouped
			 grouping groups h had has have having he her here herself high high high higher
		     highest him himself his how however i im if important in interest interested interesting
			 interests into is it its itself j just k keep keeps kind knew know known knows
			 large largely last later latest least less let lets like likely long longer
			 longest m made make making man many may me member members men might more most
			 mostly mr mrs much must my myself n necessary need needed needing needs never
			 new new newer newest next no nobody non noone not nothing now nowhere number
			 numbers o of off often old older oldest on once one only open opened opening
			 opens or order ordered ordering orders other others our out over p part parted
			 parting parts per perhaps place places point pointed pointing points possible
			 present presented presenting presents problem problems put puts q quite r
			 rather really right right room rooms s said same saw say says second seconds
			 see seem seemed seeming seems sees several shall she should show showed
			 showing shows side sides since small smaller smallest so some somebody
			 someone something somewhere state states still still such sure t take
			 taken than that the their them then there therefore these they thing
			 things think thinks this those though thought thoughts three through
	         thus to today together too took toward turn turned turning turns two
			 u under until up upon us use used uses v very w want wanted wanting
			 wants was way ways we well wells went were what when where whether
			 which while who whole whose why will with within without work
			 worked working works would x y year years yet you young younger
			 youngest your yours z lol haha omg hey ill iframe wonder else like 
			 hate sleepy reason for some little yes bye choose";
			 
			//Convert stop words into array - split at white space
			$stopWords = preg_split("/[\s,]+/", $stopWords);

			//Remove all punctionation only. ^ means not
			$no_punctuation = preg_replace("/[^a-zA-Z 0-9]+/", "", $body); //user new without punctionation

			//Predict whether user is posting a url. If so, do not check for trending words
			if(strpos($no_punctuation, "height") === false && strpos($no_punctuation, "width") === false
				&& strpos($no_punctuation, "http") === false && strpos($no_punctuation, "youtube") === false) {
				//Convert users post (with punctuation removed) into array - split at white space
				$keywords = preg_split("/[\s,]+/", $no_punctuation);

				foreach($stopWords as $value) {
					foreach($keywords as $key => $value2){
						if(strtolower($value) == strtolower($value2))
							$keywords[$key] = "";
					}
				}

				foreach ($keywords as $value) {
				    $this->calculateTrend(ucfirst($value));
				}
             }
		}//end of if statement
	} //end of submitPost()

	public function calculateTrend($term) { //a word
		if($term != '') {
			$query = mysqli_query($this->con, "SELECT * FROM trends WHERE title='$term'");
			if(mysqli_num_rows($query) == 0)
				$insert_query = mysqli_query($this->con, "INSERT INTO trends(title,hits) VALUES('$term','1')");
			else 
				$insert_query = mysqli_query($this->con, "UPDATE trends SET hits=hits+1 WHERE title='$term'");
		}

	}
	
	//loading the post to friends - data come from the Request from ajax data
	public function loadPostsFriends($data, $limit) {
		$page = $data['page']; //this variable is to be used in the ajax call
		$userLoggedIn = $this->user_obj->getUsername();
        //if we are loading 10 posts at a time, if it is the first page first time the post being loaded
        //this means post has been loaded the first time start at 0 posts
		if($page == 1)  //which means no post have been loaded
			$start = 0;  //start at the very first element at the table(start point)
		//if posts have been loaded 	
		else //this will make it start at the 10th or limit post to load the posts again   
			$start = ($page - 1) * $limit;

		$str = ""; //String to return
		//Select posts from descending order by id 
		$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' ORDER BY id DESC");

		if(mysqli_num_rows($data_query) > 0) { //nums of row is greater, means have more result
            //Counts how many time the loop been around        
			$num_iterations = 0; //Number of results checked (not necasserily posted)
			$count = 1; //counts how many results been loaded
            //everytime its goes around it will get the next row result
			while($row = mysqli_fetch_array($data_query)) { //fetch data from DB as array or assocative
				$id = $row['id'];
				$body = $row['body'];
				$added_by = $row['added_by'];
				$date_time = $row['date_added'];
				$imagePath = $row['image'];

				//Prepare user_to string so it can be included even if not posted to a user
				if($row['user_to'] == "none") {
					$user_to = "";
				}
				else { //get the name of user that we are posting to 
					$user_to_obj = new User($this->con, $row['user_to']);
					$user_to_name = $user_to_obj->getFirstAndLastName();
					$user_to = "to <a href='" . $row['user_to'] ."'>" . $user_to_name . "</a>";
				}

				//Check if user who posted, has their account closed
				$added_by_obj = new User($this->con, $added_by);  //Create new user object from User class
				if($added_by_obj->isClosed()) {
					continue; //breaks one iteration (in the loop)
				}

				$user_logged_obj = new User($this->con, $userLoggedIn);
				if ($user_logged_obj->isFriend($added_by)) {
				
					//increase it by 1 &  $start - number of row start at. if less then the position start at, then continue
					//which mean just go back to start the loop and contine iteration. if did not reach $start position yet
					//then go back to top code. we got number keep counting, how many posts been loaded, so count over them 
					//again get to the ones it hasn't loaded, it just keep continuing to continue when it does get to the once been loaded.
					if($num_iterations++ < $start)
						continue; 

					//Once 10 posts have been loaded, break
					if($count > $limit) {
						break;  //leave the loop
					}
					else {
						$count++; //increase count by 1
					}

					if ($userLoggedIn == $added_by) 
						$delete_button = "<button class='delete_button btn-danger' id='post$id'>X</button>";
					else
						$delete_button = "";
					
					//the user who add the post
					$user_details_query = mysqli_query($this->con, "SELECT first_name, last_name, profile_pic FROM users WHERE 
					                                   username='$added_by'");
					$user_row = mysqli_fetch_array($user_details_query);
					$first_name = $user_row['first_name'];
					$last_name = $user_row['last_name'];
					$profile_pic = $user_row['profile_pic'];

					?>
					<script>
						//when user click show specific comment based on post specifc id
						function toggle<?php echo $id; ?>() { //hide and show element
							var target = $(event.target);
							if (!target.is("a")) {
								var element = document.getElementById("toggleComment<?php echo $id; ?>");
								if (element.style.display == "block") {
									element.style.display = "none";
								} else {
									element.style.display = "block";
								}
							}
						}

						var delayInMilliseconds = 500; // half a second
						function backColor<?php echo $id; ?>() {
							setTimeout(function() { 
								for(var i = 0; i < 10000; i++) {
									var iframe = document.getElementsByTagName('iframe')[i];
									iframe.style.background = 'white';
									iframe.contentWindow.document.body.style.backgroundColor = 'white';
								}
							}, delayInMilliseconds);
						}

					</script>
					
					<?php
					//How comments there are
					$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id='$id'");
					$comments_check_num = mysqli_num_rows($comments_check);
			
					//Timeframe
					$date_time_now = date("Y-m-d H:i:s");
					$start_date = new DateTime($date_time); //Time of post
					$end_date = new DateTime($date_time_now); //Current time
					$interval = $start_date->diff($end_date); //Difference between dates 
					if($interval->y >= 1) {
						if($interval->y == 1)
							$time_message = $interval->y . " year ago"; //1 year ago
						else 
							$time_message = $interval->y . " years ago"; //1+ year ago
					}
					else if ($interval-> m >= 1) {
						if($interval->d == 0) {
							$days = " ago";
						}
						else if($interval->d == 1) {
							$days = $interval->d . " day ago";
						}
						else {
							$days = $interval->d . " days ago";
						}


						if($interval->m == 1) {
							$time_message = $interval->m . " month ". $days;
						}
						else {
							$time_message = $interval->m . " months ". $days;
						}

					}
					else if($interval->d >= 1) {
						if($interval->d == 1) {
							$time_message = "Yesterday";
						}
						else {
							$time_message = $interval->d . " days ago";
						}
					}
					else if($interval->h >= 1) {
						if($interval->h == 1) {
							$time_message = $interval->h . " hour ago";
						}
						else {
							$time_message = $interval->h . " hours ago";
						}
					}
					else if($interval->i >= 1) {
						if($interval->i == 1) {
							$time_message = $interval->i . " minute ago";
						}
						else {
							$time_message = $interval->i . " minutes ago";
						}
					}
					else {
						if($interval->s < 30) {
							$time_message = "Just now";
						}
						else {
							$time_message = $interval->s . " seconds ago";
						}
					}
					
					if($imagePath != "") {
						$imageDiv = "<div class='postedImage'>
										<img src='$imagePath'>
						             </div>";
					} else {
						$imageDiv = "";
					}

					$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
								<div class='post_profile_pic'>
									<img src='$profile_pic' width='50'>
								</div>
								<div class='posted_by' style='color:#ACACAC;'>
									<a href='$added_by'> $first_name $last_name </a> $user_to &nbsp;&nbsp;&nbsp;&nbsp;$time_message
									$delete_button
								</div>
								<div id='post_body'>
									$body
									<br>
									$imageDiv
									<br>
									<br>
								</div>
								<div class='newsfeedPostOptions'>
									Comments($comments_check_num)&nbsp;&nbsp;&nbsp;
									<iframe src='like.php?post_id=$id' scrolling='no'></iframe>
								</div>
							</div>
							<div class='post_comment' id='toggleComment$id' style='display:none;'>
								<iframe src='comment_frame.php?post_id=$id' id='comment_iframe' class='iframe' 
								onLoad='javascript:backColor$id();' frameborder='0'></iframe>
							</div>
							<hr>";
					}//end of if statement for isFriend()
					
					?>
					<script>
						$(document).ready(function() {
							$('#post<?php echo $id; ?>').on('click', function() {
								//send the result based on the click
								bootbox.confirm("Are you sure you want to delete this post?", function(result) {
									/*It send the data(result) to delete_post.php */
									$.post("includes/form_handlers/delete_post.php?post_id=<?php echo $id; ?>", {result:result});
									if(result) //if result is true
										location.reload();
								});
							});
						});
					</script>
					<?php
					
				} //End while loop

				//if we reach full amount of posts, either it is going to stop because no more post left, so if it loads 6 out of 10
				//and there is none left, it is going to stop. but if it loads 10 out of 10, there could be more posts following, that
				//means there migth not be exactly 10 out of 10 left, there could be some more posts after that.
				if($count > $limit) 
					$str .= "<input type='hidden' class='nextPage' value='" . ($page + 1) . "'>
								<input type='hidden' class='noMorePosts' value='false'>";
				//so if every page on now increase it by one when it next time it loads
				else 
					$str .= "<input type='hidden' class='noMorePosts' value='true'><p style='text-align: centre;'> No more 
					posts to show! </p>";
		} // end of if statement
		echo $str;
	} //end of function loadPostsFriends();

	//loading the post to friends - data come from the Request from ajax data
	public function loadProfilePosts($data, $limit) {
		$page = $data['page']; //this variable is to be used in the ajax call
		$profileUser = $data['profileUsername']; //from ajax request in profile.php
		$userLoggedIn = $this->user_obj->getUsername();
        //if we are loading 10 posts at a time, if it is the first page first time the post being loaded
        //this means post has been loaded the first time start at 0 posts
		if($page == 1)  //which means no post have been loaded
			$start = 0;  //start at the very first element at the table(start point)
		//if posts have been loaded 	
		else //this will make it start at the 10th or limit post to load the posts again   
			$start = ($page - 1) * $limit;

		$str = ""; //String to return
		//Select posts from descending order by id  (if you posted on your friend profile it will display only  on their profile)
		$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' AND ((added_by='$profileUser' AND user_to='none') OR user_to='$profileUser') ORDER BY id DESC");

		if(mysqli_num_rows($data_query) > 0) { //nums of row is greater, means have more result
            //Counts how many time the loop been around        
			$num_iterations = 0; //Number of results checked (not necasserily posted)
			$count = 1; //counts how many results been loaded
            //everytime its goes around it will get the next row result
			while($row = mysqli_fetch_array($data_query)) { //fetch data from DB as array or assocative
				$id = $row['id'];
				$body = $row['body'];
				$added_by = $row['added_by'];
				$date_time = $row['date_added'];


			
				
				//increase it by 1 &  $start - number of row start at. if less then the position start at, then continue
                //which mean just go back to start the loop and contine iteration. if did not reach $start position yet
                //then go back to top code. we got number keep counting, how many posts been loaded, so count over them 
                //again get to the ones it hasn't loaded, it just keep continuing to continue when it does get to the once been loaded.
				if($num_iterations++ < $start)
					continue; 

				//Once 10 posts have been loaded, break
				if($count > $limit) {
					break;  //leave the loop
				}
				else {
					$count++; //increase count by 1
				}

				if ($userLoggedIn == $added_by) 
					 $delete_button = "<button class='delete_button btn-danger' id='post$id'>X</button>";
				else
					$delete_button = "";
				
				//the user who add the post
				$user_details_query = mysqli_query($this->con, "SELECT first_name, last_name, profile_pic FROM users WHERE username='$added_by'");
				$user_row = mysqli_fetch_array($user_details_query);
				$first_name = $user_row['first_name'];
				$last_name = $user_row['last_name'];
				$profile_pic = $user_row['profile_pic'];

				?>
				  <script>
					  //when user click show specific comment based on post specifc id
					  function toggle<?php echo $id; ?>() { //hide and show element
						var target = $(event.target);
						if (!target.is("a")) {
							var element = document.getElementById("toggleComment<?php echo $id; ?>");
							if (element.style.display == "block") {
								element.style.display = "none";
							} else {
								element.style.display = "block";
							}
						 }
					 }

					  var delayInMilliseconds = 500; // half a second
                      function backColor<?php echo $id; ?>() {
					    setTimeout(function() { 
							for(var i = 0; i < 10000; i++) {
								var iframe = document.getElementsByTagName('iframe')[i];
								iframe.style.background = 'white';
								iframe.contentWindow.document.body.style.backgroundColor = 'white';
							}
					    }, delayInMilliseconds);
					  }

				  </script>
				  
				<?php
                //How comments there are
				$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id='$id'");
				$comments_check_num = mysqli_num_rows($comments_check);
           
				//Timeframe
				$date_time_now = date("Y-m-d H:i:s");
				$start_date = new DateTime($date_time); //Time of post
				$end_date = new DateTime($date_time_now); //Current time
				$interval = $start_date->diff($end_date); //Difference between dates 
				if($interval->y >= 1) {
					if($interval->y == 1)
						$time_message = $interval->y . " year ago"; //1 year ago
					else 
						$time_message = $interval->y . " years ago"; //1+ year ago
				}
				else if ($interval-> m >= 1) {
					if($interval->d == 0) {
						$days = " ago";
					}
					else if($interval->d == 1) {
						$days = $interval->d . " day ago";
					}
					else {
						$days = $interval->d . " days ago";
					}


					if($interval->m == 1) {
						$time_message = $interval->m . " month ". $days;
					}
					else {
						$time_message = $interval->m . " months ". $days;
					}

				}
				else if($interval->d >= 1) {
					if($interval->d == 1) {
						$time_message = "Yesterday";
					}
					else {
						$time_message = $interval->d . " days ago";
					}
				}
				else if($interval->h >= 1) {
					if($interval->h == 1) {
						$time_message = $interval->h . " hour ago";
					}
					else {
						$time_message = $interval->h . " hours ago";
					}
				}
				else if($interval->i >= 1) {
					if($interval->i == 1) {
						$time_message = $interval->i . " minute ago";
					}
					else {
						$time_message = $interval->i . " minutes ago";
					}
				}
				else {
					if($interval->s < 30) {
						$time_message = "Just now";
					}
					else {
						$time_message = $interval->s . " seconds ago";
					}
				}

				$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
							<div class='post_profile_pic'>
								<img src='$profile_pic' width='50'>
							</div>
							<div class='posted_by' style='color:#ACACAC;'>
								<a href='$added_by'> $first_name $last_name </a> &nbsp;&nbsp;&nbsp;&nbsp;$time_message
								$delete_button
							</div>
							<div id='post_body'>
								$body
								<br><br><br>
							</div>
							<div class='newsfeedPostOptions'>
								Comments($comments_check_num)&nbsp;&nbsp;&nbsp;
								<iframe src='like.php?post_id=$id' scrolling='no'></iframe>
							</div>
						</div>
						<div class='post_comment' id='toggleComment$id' style='display:none;'>
						   <iframe src='comment_frame.php?post_id=$id' id='comment_iframe' class='iframe' onLoad='javascript:backColor$id();' frameborder='0'></iframe>
						</div>
						<hr>";
				
				
				?>
				<script>
					$(document).ready(function() {
						$('#post<?php echo $id; ?>').on('click', function() {
							//send the result based on the click
							bootbox.confirm("Are you sure you want to delete this post?", function(result) {
								/*It send the data(result) to delete_post.php */
								$.post("includes/form_handlers/delete_post.php?post_id=<?php echo $id; ?>", {result:result});
								if(result) //if result is true
									location.reload();
							});
						});
					});
				</script>
				<?php
				
			} //End while loop

			 //if we reach full amount of posts, either it is going to stop because no more post left, so if it loads 6 out of 10
             //and there is none left, it is going to stop. but if it loads 10 out of 10, there could be more posts following, that
             //means there migth not be exactly 10 out of 10 left, there could be some more posts after that.
			if($count > $limit) 
				$str .= "<input type='hidden' class='nextPage' value='" . ($page + 1) . "'>
							<input type='hidden' class='noMorePosts' value='false'>";
			//so if every page on now increase it by one when it next time it loads
			else 
				$str .= "<input type='hidden' class='noMorePosts' value='true'><p style='text-align: centre;'> No more posts to show! </p>";
		} // end of if statement
		echo $str;
	} //end of function loadPostsFriends();

	public function getSinglePost($post_id) {
		$userLoggedIn = $this->user_obj->getUsername();
		$opened_query = mysqli_query($this->con, "UPDATE notifications SET opened='yes' WHERE user_to='$userLoggedIn' 
		                              AND link LIKE '%=$post_id'");
		$str = ""; //String to return
		
		//Select posts from descending order by id  (start returing one post based on post id)
		$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' AND id='$post_id' ORDER BY id DESC");
		if(mysqli_num_rows($data_query) > 0) { //nums of row is greater, means have more result
           
            //everytime its goes around it will get the next row result
			$row = mysqli_fetch_array($data_query);  //fetch data from DB as array or assocative
				$id = $row['id'];
				$body = $row['body'];
				$added_by = $row['added_by'];
				$date_time = $row['date_added'];

				//Prepare user_to string so it can be included even if not posted to a user
				if($row['user_to'] == "none") {
					$user_to = "";
				}
				else { //get the name of user that we are posting to 
					$user_to_obj = new User($this->con, $row['user_to']);
					$user_to_name = $user_to_obj->getFirstAndLastName();
					$user_to = "to <a href='" . $row['user_to'] ."'>" . $user_to_name . "</a>";
				}

				//Check if user who posted, has their account closed
				$added_by_obj = new User($this->con, $added_by);  //Create new user object from User class
				if($added_by_obj->isClosed()) {
					return; //exits the function
				}

				$user_logged_obj = new User($this->con, $userLoggedIn);
				if ($user_logged_obj->isFriend($added_by)) {

					if ($userLoggedIn == $added_by) 
						$delete_button = "<button class='delete_button btn-danger' id='post$id'>X</button>";
					else
						$delete_button = "";
					
					//the user who add the post
					$user_details_query = mysqli_query($this->con, "SELECT first_name, last_name, profile_pic FROM users WHERE 
					                                   username='$added_by'");
					$user_row = mysqli_fetch_array($user_details_query);
					$first_name = $user_row['first_name'];
					$last_name = $user_row['last_name'];
					$profile_pic = $user_row['profile_pic'];

					?>
					<script>
						//when user click show specific comment based on post specifc id
						function toggle<?php echo $id; ?>() { //hide and show element
							var target = $(event.target);
							if (!target.is("a")) {
								var element = document.getElementById("toggleComment<?php echo $id; ?>");
								if (element.style.display == "block") {
									element.style.display = "none";
								} else {
									element.style.display = "block";
								}
							}
						}

						var delayInMilliseconds = 500; // half a second
						function backColor<?php echo $id; ?>() {
							setTimeout(function() { 
								for(var i = 0; i < 10000; i++) {
									var iframe = document.getElementsByTagName('iframe')[i];
									iframe.style.background = 'white';
									iframe.contentWindow.document.body.style.backgroundColor = 'white';
								}
							}, delayInMilliseconds);
						}

					</script>
					
					<?php
					//How comments there are
					$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id='$id'");
					$comments_check_num = mysqli_num_rows($comments_check);
			
					//Timeframe
					$date_time_now = date("Y-m-d H:i:s");
					$start_date = new DateTime($date_time); //Time of post
					$end_date = new DateTime($date_time_now); //Current time
					$interval = $start_date->diff($end_date); //Difference between dates 
					if($interval->y >= 1) {
						if($interval->y == 1)
							$time_message = $interval->y . " year ago"; //1 year ago
						else 
							$time_message = $interval->y . " years ago"; //1+ year ago
					}
					else if ($interval-> m >= 1) {
						if($interval->d == 0) {
							$days = " ago";
						}
						else if($interval->d == 1) {
							$days = $interval->d . " day ago";
						}
						else {
							$days = $interval->d . " days ago";
						}


						if($interval->m == 1) {
							$time_message = $interval->m . " month ". $days;
						}
						else {
							$time_message = $interval->m . " months ". $days;
						}

					}
					else if($interval->d >= 1) {
						if($interval->d == 1) {
							$time_message = "Yesterday";
						}
						else {
							$time_message = $interval->d . " days ago";
						}
					}
					else if($interval->h >= 1) {
						if($interval->h == 1) {
							$time_message = $interval->h . " hour ago";
						}
						else {
							$time_message = $interval->h . " hours ago";
						}
					}
					else if($interval->i >= 1) {
						if($interval->i == 1) {
							$time_message = $interval->i . " minute ago";
						}
						else {
							$time_message = $interval->i . " minutes ago";
						}
					}
					else {
						if($interval->s < 30) {
							$time_message = "Just now";
						}
						else {
							$time_message = $interval->s . " seconds ago";
						}
					}

					$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
								<div class='post_profile_pic'>
									<img src='$profile_pic' width='50'>
								</div>
								<div class='posted_by' style='color:#ACACAC;'>
									<a href='$added_by'> $first_name $last_name </a> $user_to &nbsp;&nbsp;&nbsp;&nbsp;$time_message
									$delete_button
								</div>
								<div id='post_body'>
									$body
									<br><br><br>
								</div>
								<div class='newsfeedPostOptions'>
									Comments($comments_check_num)&nbsp;&nbsp;&nbsp;
									<iframe src='like.php?post_id=$id' scrolling='no'></iframe>
								</div>
							</div>
							<div class='post_comment' id='toggleComment$id' style='display:none;'>
								<iframe src='comment_frame.php?post_id=$id' id='comment_iframe' class='iframe' 
								onLoad='javascript:backColor$id();' frameborder='0'></iframe>
							</div>
							<hr>";				
					?>
					<script>
						$(document).ready(function() {
							$('#post<?php echo $id; ?>').on('click', function() {
								//send the result based on the click
								bootbox.confirm("Are you sure you want to delete this post?", function(result) {
									/*It send the data(result) to delete_post.php */
									$.post("includes/form_handlers/delete_post.php?post_id=<?php echo $id; ?>", {result:result});
									if(result) //if result is true
										location.reload();
								});
							});
						});
					</script>
					<?php
					}//end of if statement for isFriend()
					else {
						echo "<p>You cannot see this post because you are not his friends with this user.</p>";
						return;
					}
		} // end of if statement
		else {
			echo "<p>No post found. If you clicked a link, it may be broken.</p>";
			return;
		}
		echo $str;
	}
}

?>