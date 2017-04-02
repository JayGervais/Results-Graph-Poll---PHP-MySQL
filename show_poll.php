<?php

// check for variable data
$vote = $_POST['vote'];

if (empty($vote))
{
	echo '<p>You did not choose a contestant</p>';
	exit;
}

/********************************
   database query to get info
*********************************/

// log in to database
$db = new mysqli('localhost', 'poll', 'poll', 'poll');
//$db = new mysqli('tester.cynwbrug1nx.us-east-1.rds.amazonaws.com', 'tester_admin', 'pekoemini!!!!!', 'poll');
if (mysqli_connect_errno()) {
	echo '<p>Error: Could not connect to database.<br/>
	Please try again later.</p>';
	exit;
}

// add user's vote
$v_query = "UPDATE poll_results
	SET num_votes = num_votes + 1
	WHERE candidate = ?";
$v_stmt = $db->prepare($v_query);
$v_stmt->bind_param('s', $vote);
$v_stmt->execute();
$v_stmt->free_result();

// Get current results of poll
$r_query = "SELECT candidate, num_votes FROM poll_results";
$r_stmt = $db->prepare($r_query);
$r_stmt->execute();
$r_stmt->store_result();
$r_stmt->bind_result($candidate, $num_votes);
$num_candidates = $r_stmt->num_rows;

// calculate total number of votes so far
$total_votes = 0;

while ($r_stmt->fetch())
{
	$total_votes += $num_votes;
}

$r_stmt->data_seek(0);


/********************************
  initial calculation for graph
*********************************/

// set up constants
putenv('GDFONTPATH=/user/share/fonts/truetype/arial');

$width = 500; 		// width of image in pixels
$left_margin = 50;	// space to leave on left of graph
$right_margin = 50;	// space to leave on right of graph
$bar_height = 40;
$bar_spacing = $bar_height/2;
$font_name = 'Arial';
$title_size = 16;
$main_size= 12;
$small_size= 12;
$text_indent = 10; // poition for text labels from edge of image

// set up initial point to draw from
$x = $left_margin + 60;	// place to draw from baseline of graph
$y = 50;
$bar_unit = ($width-($x+$right_margin)) / 100;	// one point on the graph

// calculate height of graph - bars plus gaps plus some margin
$height = $num_candidates * ($bar_height + $bar_spacing) + 50;



/********************************
  set up base image
*********************************/
// create blank canvas
$im = imagecreatetruecolor($width, $height);

// allocate colors
$white = imagecolorallocate($im, 255, 255, 255);
$blue = imagecolorallocate($im, 0, 64, 128);
$black = imagecolorallocate($im, 0, 0, 0);
$pink = imagecolorallocate($im, 255, 78, 243);

$text_color = $black;
$percentage_color = $black;
$bg_color = $white;
$bar_color = $blue;
$number_color = $pink;

// create canvas to draw on
imagefilledrectangle($im, 0, 0, $width, $height, $bg_color);

// draw outline around canvas
imagerectangle($im, 0, 0, $width-1, $height-1, $line_color);

// add title
$title = 'Poll Results';
$title_dimensions = imagettfbbox($title_size, 0, $font_name, $title);
$title_legth = $title_dimensions[2] - $title_dimensions[0];
$title_height = abs($title_dimensions[7] - $title_dimensions[1]);
$title_above_line = abs($title_dimensions[7]);
$title_x = ($width-$title_legth)/2; // center on x
$title_y = ($y - $title_height)/2 + $title_above_line; // center in y gap

imagettftext($im, $title_size, 0, $title_x, $title_y, $text_color, $font_name, $title);

// draw base line from above first bar location
imageline($im, $x, $y-5, $x, $height-15, $line_color);


/********************************
  draw data into graph
*********************************/
// get each line of DB data and draw in graph
while ($r_stmt->fetch())
{
	if ($total_votes > 0) {
		$percent = intval(($num_votes/$total_votes)*100);
	} else {
		$percent = 0;
	}

$percent_dimensions = imagettfbbox($main_size, 0, $font_name, $percent.'%');

$percent_length = $percent_dimensions[2] - $percent_dimensions[0];

imagettftext($im, $main_size, 0, $width-$percent_length-$text_indent, $y+($bar_height/2), $percent_color, $font_name, $percent.'%');

// length of bar for this value
$bar_length = $x + ($percent * $bar_unit);

// draw bar for this value
imagefilledrectangle($im, $x, $y-2, $bar_length, $y+$bar_height, $bar_color);

// draw title for this value
imagettftext($im, $main_size, 0, $text_indent, $y+($bar_height/2), $text_color, $font_name, $candidate);

// draw outline showing 100%
imagerectangle($im, $bar_length+1, $y-2, ($x+(100*$bar_unit)), $y+$bar_height, $line_color);

// display numbers
imagettftext($im, $small_size, 0, $x+(100*$bar_unit)-50, $y+($bar_height/2), $number_color, $font_name, $num_votes.'/'.$total_votes);

// move down to next bar
$y=$y+($bar_height+$bar_spacing);

}


/********************************
  display image
*********************************/
header('Content-type: image/png');
imagepng($im);


/********************************
  clean up
*********************************/
$r_stmt->free_result();
$db->close();
imagedestroy($im);

?>