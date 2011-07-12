<?php
function htmlStart(){
	echo '<html>
	<head>
	<title>Foosball Stats</title>
	<link rel="stylesheet" type="text/css" href="/foosballStats/style.css"/>
	</head>
	<body>';
}

function scoreForms(){
	echo '<div id="loadstats">
	<form id="simple" action="" method="post" enctype="multipart/form-data">
	<h4>Enter Score:</h4>
	<label for="personOne">Person: </label>
	<input type="text" name="personOne" id="personOne"/>
	<label for="scoreOne">Score: </label>
	<input type="text" name="scoreOne" id="scoreOne"/>
	<h5>VS</h5>
	<label for="personTwo">Person: </label>
	<input type="text" name="personTwo" id="personTwo"/>
	<label for="scoreTwo">Score: </label>
	<input type="text" name="scoreTwo" id="scoreTwo"/>
	<br/><br/>
	<input type="submit" name="singleEntry" value="Submit"/>
	</form>
	
	<form id="multi" action="" method="post" enctype="multipart/form-data">
	<h4>Or upload a .csv file with the format Person,Score,Person,Score:</h4>
	<input type="file" name="file" id="file" />
	<input type="submit" name="multiEntry" value="Submit" />
	</form>
	<!--
	<form id="searchGames" action="playerList.php" enctype="multipart/form-data">
	<h4>Look-up game history by player:</h4>
	<input type="text" name="pSearch" id="pSearch" />
	<input type="submit" name="search" value="Search" />
	</form>
	-->
	</div>';	
}

function htmlEnd(){
	echo "</body></html>";
}

function dbOpen(){
	
	$con=mysql_connect("127.0.0.1","","");
	if (!$con)
	{
		die('Could not connect: ' . mysql_error());
	}

	$db_selected = mysql_select_db('foosball_stats', $con);
	if (!$db_selected) {
		die ('Can\'t use foos : ' . mysql_error());
	}

	return $con;
}

function dbClose($con){
	mysql_close($con);
}

function reloadRankings(){
	echo "<div id='rankings'>";
	echo "<table><tr><th>Rank</th><th>Player</th><th>GF/G</th></tr>";
	$con=dbOpen();
	$result = mysql_query('SELECT * FROM players ORDER BY GF_G DESC, PID ASC',$con);
	if (!$result) {
		die('Invalid query: ' . mysql_error());
	}
	$i=1;
	while ($row = mysql_fetch_assoc($result)) {
		echo "<tr><td>".$i."</td><td>".$row['PID']."</td><td>".$row['GF_G']."</td></tr>";
		$i++;
	}
	dbClose($con);
	echo "</table></div>";
}

function uploadInsert($line){
	list($playerOne, $scoreOne, $playerTwo, $scoreTwo) = split(",", $line);
	$playerOne=ucfirst(strtolower($playerOne));
	$playerTwo=ucfirst(strtolower($playerTwo));
	if($playerOne==null||$scoreOne==null||$playerTwo==null||$scoreTwo==null){
		echo "<span class='error'>Null value: ".$line."</span><br/>";
		return "null value";
	}
	elseif(preg_match("/\D/",$scoreOne)||preg_match("/\D/",$scoreTwo)){
		echo "<span class='error'>Invalid score: ".$line."</span><br />";
		return "score not a number";
	}
	elseif(strcmp($playerOne, $playerTwo)==0){
		echo "<span class='error'>You can't play yourself silly!: ".$line."</span><br />";
		return "pone equals ptwo";
	}
	elseif($scoreOne==$scoreTwo){
		echo "<span class='error'>Can't have a tie in foosball!: ".$line."</span><br />";
		return "tie game";
	}
	else{
		$con=dbOpen();
		if($scoreOne>$scoreTwo){
			$winner=$playerOne;
			$winnerScore=$scoreOne;
			$loser=$playerTwo;
			$loserScore=$scoreTwo;
		}
		else{
			$winner=$playerTwo;
			$winnerScore=$scoreTwo;
			$loser=$playerOne;
			$loserScore=$scoreOne;
		}
		//echo $winner." ".$wScore." ".$loser." ".$lScore."<br/>";

		$loserResult = mysql_query("INSERT INTO players (PID,W,L,GP,G,GA,GF_G) VALUES('".$loser."','0','1','1','".$loserScore."','".$winnerScore."','".$loserScore."') ON DUPLICATE KEY UPDATE L=L+1,GP=GP+1,G=G+".$loserScore.",GA=GA+".$winnerScore.",GF_G=G/GP",$con);
		if (!$loserResult) {
			die('Invalid loser query: ' . mysql_error());
		}

		$winnerResult = mysql_query("INSERT INTO players (PID,W,L,GP,G,GA,GF_G) VALUES('".$winner."','1','0','1','".$winnerScore."','".$loserScore."','".$winnerScore."') ON DUPLICATE KEY UPDATE W=W+1,GP=GP+1,G=G+".$winnerScore.",GA=GA+".$loserScore.",GF_G=G/GP",$con);
		if (!$winnerResult) {
			die('Invalid winner query: ' . mysql_error());
		}

		$gameResult = mysql_query("INSERT INTO games (PID_winner, w_score, PID_loser, l_score) VALUES ('".$winner."','".$winnerScore."','".$loser."','".$loserScore."')",$con);
		if(!$gameResult){
			die('Invalid game query: ' . mysql_error());
		}

		dbClose($con);
	}

}

function processFile(){
	if ($_FILES["file"]["error"] > 0){
		echo "<span class='error'>Error: " . $_FILES["file"]["error"] . "</span><br />";
		return "null file";
	}
	elseif ($_FILES["file"]["type"]!="text/csv"){
		echo "<span class='error'>File type must be csv</span><br />";
		return "not csv";
	}
	else{
		$file=fopen($_FILES["file"]["tmp_name"],'r');
		$line=fgets($file);
		if(preg_match("/\d/",$line)){
				uploadInsert(rtrim($line));
		}
		else{
			echo "<span class='error'>Ignoring header line: ".$line."</span><br />";
			$error="header line";
		}
		while (!feof($file)){
			$line=fgets($file);
			uploadInsert(rtrim($line));
		}
		fclose($file);
		return $error;
	}
}

htmlStart();
?>
<div class='errorContainer'>
<?php
if ($_POST['singleEntry']) {
	uploadInsert($_POST['personOne'].','.$_POST['scoreOne'].','.$_POST['personTwo'].','.$_POST['scoreTwo']);
}

if ($_POST['multiEntry']) {
	processFile();
}
?>
</div>
<?php
reloadRankings();
scoreForms();
htmlEnd();
?>