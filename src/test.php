<?php
$stop_date = '2009-09-30 20:24:00';
$stop_date = date('l, F j', strtotime($stop_date));
echo $stop_date."<hr>";
$stop_date = date('l, F j', strtotime($stop_date . ' +1 day'));
echo $stop_date;
?>
