<?php
session_start();
$url_array = explode('?', 'http://'.$_SERVER ['HTTP_HOST'].$_SERVER['REQUEST_URI']);
$url = $url_array[0];
   
require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_DriveService.php';
$client = new Google_Client();
$client->setClientId('ид ключ');
$client->setClientSecret('секретный ключ');
$client->setRedirectUri($url);
$client->setScopes(array('https://www.googleapis.com/auth/drive'));
if (isset($_GET['code'])) {
    $_SESSION['accessToken'] = $client->authenticate($_GET['code']);
    header('location:'.$url);exit;
} elseif (!isset($_SESSION['accessToken'])) {
    $client->authenticate();
}
$files= array();
$directory = "Директория где хранятся данные в формате PDF с определенными названиями согласно ID таблицы"
$dir = dir($directory);  // Директория на сервере скорее всего будет связана с названием УЧП
// эта папка куда будут заливать все файлы на сервере у каждой кафедры должна быть своя папка или у УЧП? - 
// потом люди будут заходить иногда кто то и зависимости от принадлежности кафедры
// будет смотреть нужную папку и после нажатия кнопки будет выполнятся заливка всей папки и удаление оттуда файлов - 
// как это все произошло - все копируется в общедоступные папки по кафедрам на каком аккаунте 
// аккаунт можно сделать один, а можно несколько - настроить folderid после расшаривания
// создается массив
while ($file = $dir->read()) {
    if ($file != '.' && $file != '..') {
        $files[] = $file;
    }
}
$dir->close();
$folderId="ид папки "; // здесь в зависимости от подключения разных пользователей будет папка менятся,
// папки будут хранится в базе или в внешнем файл json

if (!empty($_POST)) { // передаются разные параметры 
    $client->setAccessToken($_SESSION['accessToken']);
    $service = new Google_DriveService($client);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file = new Google_DriveFile();
	$folder = new Google_DriveFile();
    foreach ($files as $file_name) {
        $file_path = $directory.'/'.$file_name;
        $mime_type = finfo_file($finfo, $file_path);
        $file->setTitle($file_name);
        $file->setDescription('This is a '.$mime_type.' document');
        $file->setMimeType($mime_type);
		if ($folderId != null) {
			$parent = new Google_ParentReference();
			$parent->setId($folderId);
			$file->setParents(array($parent));
		}
        $createdFile = $service->files->insert(
            $file,
            array(
                'data' => file_get_contents($file_path),
                'mimeType' => $mime_type,
            )
        );
	}
	print_r($createdFile);
    finfo_close($finfo);
    header('location:'.$url);exit;
}

