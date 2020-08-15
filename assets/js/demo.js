$(document).ready(function () {//when document loaded


    /*$("#search_text_input").focus(function() { 
        //if the window unit has a width of 800px or larger then make the input field wider
        if(window.matchMedia( "(min-width: 13vw)" ).matches) {
            //target current object - id text input field, 500 refer to the speed
            $(this).animate({width: '20vw'}, 900);
        }
    });*/

    $('.button_holder').on('click', function() {
        document.search_form.submit(); //once form submit, it is sent to search.php result page 
    });

    //button for profile post (This submit the form for the post)
    $('#submit_profile_post').click(function() { 
        $.ajax({ //used to perform an AJAX (asynchronous HTTP) request
            type: "POST", //type of request
            url: "includes/handlers/ajax_submit_profile_post.php", //send the request to
            /*serialization is the process of translating data structures or object state into a format 
            that can be stored or transmitted and reconstructed later.
            serialize() method creates a URL encoded text string by serializing form values.
            serialized values can be used in the URL query string when making an AJAX request*/
            data: $('form.profile_post').serialize(), //data to be sent to the server
            success: function (msg) { //function to be run when the request succeeds. msg which return back
                $("#post_form").modal('hide');
                location.reload(); //reload the page
            },
            error: function() {
                alert("Failure");
            }
        }); //end of $.ajax()
    }); //end of click()
}); //end of ready()

$(document).click(function(e) {
    //e.target is whot you clicked on, .class is the class of that target. if clicked on div and get that div
    if(e.target.class != "search_results" && e.target.id != "search_text_input") {
        $(".search_results").html("");
        $(".search_results_footer").html("");
        $(".search_results_footer_empty").toggleClass("search_results_footer_empty");
        $(".search_results_footer_empty").toggleClass("search_results_footer");
    }

    if(e.target.class != "dropdown_data_window" ) {
       $(".dropdown_data_window").html("");
       $(".dropdown_data_window").css({"padding" : "0px", "height" : "0px"});
    }


});

function getUser(value, user) {
    /*$.post() method loads data from the server using a HTTP POST request. post(URL, data, function(data), dataType )
      Send an HTTP POST request to ajax_friend_search.php and get the result back. {query & userLoggedIn} -Specifies 
      data to send to the server along with the request. function(data) - contains the resulting data from the request*/
    $.post("includes/handlers/ajax_friend_search.php", {query:value, userLoggedIn:user}, function(data) {
        //when it return the data, going to set the value of div with the content of what data return
        $(".results").html(data);
    }); 
}

function getDropdownData(user, type) { //type of data to be loaded
    //if the height is zero(dropdown not showing) , load the data
    if ($(".dropdown_data_window").css("height") == "0px") { 
        var pageName;
        if(type == 'notification') { //if the type is nofication then...
            pageName = "ajax_load_notification.php"; 
            $("span").remove("#unread_notification");
        }
        else if (type == 'message') { //if the type is message then...
            pageName = "ajax_load_message.php"; 
            $("span").remove("#unread_message");
        }

        var ajaxReq = $.ajax({ //sending ajax request
            url: "includes/handlers/" + pageName,
            type: "POST",
            data: "page=1&userLoggedIn=" + user,
            cache: false,

            success: function(response) {
                $(".dropdown_data_window").html(response);
                $(".dropdown_data_window").css({"padding" : "0px", "height": "210px", "border" : "1px solid #DADADA"});
                $("#dropdown_data_type").val(type);
            }
        });
    }
    else { //if the height is not zero (dropdown showing) 
        $(".dropdown_data_window").html("");
        $(".dropdown_data_window").css({"padding" : "0px", "height": "0px", "border" : "none"});
    }
 }

 function getLiveSearchUsers(value, user) {
     //Ajax call and return data and append it to the div, for query and userLoggedIn the value is given from the parameters
     $.post("includes/handlers/ajax_search.php", {query:value, userLoggedIn:user}, function(data) {//data returned from ajax_search
        if($(".search_results_footer_empty")[0]) { //access that class
            //toggles between adding and removing one or more class names from the selected elements
            $(".search_results_footer_empty").toggleClass("search_results_footer");
            $(".search_results_footer_empty").toggleClass("search_results_footer_empty");
        }
        $('.search_results').html(data); //append data to html content
        //a little button link below that will show all results
        $('.search_results_footer').html("<a href='search.php?q=" + value + "'>See All Results</a>")
        if(data == "") {
            $(".search_results_footer").html("");
            $(".search_results_footer_empty").toggleClass("search_results_footer_empty");
            $(".search_results_footer_empty").toggleClass("search_results_footer");
        }
     });
 }

 

 