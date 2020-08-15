<?php
class Message {
    private $user_obj;
    private $con;
    
    //This function create object of the User class
    public function __construct($con, $user){
        //The $this keyword refers to the current object, and is only available inside methods
        $this->con = $con; //contain database connection information
        $this->user_obj = new User($con, $user);
    }
    
    //Retrieving the most recently interacted with user
    public function getMostRecentUser() {
        $userLoggedIn = $this->user_obj->getUsername();
        //descending order - in reverse order for most recent one and limit to one result
        $query = mysqli_query($this->con, "SELECT user_to, user_from FROM messages WHERE user_to='$userLoggedIn' 
                              OR user_from='$userLoggedIn' ORDER BY id DESC LIMIT 1");
        if (mysqli_num_rows($query) == 0)  //if there is no recent user 
             return false;
        
        $row = mysqli_fetch_array($query);
        $user_to = $row['user_to'];
        $user_from = $row['user_from'];
        //simply return which one is not the loggedIn user
        if ($user_to != $userLoggedIn) //if user_to is not userLoggedIn
             return $user_to;
        else //else return user_from
             return $user_from;
    }

    public function sendMessage($user_to, $body, $date) {
        if ($body != "") {
            $userLoggedIn = $this->user_obj->getUsername();
            $query = mysqli_query($this->con, "INSERT INTO messages VALUES(NULL, '$user_to', '$userLoggedIn', '$body', 
                                              '$date', 'no', 'no', 'no')");                                            
        }
    }

    //Retrieve messages from other user
    public function getMessages($otherUser) { 
        $userLoggedIn = $this->user_obj->getUsername();
        $data = ""; //return big long string
        //Will set all opened values into yes Where messaging between these two users. if loaded messages open them all
        $query = mysqli_query($this->con, "UPDATE messages SET opened='yes' WHERE user_to='$userLoggedIn' AND user_from='$otherUser'");
        //Query to retrieve messeages - so regardless of who sent messages as long the message is between these two users 
        $get_messages_query = mysqli_query($this->con, "SELECT * FROM messages WHERE (user_to='$userLoggedIn' AND 
                              user_from='$otherUser') OR (user_from='$userLoggedIn' AND user_to='$otherUser') ");
        while($row = mysqli_fetch_array($get_messages_query)) {
            $user_to = $row['user_to'];
            $user_from = $row['user_from'];
            $body = $row['body'];

            //Tenary operator - change the color of div return depending on whether it was from userLoggedIn or to_userloggedIn
            $div_top = ($user_to == $userLoggedIn) ? "<div class='message' id='green' >" : "<div class='message' id='blue'>";
            /*$data going to add on data(first time it will be black as above) and going over the loop will contain the messages*/
            $data = $data . $div_top . $body . "</div><br><br>";
        }
        return $data;
    }

    //Get the latest message from each conversation
    public function getLatestMessage($userLoggedIn, $user2) {
        $details_array = array(); //empty array
        //Get one lastest message from descending
        $query = mysqli_query($this->con, "SELECT body, user_to, date FROM messages WHERE (user_to='$userLoggedIn' AND user_from='$user2') 
                                           OR (user_to='$user2' AND user_from='$userLoggedIn') ORDER BY id DESC LIMIT 1");
        $row = mysqli_fetch_array($query);
        //if user_to is userLoggedIn then execute first expression else false then execute second expression
        $sent_by = ($row['user_to'] == $userLoggedIn) ? "They said: " : "You Said: ";

        //Timeframe
        $date_time_now = date("Y-m-d H:i:s");
        $start_date = new DateTime($row['date']); //Time of post
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
        
        array_push($details_array, $sent_by);
        array_push($details_array, $row['body']);
        array_push($details_array, $time_message);

        return $details_array;
    }

    //Getting conversation list
    public function getConvos() {
        $userLoggedIn = $this->user_obj->getUsername(); //get the username of user who logged in
        $return_string = ""; //empty string
        //add username that this person is having conversation with in this array
        $convos = array(); //empty array
        //Select user_to and user_from based on if either the loggedIn user is user_to or user_from
        $query = mysqli_query($this->con, "SELECT user_to, user_from FROM messages WHERE user_to='$userLoggedIn' OR
                              user_from='$userLoggedIn' ORDER BY id DESC");
        while($row = mysqli_fetch_array($query)) {
            //check if user_to is not userLoggedIn, if true execute expreesion 1 else excute expression 2
            $user_to_push = ($row['user_to'] != $userLoggedIn) ? $row['user_to'] : $row['user_from'];
            //check that username is not already in the array
            if(!in_array($user_to_push, $convos)) { //return true if the user is not already in there
                array_push($convos, $user_to_push); //will add all username in the array
            }
        }
        //now we have all the conversation the user had. Each time this iterates, username is going to be 
        //reference to the item of $convos
        foreach($convos as $username) {
            $user_found_obj = new User($this->con, $username);
            $latest_message_details = $this->getLatestMessage($userLoggedIn, $username);
            //position 1 to access the body. if the length of body is greater then 12 chars
            $dots = (strlen($latest_message_details[1]) >= 20) ? "..." : "";
            //split the body string by amount of chars you defined. Make sure body chars are at max of 12 chars
            $split = str_split($latest_message_details[1], 20);
            $split = $split[0] . $dots;

            //Conversation list
            $return_string .= "<a href='messages.php?u=$username'> <div class='user_found_messages'>
                                <img src='" . $user_found_obj->getProfilePic() . "' style='border-radius: 5px margin-right: 5px;'> 
                                " . $user_found_obj->getFirstAndLastName() . " 
                                <span class='timestamp_smaller' id='grey'>" . $latest_message_details[2] . "</span> 
                                <p id='grey' style='margin: 0;'>" . $latest_message_details[0]. $split ."</p> 
                                </div>
                                </a>";
        }
        return $return_string;
    }
    
    //Retreving the data for our Dropdown window
    public function getConvosDropdown($data, $limit) {
        $page = $data['page'];
        $userLoggedIn = $this->user_obj->getUsername(); //get the username of user who logged in
        $return_string = ""; //empty string
        //add username that this person is having conversation with in this array
        $convos = array(); //empty array

        if($page == 1) //if page is one (first page we are loading) 
            $start = 0; //start from very first post
        else //if it is not the first page, then...
            $start = ($page - 1) * $limit ; //it need to know where to start loading from

        //when user open dropdown and seen mwssages then set viewed = yses
        $set_viewed_query = mysqli_query($this->con, "UPDATE messages SET viewed='yes' WHERE user_to='$userLoggedIn'");

        //Select user_to and user_from based on if either the loggedIn user is user_to or user_from
        $query = mysqli_query($this->con, "SELECT user_to, user_from FROM messages WHERE user_to='$userLoggedIn' OR
                              user_from='$userLoggedIn' ORDER BY id DESC");
        while($row = mysqli_fetch_array($query)) {
            //check if user_to is not userLoggedIn, if true execute expreesion 1 else excute expression 2
            $user_to_push = ($row['user_to'] != $userLoggedIn) ? $row['user_to'] : $row['user_from'];
            //check that username is not already in the array
            if(!in_array($user_to_push, $convos)) { //return true if the user is not already in there
                array_push($convos, $user_to_push); //will add all username in the array
            }
        }
  
        $num_iteration = 0; //number of messages seen and checked (not posted)
        $count = 1; //number of messages loaded

        //now we have all the conversation the user had. Each time this iterates, username is going to be 
        //reference to the item of $convos
        foreach($convos as $username) { 
            //if it hasn't reach it start point yet
            if ($num_iteration++ < $start ) //if num of iteration is less then start
                continue; //continue the loop
            //if reach the limit(how many messages to load)
            if ($count > $limit) //if num of messages loaded is greater then limit(7), then...
                break; //exit the loop
            else
                $count++;

            //Query to check to if it has been opened or not
            $is_unread_query = mysqli_query($this->con, "SELECT opened FROM messages WHERE user_to='$userLoggedIn' AND 
                                            user_from='$userLoggedIn' ORDER BY id DESC");
            $row = mysqli_fetch_array($is_unread_query);
            //if it hasn't been opened change back-color else do noting
            $style = (isset($row['opened']) && $row['opened'] == 'no') ? "background-color: #DDEDFF" : "";

            $user_found_obj = new User($this->con, $username);
            $latest_message_details = $this->getLatestMessage($userLoggedIn, $username);
            //position 1 to access the body. if the length of body is greater then 12 chars
            $dots = (strlen($latest_message_details[1]) >= 20) ? "..." : "";
            //split the body string by amount of chars you defined. Make sure body chars are at max of 12 chars
            $split = str_split($latest_message_details[1], 20);
            $split = $split[0] . $dots;

            //lastest Conversation list
            $return_string .= "<a href='messages.php?u=$username'> 
                                    <div class='user_found_messages' style='" . $style . "'>
                                        <img src='" . $user_found_obj->getProfilePic() . "' style='border-radius: 5px margin-right: 5px;'> 
                                        " . $user_found_obj->getFirstAndLastName() . " 
                                        <span class='timestamp_smaller' id='grey'>" . $latest_message_details[2] . "</span> 
                                        <p id='grey' style='margin: 0;'>" . $latest_message_details[0]. $split ."</p> 
                                    </div>
                                </a>";
        } //end of foreach loop 

        //if messages were loaded. it will tell us to load more messages or not
        if($count > $limit) //another page to load
            $return_string .= "<input type='hidden' class='nextPageDropdownData' value='" . ($page + 1) . "'><input type='hidden' class='noMoreDropdownData' value='false'>";
        else 
            $return_string .= "<input type='hidden' class='noMoreDropdownData' value='true'><p style='text-align: center;'>No more messages to load!</p>";

        return $return_string;
    }
  
    //get number of unread messages
    public function getUnreadNumber() {
        $userLoggedIn = $this->user_obj->getUsername();
        $query = mysqli_query($this->con, "SELECT * FROM messages WHERE viewed='no' AND user_to='$userLoggedIn'");
        return mysqli_num_rows($query);
    }
 
} //end of class Message
?>