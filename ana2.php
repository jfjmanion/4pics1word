<?php
$start = microtime(true);
$letters = "doiasasgilfs";
$letters_array = str_split($letters);
$num_letters = $_GET['wordlen'];
$all_words = array();
$file = "dict-".$num_letters.".sqlite";
$update_dictionary = false;

if(file_exists($file) && $update_dictionary){
	unlink($file);
	$pdo = new PDO('sqlite:'.dirname(__FILE__).'/'.$file, null, null, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));
	//make sure the dictionary exists
	create_dictionary($pdo, $num_letters);
} else {
	$pdo = new PDO('sqlite:'.dirname(__FILE__).'/'.$file, null, null, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION));

}

//get the potential words
recurse("", $letters_array);

$words = are_words($pdo, $all_words);

foreach($words as $word){
	echo $word . "<br/>";
}	
$finish = microtime(true);


$total = $finish - $start;
echo $total;


/**
  * Recurses through letters to find all the permutations that can be made
  * @param String $word The current build of the word
  * @param Array $letters The letters that can be used
  */
function recurse($word, $letters){
	global $num_letters, $all_words;
	
	//reset the indexes
	$letters = array_values($letters);
	
	$count = count($letters);
	for ($i = 0; $i < $count; $i++){
		$new_word = $word . $letters[$i];
		
		//if its the right size and unique, add it
		if (strlen($new_word) == $num_letters && !in_array($new_word, $all_words)){
			$all_words[] = "'" . $new_word . "'";
		}
		
		//forward on the remaing letters
		$new_letters = $letters;
		unset($new_letters[$i]);
		
		//if there are more letters to check
		if (!empty($new_letters) && strlen($new_word) <= $num_letters){
			recurse($new_word, $new_letters);
		}
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
	$pdo->exec("CREATE TABLE dictionary(id TEXT PRIMARY KEY, value TEXT)");
	$stmt = $pdo->prepare('INSERT INTO dictionary(id, value) VALUES(:id, :value)');
	$id = null;
	$value = null;
	
	// this binds the variables by reference so you can re-use the prepared statement
	$stmt->bindParam(':value', $value);
	$stmt->bindParam(':id', $id);
	$id= 0;
	//read the dictonary
	$file_handle = fopen('./en_US.dic', "r");
	while (!feof($file_handle)) {
	   $line = fgets($file_handle);
	   $remove_at = strpos($line, "/");
	   //if no "/", ignore the substr
	   if ($remove_at){
	   		$line = substr($line, 0,$remove_at);
	   }
	   if (strlen($line) == $number_letters){
		   $value = $line;
		   $stmt->execute();
		   $id++;
	   }
	  
	}
	fclose($file_handle);







}
