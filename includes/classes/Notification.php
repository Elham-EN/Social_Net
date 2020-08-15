<?php
    class Notification {
        private $user_obj;
        private $con;
        
        //This function create object of the User class
        public function __construct($con, $user){
            //The $this keyword refers to the current object, and is only available inside methods
            $this->con = $con; //contain database connection information
            $this->user_obj = new User($con, $user);
        }

        //get number of unread messages
        public function getUnreadNumber() {
            $userLoggedIn = $this->user_obj->getUsername();
            $query = mysqli_query($this->con, "SELECT * FROM notifications WHERE viewed='no' AND user_to='$userLoggedIn'");
            return mysqli_num_rows($query);
        } 
        //parameter: post_id - id of the post link to, 
        public function insertNotification($post_id, $user_to, $type) { //type of notification to insert
            $userLoggedIn = $this->user_obj->getUsername();
            $userLoggedInName = $this->user_obj->getFirstAndLastName();
            $date_time = date("Y-m-d H:i:s");

            //diffrerent messages for different scenarios
            switch($type) {
                case 'comment':
                    $message = $userLoggedInName . " commented on your post";
                    break;
                case 'like':
                    $message = $userLoggedInName . " like your post";
                    break;
                case 'profile_post':
                    $message = $userLoggedInName . " posted on your profile";
                    break;
                case 'comment_non_owner':
                    $message = $userLoggedInName . " commented on a post you commented on";
                    break;
                case 'profile_comment':
                    $message = $userLoggedInName . " commented on your profile post";
                    break;
            } //end of switch statement

            //link var and put it into database. page name of the post we refer to
            $link = "post.php?id=" . $post_id;
            //UserLoggedIn - the user whose notification is from and left notification for $user_to
            $insert_query = mysqli_query($this->con, "INSERT INTO notifications VALUES(NULL, '$user_to', '$userLoggedIn', 
                                          '$message', '$link', '$date_time', 'no', 'no' )");
        }
        
        public function getNotification($data, $limit) { 
            $page = $data['page'];
            $userLoggedIn = $this->user_obj->getUsername(); //get the username of user who logged in
            $return_string = ""; //empty string
           
            if($page == 1) //if page is one (first page we are loading) 
                $start = 0; //start from very first post
            else //if it is not the first page, then...
                $start = ($page - 1) * $limit ; //it need to know where to start loading from
    
            //when user open dropdown and seen notification then set viewed = yses
            $set_viewed_query = mysqli_query($this->con, "UPDATE notifications SET viewed='yes' WHERE user_to='$userLoggedIn'");
    
            //Select user_to  based on if  the loggedIn user is user_to  order by descending order (getting lastest notification)
            $query = mysqli_query($this->con, "SELECT * FROM notifications WHERE user_to='$userLoggedIn' ORDER BY id DESC");
           
            if(mysqli_num_rows($query) == 0) {
                echo "You have no notification";
                return; //leave the function!
            }

            $num_iteration = 0; //number of messages seen and checked (not posted)
            $count = 1; //number of messages loaded
    
            //now we have all the conversation the user had. Each time this iterates, username is going to be 
            //reference to the item of $convos.
            while($row = mysqli_fetch_array($query)) { 
                //if it hasn't reach it start point yet
                if ($num_iteration++ < $start ) //if num of iteration is less then start
                    continue; //continue the loop
                //if reach the limit(how many messages to load)
                if ($count > $limit) //if num of messages loaded is greater then limit(7), then...
                    break; //exit the loop
                else
                    $count++;

                $user_from = $row['user_from']; //notification from (notification sent to $user_to)
                $query_data_query = mysqli_query($this->con, "SELECT * FROM users WHERE username='$user_from'");
                $user_data = mysqli_fetch_array($query_data_query); //contain user data who sent notification to

                //Timefrom for when notification was sent
				$date_time_now = date("Y-m-d H:i:s");
				$start_date = new DateTime($row['datetime']); //Time of notification
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
				}//end of Timeframe
    
                $opened = $row['opened'];
                //if it hasn't been opened change back-color else do noting
                $style = (isset($row['opened']) && $row['opened'] == 'no') ? "background-color: #DDEDFF" : "";
    
                //lastest notification list 
                $return_string .= "<a href='" . $row['link'] . "'> 
                                       <div class='notification resultDisplayNotification' style='" . $style . "'>
                                           <div class='notificationProfilePic'>
                                              <img src='" . $user_data['profile_pic'] . "'>
                                           </div>
                                           <p class='timestamp_smaller' id='grey'>" . $time_message . "</p>" . $row['message'] . " 
                                       </div>
                                   </a>";
            } //end of while loop 
    
            //if messages were loaded. it will tell us to load more messages or not
            if($count > $limit) //another page to load
                $return_string .= "<input type='hidden' class='nextPageDropdownData' value='" . ($page + 1) . "'><input type='hidden' class='noMoreDropdownData' value='false'>";
            else 
                $return_string .= "<input type='hidden' class='noMoreDropdownData' value='true'><p style='text-align: center;'>No more notification to load!</p>";
    
            return $return_string;
        }

    }//end of class Notification
?>