<?php
require_once __DIR__ . "/../src/dbconnection.php";
require_once __DIR__ . "/../src/flash_message.php";

function create_event(PDO $dbh, $start, $end, $series) {
    if (isset($_SESSION["userid"])) {
        $userid = $_SESSION["userid"];
    }
    else {
        $userid = 1;
    }

    // 0 waiting, 1 approved, 2 rejected
    // user with auto approval rights/moderator -> 1
    $approval_state = 0;
    if ($approval_state == 1) {
        $approved_by = $userid;
    }
    else {
        $approved_by = null;
    }

    $stmt = $dbh->prepare("
        INSERT INTO event (
        name, description, datetime_start, datetime_end, location,
        created_by, approval_state, approved_by, event_series) VALUES (
        :name, :description, :start, :end, :location, :created_by,
        :approval_state, :approved_by, :series);");
    try {
        $stmt->execute(array(":name" => $_POST["name"], ":description" => $_POST["description"],
        ":start" => $start, ":end" => $end, ":location" => $_POST["location"],
        ":created_by" => $userid, ":approval_state" => $approval_state,
        ":approved_by" => $approved_by, ":series" => $series));
        echo "Successfully submitted event!<br/>";
    } catch (PDOException $e) {
        // printing detailed error to user might be bad, change this later?
        //die("Error!: " . $e->getMessage() . "<br/>");
        die("Sorry, something went wrong! Please try again.");
    }
}

function valid_date(string $date, string $format)
{
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
}

// probably rename and maybe split function
function validate($dbh) {
    $required_string_data = array("name", "location", "datetime_start", "datetime_end");
    foreach ($required_string_data as $str) {
        if (empty($_POST[$str])) {
            error_and_redirect("Please enter a value for the event name, location, start and end!");
        }          
    }

    // seconds and milliseconds are expected to be 0
    // e.g. 2022-10-25T14:16:00.000Z
    $datetime_format = "Y-m-d\TH:i:00.000\Z"; 
    $datetime_start = $_POST["datetime_start"];
    $datetime_end = $_POST["datetime_end"];
    if (!valid_date($datetime_start, $datetime_format)) {
        error_and_redirect("Please enter a valid start date and time!");
    }
    if (!valid_date($datetime_end, $datetime_format)) {
        error_and_redirect("Please enter a valid end date and time!");
    }
    if ($datetime_end < $datetime_start) {
        $datetime_end = $datetime_start;
    }

    if (empty($_POST["series"])) {
        $series = null;
    }
    //maybe also check if int is a valid seriesid in the db
    elseif (is_numeric($_POST["series"])) {
        $series = intval($_POST["series"]);
    }
    else {
        error_and_redirect("The event series value is invalid! This shouldn't happen.");
    }

    create_event($dbh, $datetime_start, $datetime_end, $series);
}

define("FLASH_MESSAGE_NAME", "create_event_error_message");
define("ERROR_REDIRECT_LOCATION", "/new_event.php");
session_start();
validate($dbh);

?>