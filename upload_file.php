<?php
function uploadInsert($line){
	list($playerOne, $scoreOne, $playerTwo, $scoreTwo) = split(",", $line);
	if($playerOne==null||$scoreOne==null||$playerTwo==null||$scoreTwo==null){
		echo "Null value: ".$line."<br/>";
	}
	elseif(preg_match("/\D/",$scoreOne)||preg_match("/\D/",$scoreTwo)){
		echo "Invalid score: ".$line."<br />";
	}
	elseif($playerOne==$playerTwo){
		echo "You can't play yourself silly!: ".$line."<br />";
	}
	elseif($scoreOne==$scoreTwo){
		echo "Can't have a tie in foosball!: ".$line."<br />";
	}
	else{
		if($scoreOne>$scoreTwo){
			$winner=$playerOne;
			$wScore=$scoreOne;
			$loser=$playerTwo;
			$lScore=$scoreTwo;
		}
		else{
			$winner=$playerTwo;
			$wScore=$scoreTwo;
			$loser=$playerOne;
			$lScore=$scoreOne;
		}
		echo $winner." ".$wScore." ".$loser." ".$lScore."<br/>";
	}
}

if ($_POST['singleEntry']) {
	uploadInsert($_POST['personOne'].','.$_POST['scoreOne'].','.$_POST['personTwo'].','.$_POST['scoreTwo']);
}

if ($_POST['multiEntry']) {


	if ($_FILES["file"]["error"] > 0){
		echo "Error: " . $_FILES["file"]["error"] . "<br />";
	}
	else{
		$file=fopen($_FILES["file"]["tmp_name"],'r');
		$line=fgets($file);
		if(ereg($line,"[0-9]")){
			uploadInsert($line);
		}
		else{
			echo "Ignoring header line: ".$line."<br />";
		}
		while (!feof($file)){
			$line=fgets($file);
			uploadInsert(rtrim($line));
		}
		fclose($file);
	}
}
?>