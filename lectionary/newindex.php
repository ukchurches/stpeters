<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');

// based on:
// https://www.churchofengland.org/prayer-and-worship/worship-texts-and-resources/common-worship/churchs-year/lectionary
//// https://www.churchofengland.org/prayer-and-worship/worship-texts-and-resources/common-worship/churchs-year/calendar
// https://www.churchofengland.org/prayer-and-worship/worship-texts-and-resources/common-worship/churchs-year/rules

function appendtodb($timestamp,$appendtext){
global $servername, $username, $password, $dbname;	
$connection = new mysqli($servername, $username, $password, $dbname);
$thisyeardate=date("Y-m-d", $timestamp);
$query="UPDATE `thisyeardates` SET `full_name` = CONCAT( `datename`,' - ".$appendtext."') WHERE `thisyeardates`.`thisyeardate` = '".$thisyeardate."'";
$result = $connection->query($query);	
}

function addtodb($datename,$thisyeardate, $calendar_id, $priority="5"){
global $servername, $username, $password, $dbname, $lityear;
$connection1 = new mysqli($servername, $username, $password, $dbname);
$query1="SELECT * FROM `anglican_calendar` WHERE `calendar_id` = ".$calendar_id ;
$result1 = $connection1->query($query1);
$row_result1 = $result1->fetch_assoc();
$fullname=$row_result1['dayname'];
$short_collect=$row_result1['collect2'];
$long_collect=$row_result1['collect'];
$template_name=$row_result1['template_name'];
$post_communion=$row_result1['post_communion'];
$exception_note=$row_result1['exception_note'];
$type=$row_result1['type'];
//$sun_no  https://stackoverflow.com/questions/32615861/get-week-number-in-month-from-date-in-php
$sun_no=weekOfMonth($thisyeardate);
$connection2 = new mysqli($servername, $username, $password, $dbname);
$query=
"
INSERT INTO `thisyeardates` (
`thisyeardate_id`, 
`datename`, 
`full_name`, 
`type`, 
`template_name`,
`thisyeardate`, 
`calendar_id`, 
`short_collect`, 
`long_collect`, 
`post_communion`, 
`notes`, 
`lityear`, 
`sun_no`, 
`item_priority`
)  VALUES  (
NULL, 
 '".$datename."', 
 '".$fullname."', 
 '".$type."', 
 '".$template_name."', 
 '".$thisyeardate."', 
 '".$calendar_id."', 
 '".$short_collect."', 
 '".$long_collect."', 
 '".$post_communion."', 
 '".$exception_note."', 
 '".$lityear."', 
  '".$sun_no."', 
 '".$priority."' );
";

$result = $connection2->query($query);	   
}

function isthissunday($datex,$yearx){
	$datestring=$yearx.$datex;
	$issunday="no";
	$datestamp=strtotime($datestring);
	if(date("D",$datestamp)=="Sun")$issunday="yes";
	return $issunday;
}	

function weekOfMonth($date) {
    // estract date parts
    list($y, $m, $d) = explode('-', date('Y-m-d', strtotime($date)));
    // current week, min 1
    $w = 1;
    // for each day since the start of the month
    for ($i = 1; $i < $d; ++$i) {
        // if that day was a sunday and is not the first day of month
        if ($i > 1 && date('w', strtotime("$y-$m-$i")) == 0) {
            // increment current week
            ++$w;
        }
    }
      // now return
		if (date('w', strtotime("$y-$m-$d")) == 0) {
    return $w;
	}else{
		return 0;
	}
}
	
function findsunday($datex,$yearx){
//find the the sunday on or first sunday after this date
$returndatestamp="";
	$finddate=$yearx.$datex;
	//echo $finddate;
	$findsundaystamp=strtotime($finddate);
	if(date("D",$findsundaystamp)=="Sun")$returndatestamp=$findsundaystamp;
	$findsundaystamp=strtotime('+1 day', $findsundaystamp);
	if(date("D",$findsundaystamp)=="Sun")$returndatestamp=$findsundaystamp;
	$findsundaystamp=strtotime('+1 day', $findsundaystamp);
	if(date("D",$findsundaystamp)=="Sun")$returndatestamp=$findsundaystamp;
	$findsundaystamp=strtotime('+1 day', $findsundaystamp);
	if(date("D",$findsundaystamp)=="Sun")$returndatestamp=$findsundaystamp;
	$findsundaystamp=strtotime('+1 day', $findsundaystamp);
	if(date("D",$findsundaystamp)=="Sun")$returndatestamp=$findsundaystamp;
	$findsundaystamp=strtotime('+1 day', $findsundaystamp);
	if(date("D",$findsundaystamp)=="Sun")$returndatestamp=$findsundaystamp;
	$findsundaystamp=strtotime('+1 day', $findsundaystamp);
	if(date("D",$findsundaystamp)=="Sun")$returndatestamp=$findsundaystamp;
	return $returndatestamp;
}

function createthisyeardates($mydate){
global $servername, $username, $password, $lityear, $dbname;	
// set timezone to get easter_date correct
date_default_timezone_set('Europe/London');
$mydatestamp=strtotime($mydate);
$monthday=date("-m-d",$mydatestamp);
$year=date("Y",$mydatestamp);
//first move to next sunday If its not sunday today)
$searchtimestamp=findsunday($monthday,$year);
// now find the Advent Sunday that starts this liturgical year
$adventstamp=findsunday("-11-27",$year);
if($adventstamp>$searchtimestamp){
	$year=$year-1;
	$adventstamp=findsunday("-11-27",$year);
}



// only do the rest if we havent got the correct year in thisyeardates table 

$connection = new mysqli($servername, $username, $password, $dbname);
$queryx="SELECT *  FROM `settings`";
$resultx = $connection->query($queryx);
$row_result = $resultx->fetch_assoc();
//if ($row_result['advent_year']!=$year){
	
	
	
/// force recalculate	
	
	
	
if (3===3){
// Find liturgical year from advent onwards A B or C

$lityear="Z";
$remainder=($year %3);
if($remainder==0)$lityear="A";
if($remainder==1)$lityear="B";
if($remainder==2)$lityear="C";

//Update presenter settings




$connection = new mysqli($servername, $username, $password, $dbname);
$qry="UPDATE `settings` SET `advent_year` ='".$year."',`lectionary_year` = '".$lityear."'" ;
$result = $connection->query($qry);





// now for season timestamps:

$mainyear=$year+1;
$epiphanystamp=strtotime($mainyear."-01-06"); //christmas season up to $epiphanystamp
$candlemassstamp=strtotime($mainyear."-02-02"); //epiphany season up to $candlemassstamp 
$easterstamp=easter_date($mainyear); //lent up to easter
$palmsundaystamp=strtotime('-1 week', $easterstamp);
$secondsundayofeaster=strtotime('+1 week', $easterstamp);
$beforeadventstamp=findsunday("-10-30",$mainyear);  //4th sunday before advent or all saints day
$lastrinitytstamp=strtotime('-1 week', $beforeadventstamp);






$searchtimestamp=$adventstamp;
addtodb("Advent Sunday",date("Y-m-d",$searchtimestamp ),10);

//Deal with Andrew as a Festival may not be celebrated on Sundays in Advent, Lent or Eastertide. Festivals coinciding with a Principal Feast or Principal Holy Day are transferred to the first available day.

if (isthissunday("-11-30",$year)=="no"){
addtodb("Andrew",$year."-11-30",102);
}else{
addtodb("Andrew (from Nov 30)",$year."-12-01",102);	
}


// sundays after advent 

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Advent 2",date("Y-m-d", $searchtimestamp),11);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Advent 3",date("Y-m-d", $searchtimestamp),12);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Advent 4",date("Y-m-d", $searchtimestamp),13);

//other days up to end of the year 

addtodb("Christmas Eve",$year."-12-24",101);
addtodb("Christmas Day",$year."-12-25",14);
addtodb("Stephen",$year."-12-26",15);
addtodb("John",$year."-12-27",16);
addtodb("The Holy Innocents",$year."-12-28",17);

// christmas 1 sunday not there if christmas is a sunday -- needs fix	
$searchtimestamp=findsunday("-12-26",$year);
addtodb("First Sunday of Christmas",date("Y-m-d", $searchtimestamp),18);

// Second sunday of Christmas only if before 6th Jan see 2023 fix needed
$secondsundaychristmas=strtotime('+1 week', $searchtimestamp);
if (date("d", $searchtimestamp)<"6"){
addtodb("Second Sunday of Christmas",date("Y-m-d", $secondsundaychristmas),20);
}

$year= $year+1; // we have moved up a year

addtodb("Naming and Circumcision of Jesus",$year."-01-01",19);

addtodb("Epiphany",$year."-01-06",22);

//  If the Epiphany (6 January) falls on a weekday it may, for pastoral reasons, be celebrated on the Sunday falling between 2 and 8 January inclusive. 

//The Baptism of Christ must be transferred if Epiphany is celebrated on Sunday 7 or 8 January but otherwise may not be transferred  

$transferbaptismchrist=0;
if (isthissunday("-01-06",$year)=="no"){	
	$searchtimestamp=findsunday("-01-02",$year);
	addtodb("Epiphany (from 6th Jan)",date("Y-m-d", $searchtimestamp),22);
	if (date("d", $searchtimestamp)=="07")$transferbaptismchrist=1;
	if (date("d", $searchtimestamp)=="08")$transferbaptismchrist=1;
	}

if (isthissunday("-01-06",$year)=="yes"){
	//6 January is a Sunday
	$searchtimestamp=findsunday("-01-07",$year);
	addtodb("Second Sunday of Epiphany - The Baptism of Christ",date("Y-m-d", $searchtimestamp),24);
	$searchtimestamp=strtotime('+1 week', $searchtimestamp);
	
	//There is only a third or fourth sunday in advent if before candlemass
	if (strtotime('+1 week', $searchtimestamp)<=$candlemassstamp){
	addtodb("Third Sunday of Epiphany",date("Y-m-d", $searchtimestamp),26);
	}
	if (strtotime('+1 week', $searchtimestamp)<=$candlemassstamp){
	$searchtimestamp=strtotime('+1 week', $searchtimestamp);
	addtodb("Fourth Sunday of Epiphany",date("Y-m-d", $searchtimestamp),107);
	}
}else{
	//6 January is not a Sunday
	$searchtimestamp=findsunday("-01-07",$year);
	
	if ($transferbaptismchrist==1){
	addtodb("First Sunday of Epiphany - The Baptism of Christ (if Epiphany was celebrated on 6th Jan)",date("Y-m-d", $searchtimestamp),23);
	$baptismtimestamp=strtotime('+1 day', $searchtimestamp);
	addtodb("The Baptism of Christ (if Epiphany was celebrated on 7th or 8th Jan)",date("Y-m-d", $baptismtimestamp),23);
	}else{	
	addtodb("First Sunday of Epiphany - The Baptism of Christ",date("Y-m-d", $searchtimestamp),23);
	}

	if (strtotime('+1 week', $searchtimestamp)<=$candlemassstamp){
	$searchtimestamp=strtotime('+1 week', $searchtimestamp);
	addtodb("Second Sunday of Epiphany",date("Y-m-d", $searchtimestamp),24);
	}
	if (strtotime('+1 week', $searchtimestamp)<=$candlemassstamp){
	$searchtimestamp=strtotime('+1 week', $searchtimestamp);
	addtodb("Third Sunday of Epiphany",date("Y-m-d", $searchtimestamp),26);
	}
	if (strtotime('+1 week', $searchtimestamp)<=$candlemassstamp){
	$searchtimestamp=strtotime('+1 week', $searchtimestamp);
	addtodb("Fourth Sunday of Epiphany",date("Y-m-d", $searchtimestamp),107);
	}	
}


//  The Presentation of Christ in the Temple (Candlemas) is celebrated either on 2 February or on the Sunday falling between 28 January and 3 February. 
addtodb("The Presentation of Christ in the Temple - Candlemas",$year."-02-02",27);
if (isthissunday("-02-02",$year)=="no"){
	$searchtimestamp=findsunday("-01-28",$year);	
	addtodb("The Presentation of Christ in the Temple - Candlemas (from 2nd Feb)",date("Y-m-d", $searchtimestamp),27); 
	}

//Ordinary Time1 before lent. This begins on the day following $candlemassstamp -the Presentation of Christ
//find Fifth Sunday before Lent

$searchtimestamp=$easterstamp;
$searchtimestamp=strtotime('-11 weeks', $searchtimestamp);
$checkdte=strtotime('-1 day', $searchtimestamp);
if ($checkdte>$candlemassstamp){
addtodb("Fifth Sunday before Lent",date("Y-m-d", $searchtimestamp),203);
}

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
if ($searchtimestamp>$candlemassstamp){
addtodb("Fourth Sunday before Lent",date("Y-m-d", $searchtimestamp),204);
}

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
if ($searchtimestamp>$candlemassstamp){
addtodb("Third Sunday before Lent",date("Y-m-d", $searchtimestamp),28);
}

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
if ($searchtimestamp>$candlemassstamp){
addtodb("Second Sunday before Lent",date("Y-m-d", $searchtimestamp),29);
}

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Sunday next before Lent",date("Y-m-d", $searchtimestamp),30);

$searchtimestamp=strtotime('+3 days', $searchtimestamp);
addtodb("Ash Wednesday",date("Y-m-d", $searchtimestamp),31);

//First Sunday of Lent
$searchtimestamp=findsunday(date("-m-d",$searchtimestamp), $year); 
addtodb("First Sunday of Lent",date("Y-m-d", $searchtimestamp),32);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Second Sunday of Lent",date("Y-m-d", $searchtimestamp),33);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Third Sunday of Lent",date("Y-m-d", $searchtimestamp),34);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Mothering Sunday",date("Y-m-d", $searchtimestamp),103);
addtodb("Fourth Sunday of Lent",date("Y-m-d", $searchtimestamp),36);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Fifth Sunday of Lent",date("Y-m-d", $searchtimestamp),38);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Palm Sunday",date("Y-m-d", $searchtimestamp),39);

$searchtimestamp=strtotime('+1 day', $searchtimestamp);
//addtodb("Monday of Holy Week",date("Y-m-d", $searchtimestamp),205);


$searchtimestamp=strtotime('+1 day', $searchtimestamp);
//addtodb("Tuesday of Holy Week",date("Y-m-d", $searchtimestamp),206);
$searchtimestamp=strtotime('+1 day', $searchtimestamp);
//addtodb("Wednesday of Holy Week",date("Y-m-d", $searchtimestamp),207);
$searchtimestamp=strtotime('+1 day', $searchtimestamp);
addtodb("Maundy Thursday",date("Y-m-d", $searchtimestamp),40);
$searchtimestamp=strtotime('+1 day', $searchtimestamp);
addtodb("Good Friday",date("Y-m-d", $searchtimestamp),41);
$searchtimestamp=strtotime('+1 day', $searchtimestamp);
addtodb("Easter Eve",date("Y-m-d", $searchtimestamp),201);
addtodb("Easter Vigil",date("Y-m-d", $searchtimestamp),202);

$searchtimestamp=strtotime('+1 day', $searchtimestamp);
addtodb("Easter Day",date("Y-m-d", $searchtimestamp),42);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Second Sunday of Easter",date("Y-m-d", $searchtimestamp),43);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Third Sunday of Easter",date("Y-m-d", $searchtimestamp),46);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Fourth Sunday of Easter",date("Y-m-d", $searchtimestamp),48);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Fifth Sunday of Easter",date("Y-m-d", $searchtimestamp),49);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Sixth Sunday of Easter",date("Y-m-d", $searchtimestamp),51);

$searchtimestamp=strtotime('+39 days', $easterstamp);
addtodb("Ascension Day",date("Y-m-d", $searchtimestamp),52);

$searchtimestamp=findsunday(date("-m-d", $searchtimestamp),$year);
addtodb("Seventh Sunday of Easter - Sunday after Ascension Day",date("Y-m-d", $searchtimestamp),53);

$searchtimestamp=strtotime('+7 weeks', $easterstamp);
addtodb("Pentecost - Whit Sunday",date("Y-m-d", $searchtimestamp),54);

//Ordinary Time

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Trinity Sunday",date("Y-m-d", $searchtimestamp),56);

$searchtimestamp=strtotime('+4 days', $searchtimestamp);
addtodb("Day of Thanksgiving for Holy Communion (Corpus Christi",date("Y-m-d", $searchtimestamp),57);
//  The Thursday after Trinity Sunday may be observed as the Day of Thanksgiving for the Holy Communion (sometimes known as Corpus Christi), and may be kept as a Festival. Where the Thursday following Trinity Sunday is observed as a Festival to commemorate the Institution of the Holy Communion and that day falls on a date which is also a Festival, the commemoration of the Institution of Holy Communion shall be observed on that Thursday and the other occurring Festival shall be transferred to the first available day.



$searchtimestamp=strtotime('+3 days', $searchtimestamp);
addtodb("First Sunday after Trinity",date("Y-m-d", $searchtimestamp),60);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Second Sunday after Trinity",date("Y-m-d", $searchtimestamp),61);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Third Sunday after Trinity",date("Y-m-d", $searchtimestamp),63);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Fourth Sunday after Trinity",date("Y-m-d", $searchtimestamp),67);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Fifth Sunday after Trinity",date("Y-m-d", $searchtimestamp),69);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Sixth Sunday after Trinity",date("Y-m-d", $searchtimestamp),70);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Seventh Sunday after Trinity",date("Y-m-d", $searchtimestamp),73);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Eigth Sunday after Trinity",date("Y-m-d", $searchtimestamp),74);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Ninth Sunday after Trinity",date("Y-m-d", $searchtimestamp),76);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Tenth Sunday after Trinity",date("Y-m-d", $searchtimestamp),78);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Eleventh Sunday after Trinity",date("Y-m-d", $searchtimestamp),79);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Twelfth Sunday after Trinity",date("Y-m-d", $searchtimestamp),81);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Thirteenth Sunday after Trinity",date("Y-m-d", $searchtimestamp),82);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Fourteenth Sunday after Trinity",date("Y-m-d", $searchtimestamp),83);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Fifteenth Sunday after Trinity",date("Y-m-d", $searchtimestamp),85);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Sixteenth Sunday after Trinity",date("Y-m-d", $searchtimestamp),87);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Seventeenth Sunday after Trinity",date("Y-m-d", $searchtimestamp),89);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Eighteenth Sunday after Trinity",date("Y-m-d", $searchtimestamp),91);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);


// *** need to find last sunday after trinity
if($searchtimestamp<$lastrinitytstamp)addtodb("Nineteenth Sunday after Trinity",date("Y-m-d", $searchtimestamp),92);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
if($searchtimestamp<$lastrinitytstamp)addtodb("Twentieth Sunday after Trinity",date("Y-m-d", $searchtimestamp),104);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
if($searchtimestamp<$lastrinitytstamp)addtodb("Twenty-first Sunday after Trinity",date("Y-m-d", $searchtimestamp),208);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
if($searchtimestamp<$lastrinitytstamp)addtodb("Twenty-second Sunday after Trinity",date("Y-m-d", $searchtimestamp),209);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
if($searchtimestamp<$lastrinitytstamp)addtodb("Twenty-third Sunday after Trinity",date("Y-m-d", $searchtimestamp),210);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
if($searchtimestamp<$lastrinitytstamp)addtodb("Twenty-fourth Sunday after Trinity",date("Y-m-d", $searchtimestamp),211);
$searchtimestamp=strtotime('+1 week', $searchtimestamp);
if($searchtimestamp<$lastrinitytstamp)addtodb("Twenty-fifth Sunday after Trinity",date("Y-m-d", $searchtimestamp),212);

$searchtimestamp=$lastrinitytstamp;
addtodb("Last Sunday of Trinity - Bible Sunday",date("Y-m-d", $searchtimestamp),105);

$searchtimestamp=$beforeadventstamp;
addtodb("Fourth Sunday before Advent",date("Y-m-d", $searchtimestamp),106);

// ** last sunday of trinity is Bible Sunday append

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Third Sunday before Advent",date("Y-m-d", $searchtimestamp),98);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Second Sunday before Advent",date("Y-m-d", $searchtimestamp),99);

$searchtimestamp=strtotime('+1 week', $searchtimestamp);
addtodb("Sunday next before Advent - Christ the King",date("Y-m-d", $searchtimestamp),100);



// add fixed days from Jan 1st to next advent

addtodb("The Conversion of Paul",$year."-01-25",25);


//The Annunciation falling on a Sunday must be transferred

if (isthissunday("-03-25",$year)=="yes"){
addtodb("The Annunciation of Our Lord (from 25 March)",$year."-03-26",37);
$annunciationstamp=strtotime($year."-03-26");
}else{
addtodb("The Annunciation of Our Lord",$year."-03-25",37);
$annunciationstamp=0;
}

// When St Joseph’s Day falls between Palm Sunday and the Second Sunday of Easter inclusive, it is transferred to the Monday after the Second Sunday of Easter or, if the Annunciation has already been moved to that date, to the first available day thereafter.
// need fix to move st joseph if it falls on a sunday in lent

$josephstamp=strtotime($year."-03-19");
if($josephstamp >= $palmsundaystamp && $josephstamp <= $secondsundayofeaster){
	$josephstamp=$secondsundayofeaster;
	$josephstamp=strtotime('+1 day', $josephstamp);
	if ($josephstamp==$annunciationstamp)$josephstamp=strtotime('+1 day', $josephstamp);	
	addtodb("Joseph of Nazareth (from 19 Mar)",date("Y-m-d", $josephstamp),35);
}else{	
	addtodb("Joseph of Nazareth",$year."-03-19",35);
}


//When St George’s Day or St Mark’s Day falls between Palm Sunday and the Second Sunday of Easter inclusive, it is transferred to the Monday after the Second Sunday of Easter. If both fall in this period, St George’s Day is transferred to the Monday and St Mark’s Day to the Tuesday. When the Festivals of George and Mark both occur in the week following Easter and are transferred in accordance with these Rules in a place where the calendar of The Book of Common Prayer is followed, the Festival of Mark shall be observed on the second available day so that it will be observed on the same day as in places following alternative authorized Calendars, where George will have been transferred to the first available free day. St Joseph, St George or St Mark falling between Palm Sunday and the Second Sunday of Easter inclusive must be transferred

// ** so why does coomon prayer app transfer Mark from easter 4?


$georgestamp=strtotime($year."-04-23");
if($georgestamp >= $palmsundaystamp && $georgestamp <= $secondsundayofeaster){
	$georgestamp=$secondsundayofeaster;
	$georgestamp=strtotime('+1 day', $georgestamp);
	addtodb("George (from 23 Apr)",date("Y-m-d", $georgestamp),44);
}else{
	addtodb("George",$year."-04-23",44);
}

$markstamp=strtotime($year."-04-25");
if($markstamp >= $palmsundaystamp && $markstamp <= $secondsundayofeaster){
	$markstamp=$secondsundayofeaster;
	$markstamp=strtotime('+1 day', $markstamp);
	if ($markstamp==$georgestamp)$markstamp=strtotime('+1 day', $markstamp);
	addtodb("Mark (from 25 Apr)",date("Y-m-d", $markstamp),45);
}else{
if ($markstamp==$georgestamp)$markstamp=strtotime('+1 day', $markstamp);	
	addtodb("Mark",date("Y-m-d", $markstamp),45);	
}

addtodb("Philip and James",$year."-05-01",47);
addtodb("Matthias",$year."-05-14",50);



// **** The visit should be moved if it falls on pentecost sunday
addtodb("The Visit of the Blessed Virgin Mary to Elizabeth",$year."-05-31",55);
// ** Barnabas to me moved on if Corpus Christi is kept on same day
addtodb("Barnabas",$year."-06-11",58);


addtodb("The Birth of John the Baptist",$year."-06-24",62);
addtodb("Peter and Paul",$year."-06-29",64);
addtodb("Peter",$year."-06-29",65);
addtodb("Thomas",$year."-07-03",66);
addtodb("Mary Magdalene",$year."-07-22",71);
addtodb("James",$year."-07-25",72);
addtodb("The Transfiguration of Our Lord",$year."-08-06",75);

addtodb("The Blessed Virgin Mary",$year."-08-15",77);
//The Festival of the Blessed Virgin Mary (15 August) may, for pastoral reasons, be celebrated instead on 8 September.
// *** or moved to monday if this is a sunday
addtodb("The Blessed Virgin Mary (from 15th August)",$year."-09-08",77);

addtodb("Bartholomew",$year."-08-24",80);
addtodb("Holy Cross Day",$year."-09-14",84);

// ***Harvest Thanksgiving may be celebrated on any Sunday in Autumn - trinity, replacing the provision for the day provided it does not displace any principal feast or festival
$harvestthanksgivingstamp=findsunday("-09-01",$year);
addtodb("Harvest Thanksgiving",date("Y-m-d", $harvestthanksgivingstamp),213);





addtodb("Matthew",$year."-09-21",86);
addtodb("Michael and All Angels",$year."-09-29",88);
addtodb("Luke",$year."-10-18",93);
addtodb("Simon and Jude",$year."-10-28",96);


// All Saints’ Day is celebrated on either 1 November or the Sunday falling between 30 October and 5 November; if the latter there may be a secondary celebration on 1 November.

addtodb("All Saints’ Day",$year."-11-01",97);

if (isthissunday("-11-01",$year)=="no"){
addtodb("All Saints’ Day (if also celebrated on Sunday)",$year."-11-01",97);	
	
	
	
$allsaintsstamp=findsunday("-10-30",$year);
addtodb("All Saints’ Day (from 1 Nov)",date("Y-m-d", $allsaintsstamp),97);
}



addtodb("Andrew",$year."-11-30",102);

// *** dedication festival 4 or 25th oct or locally chosen date

// Append special day titles (no actual collect etc provided)

//Education Sunday is 2nd sun in sept
$educationsundaystamp=findsunday("-09-01",$year);
$educationsundaystamp=strtotime('+1 week', $educationsundaystamp);

$appendtext= "Education Sunday";
appendtodb($educationsundaystamp,$appendtext);


addtodb("Remembrance Day",$year."-11-11",214);
$remembrancesundaystamp=findsunday("-11-08",$year);

$appendtext= "Remembrance Sunday";
appendtodb($remembrancesundaystamp,$appendtext);
// **** chech what happens when 11th is a sunday

// The Week of Prayer for Christian Unity is traditionally observed from the 18th to the 25th January - the octave of St. Peter and St. Paul. ul. However, some areas observe it at Pentecost or some other time.

$appendtext= "Week of Prayer for Christian Unity (18-25 Jan)";
$wopday1=$year."-01-18";

$wopstamp=strtotime($wopday1);
if((date("D",$wopstamp)=="Sun"))appendtodb($wopstamp,$appendtext);
$wopstamp=strtotime('+1 day', $wopstamp);
if((date("D",$wopstamp)=="Sun"))appendtodb($wopstamp,$appendtext);
$wopstamp=strtotime('+1 day', $wopstamp);
if((date("D",$wopstamp)=="Sun"))appendtodb($wopstamp,$appendtext);
$wopstamp=strtotime('+1 day', $wopstamp);
if((date("D",$wopstamp)=="Sun"))appendtodb($wopstamp,$appendtext);
$wopstamp=strtotime('+1 day', $wopstamp);
if((date("D",$wopstamp)=="Sun"))appendtodb($wopstamp,$appendtext);
$wopstamp=strtotime('+1 day', $wopstamp);
if((date("D",$wopstamp)=="Sun"))appendtodb($wopstamp,$appendtext);
$wopstamp=strtotime('+1 day', $wopstamp);
if((date("D",$wopstamp)=="Sun"))appendtodb($wopstamp,$appendtext);
$wopstamp=strtotime('+1 day', $wopstamp);
if((date("D",$wopstamp)=="Sun"))appendtodb($wopstamp,$appendtext);

//Christian Aid week is 2nd week in May
$christianaidstamp=findsunday("-05-01",$year);
$christianaidstamp=strtotime('+1 week', $christianaidstamp);
$appendtext= "Start of Christian Aid Week";
appendtodb($christianaidstamp,$appendtext);

$appendtext= "End of Christian Aid Week";
$christianaidstamp=strtotime('next sunday', $christianaidstamp);
appendtodb($christianaidstamp,$appendtext);


//Construct seasons


$connection = new mysqli($servername, $username, $password, $dbname);
$query="SELECT *  FROM `thisyeardates` ORDER BY `thisyeardate`,`item_priority` ASC";
$result = $connection->query($query);	
$row_result = $result->fetch_assoc();
	$season="xxx";
do{

$thisyeardate_id=$row_result['thisyeardate_id'];
$connectionx = new mysqli($servername, $username, $password, $dbname);
if($row_result['full_name']=="The First Sunday of Advent")$season="Advent";
if($row_result['full_name']=="Christmas Day")$season="Christmas";
if($row_result['full_name']=="The Epiphany")$season="Epiphany";
if($row_result['full_name']=="The Fifth Sunday before Lent")$season="Ordinary Time";
if($row_result['full_name']=="The Fourth Sunday before Lent")$season="Ordinary Time";
if($row_result['full_name']=="The Third Sunday before Lent")$season="Ordinary Time";
if($row_result['full_name']=="The Second Sunday before Lent")$season="Ordinary Time";
if($row_result['full_name']=="The Sunday next before Lent")$season="Ordinary Time";
if($row_result['full_name']=="Ash Wednesday")$season="Lent";
if($row_result['full_name']=="Easter Day")$season="Easter";
if($row_result['full_name']=="Trinity Sunday")$season="Ordinary Time";


// now update the season so we can pick the right service template
$queryx="UPDATE `thisyeardates` SET `season` = '".$season."' WHERE `thisyeardates`.`thisyeardate_id` = ".$thisyeardate_id;
$resultx = $connectionx->query($queryx);

} while ($row_result = $result->fetch_assoc());


}


}



//Prepare database table


$connection = new mysqli($servername, $username, $password, $dbname);
$qry="TRUNCATE TABLE `thisyeardates`";
$result = $connection->query($qry);


$dd=date("Y-m-d");

createthisyeardates($dd);

$dd=date('Y-m-d', strtotime('+1 year'));

createthisyeardates($dd);

$dd=date('Y-m-d', strtotime('+2 year'));

createthisyeardates($dd);

$dd=date('Y-m-d', strtotime('+3 year'));

createthisyeardates($dd);

$dd=date('Y-m-d', strtotime('+4 year'));

createthisyeardates($dd);


$dd=date('Y-m-d', strtotime('+5 year'));

createthisyeardates($dd);


$dd=date('Y-m-d', strtotime('+6 year'));

createthisyeardates($dd);


$dd=date('Y-m-d', strtotime('+7 year'));

createthisyeardates($dd);


$dd=date('Y-m-d', strtotime('+8 year'));

createthisyeardates($dd);


$dd=date('Y-m-d', strtotime('+9 year'));

createthisyeardates($dd);


$dd=date('Y-m-d', strtotime('+10 year'));

createthisyeardates($dd);






















$connection = new mysqli($servername, $username, $password, $dbname);


$query_titles = "SELECT * FROM `thisyeardates` ORDER BY `thisyeardate`ASC;";

$result_titles = $connection->query($query_titles);
$row_titles = $result_titles->fetch_assoc();
do{
	echo $row_titles['thisyeardate'];
	echo " - ".$row_titles['full_name'];
	echo " - type: ".$row_titles['type'];
echo " - template: ".$row_titles['template_name'];
//echo " - Season: ".$row_titles['season'];
//	echo " - priority: ".$row_titles['item_priority'];
//	echo "<br><em>"	.$row_titles['notes']."</em>";//
	echo "<br>";
} while ($row_titles = $result_titles->fetch_assoc());

//$result_titles = $connection->query($query_titles);
//$row_titles = $result_titles->fetch_assoc();




//function addate($dayname, $daymonth, $note=''){
//global $servername, $username, $password, $dbname;	
//$connection = new mysqli($servername, $username, $password, $dbname);
//$pieces = explode("-", $daymonth);
//$dayno= $pieces[0]; // piece1
//$monthno=$pieces[1]; // piece2


//$query="INSERT INTO `annualdates` (`annualdate_id`, `anniversaryname`, `dayno`, `monthno`, `note`) VALUES (NULL, '".$dayname."', '".$dayno."', //'".$monthno."', '".$note."')";
////$result = $connection->query($query);	

//};

//addate("St Andrew","30-11","a Festival may not be celebrated on Sundays in Advent, Lent or Eastertide. Festivals coinciding with a Principal Feast or Principal Holy Day are transferred to the first available day.");
//addate("Christmas Eve","24-12");
//ddate("Christmas Day","25-12");
//addate("St Stephen","26-12");
//addate("St John","27-12");
//addate("Holy Innocents","1-12");
//addate("Epiphany","6-1","If the Epiphany (6 January) falls on a weekday it may, for pastoral reasons, be celebrated on the Sunday falling between 2 and 8 January inclusive.");
//addate("Naming and Circumcision of Jesus","1-12");
//addate("The Presentation of Christ in the Temple (Candlemas)","2-2","The Presentation of Christ in the Temple (Candlemas) is celebrated either on 2 February or on the Sunday falling between 28 January and 3 February.");

//addate("Christmas Day","25-12");






?>