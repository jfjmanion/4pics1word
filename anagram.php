<?php
DEFINE('LOGGING', false);

if(LOGGING) {
	$start = microtime(true);
}

//letters to create words from
$letters = $_GET['letters'];
$letters_array = str_split($letters);

//number of letters in a word
$num_letters = $_GET['wordlen'];

//if we want to update the dictionary
$update_dictionary = $_GET['update'];

//array for all possible words
$all_words = array();

//filename of Dictionary
$file = "dict-" . $num_letters . ".sqlite";

//Load and or Update the dictionary
if(file_exists($file) && $update_dictionary){
	unlink($file);
	$pdo = new PDO('sqlite:'.dirname(__FILE__).'/'.$file);
	create_dictionary($pdo, $num_letters);
} else if(!file_exists($file)) {
	$pdo = new PDO('sqlite:'.dirname(__FILE__).'/'.$file);
	create_dictionary($pdo, $num_letters);
} else{
	$pdo = new PDO('sqlite:'.dirname(__FILE__).'/'.$file);
}
//get the potential words
recurse($pdo, "", $letters_array);

$words = are_words($pdo, $all_words);

foreach($words as $word){
	echo $word . "<br/>";
}

if(LOGGING) {	
	$finish = microtime(true);
	$total = $finish - $start;
	echo $total;
}
























/**
  * Recurses through letters to find all the permutations that can be made
  * @param String $word The current build of the word
  * @param Array $letters The letters that can be used
  */
function recurse(&$pdo, $word, $letters){
	global $num_letters, $all_words;
	
	//reset the indexes
	$letters = array_values($letters);

	$count = count($letters);
	for ($i = 0; $i < $count; $i++){
		$new_word = $word . $letters[$i];
		
		//if its the right size and unique, add it
		if (strlen($new_word) == $num_letters && !in_array($new_word, $all_words)){
			$all_words[] = "'" . $new_word . "'";
			continue;
		}
		
		//forward on the remaing letters
		$new_letters = $letters;
		unset($new_letters[$i]);
		
		//if there are more letters to check
		if (!empty($new_letters) && strlen($new_word) < $num_letters ){
			//if it get this far, then do the database lookup
			if(is_possible_word($pdo, $new_word)){
				recurse($pdo, $new_word, $new_letters);
			}
		}
	}
}	

function is_possible_word(&$pdo, $string) {
	$select = "SELECT count(value) FROM dictionary WHERE value like '{$string}%'";

	try {
		$stmt = $pdo->prepare($select);
		$stmt->execute();
		$value = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
	} catch (PDOException $e){
		var_dump($e);
		die();
	}

	if ((int) $value[0] > 0){
		return true;
	} else {
		return false;
	}
}

/**
  * Checks the database to see which words in an array are words
  * @param handle $pdo The pdo handle
  * @param Array $words Words to check to see if they are an array
  * @return Array The words that are 
  */
function are_words(&$pdo, $array){
	
	$words = implode(",", $array);
	$select = "SELECT value FROM dictionary WHERE value IN ({$words})";

	try {
		$stmt = $pdo->prepare($select);
		$stmt->execute();
		$value = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
	} catch (PDOException $e){
		$value = $e;
	}
	
	return $value;
}


function create_dictionary(&$pdo, $number_letters){

	// create the table if you need to
	$pdo->exec("CREATE TABLE dictionary(value TEXT PRIMARY KEY)");
	$stmt = $pdo->prepare('INSERT INTO dictionary(value) VALUES (:value)');
	$value = null;
	$inserted = array();
	
	// this binds the variables by reference so you can re-use the prepared statement
	$stmt->bindParam(':value', $value);
	//read the dictonary
	$file_handle = fopen('./dictionary/en_US.dic', "r");
	while (!feof($file_handle)) {
	   $line = fgets($file_handle);
	   $remove_at = strpos($line, "/");
	   //if no "/", ignore the substr
	   if ($remove_at){
	   		$line = substr($line, 0,$remove_at);
	   }
	   $value = trim(strtolower($line));
	   if (strlen($value) == $number_letters && !in_array($value, $inserted)){
		   $inserted[] = $value; 
		   $stmt->execute();
	   }
	  
	}
	fclose($file_handle);







}
