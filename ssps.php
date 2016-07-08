<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
<style>
	.organic {
		width: 100%;
		border: 1px solid black;
	}
	.organic td {text-align: center;}
</style>
<?php
//////////////Соединение с базой данных//////////////////
define("DB_HOST", "localhost");
define("DB_LOGIN", "root");
define("DB_PASS", "");
define("DB_NAME", "new_db");
$link = mysqli_connect(DB_HOST, DB_LOGIN, DB_PASS, DB_NAME);
if (!$link) echo "Ошибка соединения с базой данных: " . mysqli_connect_error();
//////////////Соединение с базой данных//////////////////


////////////////////Если при нажатии кнопки отправляеются POST то запустить функцию на очистку бд и обновить стр/////////////////////////////////////
if ($_SERVER["REQUEST_METHOD"] == "POST") {	
	delDB();
	header("Location: " . $_SERVER["PHP_SELF"]);
	exit;
}
?>
<form action="" method="POST">
	<p><button type="submit" name="check" value=true>Обновить данные</button></p>
</form>
<?php
$table_class = "organic";
if(!$last_month = checkMonth()) {
	echo "<p style='font-size: 22px; float: right;'>Обновите данные!</p>";
} else echo "<p style='font-size: 18px; float: right;'>Данные корректны за прошлый месяц.</p>";
////////////////////Если массив пуст, то попытаться запустить функции по обработке JSON и записи в бд////////////////////////////////////
if(!$arrDB = getData()) {
	if(!addStat(getArrInJson())) {
		echo "Ошибка при попытке прочитать данные";
		exit;
	} else header("Location: " . $_SERVER["PHP_SELF"]);
} 
?>
<p>MySQL DB</p>
<!--///////////////////////////-->
<table class='<?=$table_class?>'>
<tr>
	<th>№</th>
	<th>Site name</th>	
	<th>Visits</th>
	<th>Visitors</th>
	<th>Bounce rate</th>
	<th>Avg Time on site</th>
	<th>Date</th>
</tr>
<?php
$db_num = 1;
foreach ($arrDB as $db) {
?>
<tr>
<td><?=$db_num++?></td>
<td><?=$db['name']?></td>
<td><?=$db['visits']?></td>
<td><?=$db['visitors']?></td>
<td><?=$db['bouncerate']?></td>
<td><?=date("i:s", $db['avrtime']) . " (" . round($db['avrtime']) . ")";?></td>
<td><?=$db['month']?></td>
</tr>
<?php	
}
?>
</table>
<!--///////////////////////////-->
<?php
//функция очищает таблицу бд
function delDB() {
	global $link;	
	$sql = 'DELETE FROM ssps';
	$result = mysqli_query($link, $sql);	
	if(!$result) return false;
	mysqli_close($link);
	return true;
}
//функция переводит статистику за пр месяц которая в объекте json, в многомерный ассоц массив
function getArrInJson() {
	$arr = array();
	$url="https://www.some-site.com/v3/Reporting/spaces/10146/Keymetrics/?start_period=current_month-1&end_period=current_month-1&period_type=agg&format=json";
	$ch = curl_init();
	$username = "admin";
	$password = "12345";
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL,$url);
	$result=curl_exec($ch);
	$obj = json_decode($result, true);
	foreach ($obj as $value) {
		$name = $value['definition']['profileName'];		
		$arr[$name]['pid'] = $pid = $value['definition']['profileID'];
		$arr[$name]['name'] = $value['definition']['profileName'];		
		$arr[$name]['visits'] = $value['data'][0]['SubRows'][$pid]['measures']['Visits'];
		$arr[$name]['visitors'] = $value['data'][0]['SubRows'][$pid]['measures']['Visitors'];
		$arr[$name]['bouncerate'] = round($value['data'][0]['SubRows'][$pid]['measures']['BounceRate'], 2);
		$arr[$name]['avgtimeonsite'] = $value['data'][0]['SubRows'][$pid]['measures']['AvgTimeonSite'];
		$arr[$name]['month'] = $value['data'][0]['start_date'];
	}
	return $arr; 
}
//функция вывода данных из БД в массив
function getData() {
	global $link;
	$dataDB = array();
	$last_month = date("Y-m", strtotime("last Month"));
	$sql = "SELECT pid, name, visits, visitors, bouncerate, avrtime, month FROM ssps WHERE month = \"$last_month\"";
	$result = mysqli_query($link, $sql);
	if(!$result) return false;
	$dataDB = mysqli_fetch_all($result, MYSQLI_ASSOC);
	mysqli_free_result($result);
	return $dataDB;
}
//функция сравнения месяца статистики и месяца в базе данных
function checkMonth() { 
	global $link;
	$last_month = date("Y-m", strtotime("last Month"));
	$sql = "SELECT month FROM ssps WHERE pid = 'zRBKLXRZMF6'";
	if(!$result = mysqli_query($link, $sql)) return false;
	$check = mysqli_fetch_assoc($result);
	mysqli_free_result($result);
	//mysqli_close($link);
	if($check['month'] === $last_month)	return true;
	else return false;
}
//функция добавляет статистику из массива в базу данных
function addStat($arr) {
	global $link;
	$sql = "INSERT INTO ssps (pid, name, visits, visitors, bouncerate, avrtime, month) VALUES (?, ?, ?, ?, ?, ?, ?)";
	if (!$stmt = mysqli_prepare($link, $sql)) return false;
	foreach ($arr as $item) {
		mysqli_stmt_bind_param($stmt, "ssiidss", $item['pid'], $item['name'], $item['visits'], $item['visitors'], $item['bouncerate'], $item['avgtimeonsite'], $item['month']);
		mysqli_stmt_execute($stmt);	
	}	
	mysqli_stmt_close($stmt);
	return true; 
}
function getLastMonth() {
	$lMonth = date('m')-1;
	$today = '';
	switch ($lMonth) {
		case 1:
				$today = "Январь";
			break;
		case 2:
				$today = "Февраль";
			break;
		case 3:
				$today = "Март";
			break;
		case 4:
				$today = "Апрель";
			break;
		case 5:
				$today = "Май";
			break;
		case 6:
				$today = "Июнь";
			break;
		case 7:
				$today = "Июль";
			break;
		case 8:
				$today = "Август";
			break;
		case 9:
				$today = "Сентябрь";
			break;
		case 10:
				$today = "Октябрь";
			break;
		case 11:
				$today = "Ноябрь";
			break;
		case 12:
				$today = "Декабрь";
			break;		
	}
	return $today;
}
?>
</body>
</html>