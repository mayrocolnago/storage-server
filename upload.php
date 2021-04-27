<?php @session_start();

$uploadsecuritylevel = 4;

@ini_set('upload_max_filesize', '99M');
@ini_set('post_max_size', '99M');

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

@include(__DIR__ .'/tokens.php');
if(!is_array($tokens ?? '')) $tokens = [];


if(($_SERVER['REDIRECT_URL'] ?? '') == '/upload.js') { 

  $_SESSION['upstoretoken'] = md5(($_SERVER['SERVER_NAME'] ?? 'localhost').md5(uniqid()));
?>
/*###############################################################
##           ## AUTO-UPLOAD SCRIPT WITH QUEUE ##               ##
##                     ### USAGE ###                           ##
#################################################################

  Setup a form with a file input:
  
  <form><input type="file" id="file"></form>

  $(document).ready(... //After JQuery initialization
  
    var onstart = function(data){ //imediatly exec when a file is being uploaded };
    var ondone = function(data){ //execute as soon as the file finishes upload };
  
    bindupload('#file',{ 'f':'name_sufix', 'p':'path/' }, onstart, ondone);


#################################################################
*/
<?php exit(str_replace(["\n","\r","  "], "", 
           str_replace('[SERVER_NAME]', ($_SERVER['SERVER_NAME'] ?? 'localhost'), 
           str_replace('[TOKEN]', ($_SERVER['token'] ?? hash('sha256',($_SESSION['upstoretoken'] ?? ''))), 
           @file_get_contents(__DIR__ .'/upload.js') ))) );

} else
  $tokens[] = hash('sha256',($_SESSION['upstoretoken'] ?? 'notfound'));


if(empty($_REQUEST['token'] ?? '')) exit;

if(!in_array($_REQUEST['token'],$tokens)) exitcod('','token-invalid');

function exitcod($h,$s='') { $r = array('result'=>$h); if($s != '') $r['err'] = $s; exit(json_encode($r)); }


$workingpath = __DIR__ . '/';
$workingpath = ((strpos($workingpath,'/storage/') !== false) ? $workingpath : $workingpath.'storage/');

$skipcheckfiletype = array('apk','xml','mp4','mkv','rmvb','mpg','mpeg','avi','mov','3gp','ogv','flv','gsm');
$gocheckupload = (isset($_REQUEST['fromurl'])) ? false : true;

//if(isset($_REQUEST['deletefile'])) if(strlen($_REQUEST['deletefile']) > 5) if(file_exists($filename = str_replace(array('..','//','/.'),'',preg_replace('/[^0-9a-zA-Z\.\-\_\/]/','',($_REQUEST['deletefile'] ?? ''))))) exitcod((@unlink($filename)) ? 1 : 0);

if((isset($_FILES['file'])) && (!empty(@$_FILES['file']))) if(isset($_FILES['file']['name'])) 
  foreach($skipcheckfiletype as $skp) if(strpos($_FILES['file']['name'],'.'.$skp) !== false)
    $gocheckupload = false;

function upstoreinnercheckupload($seclevel=3,&$filecont,$nome='',$mime_type='') {
   $keyban = array("echo","php","aspx","shell","sock","open","read","write","base64","eval","exec","dump","sql","code");
   $pg=""; $qp = 0;
   foreach($keyban as $kb) if(stripos($filecont,$kb) !== false){ $pg .= $kb.';'; $qp++; }
   if(($qp >= intval($seclevel)) and (intval($seclevel) >= 9)) {
     $msggm = '['.($_SERVER['HTTP_HOST'] ?? 'localhost').'] user'.((isset($_COOKIE['uid']))? ' '.($_COOKIE['uid'] - 999) : '').
              ' ip '.($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0').' uploaded file '.$nome.' as '.$mime_type.
              ' containing "'.$pg.'" as key for security breach at '.date('H:i:s d/m/Y')."\n\n"; 
     $nomean = 'analyse/upload'.date("YzGHis").'.txt';
     $fp = @fopen($nomean,"w");
     @fwrite($fp, $msggm.base64_encode($filecont));
     @fclose($fp); $filecont = '';
     return false;
   } else 
   return true;
}

if($gocheckupload ?? true) 
  foreach($_FILES as $nome=>$fgnome)
    if(!empty($fgnome['tmp_name'] ?? ''))
      if(!(upstoreinnercheckupload($uploadsecuritylevel, @file_get_contents($fgnome['tmp_name']), ($fgnome['name'] ?? 'null'), ($fgnome['type'] ?? 'text/plan') )))
          @file_put_contents($fgnome['tmp_name'], '');

$source = (isset($_REQUEST['f'])) ? preg_replace('/[^0-9a-zA-Z\-\_]/','',$_REQUEST['f']) : 'non'; $mime_type = '';
$path = (isset($_REQUEST['p'])) ? preg_replace('/[^a-z\/\_]/','',$_REQUEST['p']) : '';

try { if(strlen($path) > 0) {
  if($path[strlen($path)-1] != '/') $path[strlen($path)] = '/';
  if($path[0] == '/') $path = substr($path,1,strlen($path)); }
} catch(Exception $e) { $path = ''; }

while (strpos($path,'__') !== false) { $path = str_replace('__','_',$path); }
while (strpos($path,'//') !== false) { $path = str_replace('//','/',$path); }

if(strlen(trim($path)) <= 4) $path = 'root/';
$mapth = $workingpath.$path;

if(!(@is_dir(substr($mapth,0,-1))))
  @shell_exec('mkdir -m 777 -p "'.$mapth.'" && find '.$workingpath.'* -type d -exec sh -c \'touch $0/index.html && echo "php_flag engine off" > $0/.htaccess\' {} \\; ');

if(!(@is_dir($analysefolder = ($workingpath.'analyse/'))))
  @shell_exec('mkdir -m 777 -p "'.$analysefolder.'" && find '.$analysefolder.'* -type d -exec sh -c \'touch $0/index.html && echo "php_flag engine off" > $0/.htaccess\' {} \\; ');

$extensao = 'txt';
$tipo = 'document';
$taok = false;

if((isset($_REQUEST['download'])) && (isset($_REQUEST['file']))) {
  $mime_type = 'stream/unknown';
  if(file_exists($novonome = $path . $source . '_' . ((isset($_REQUEST['n'])) ? preg_replace('/[^0-9a-zA-Z\-\_]/','',$_REQUEST['n']) : uniqid(time())) . '.' . ((isset($_REQUEST['e'])) ? preg_replace('/[^0-9a-z]/','',$_REQUEST['e']) : '.txt'))) @unlink($novonome);
  if(!($fp_remote = @fopen($_REQUEST['file'], 'rb'))) exitcod('');
  if(!($fp_local = @fopen($novonome, 'wb'))) exitcod('');
  while($buffer = @fread($fp_remote, 8192)) @fwrite($fp_local, $buffer);
  @fclose($fp_remote);
  @fclose($fp_local);
  exitcod($novonome);
} else
  if((isset($_REQUEST['fromurl'])) && (isset($_REQUEST['file']))) {
    $mime_type = 'stream/unknown';
    if(file_exists($novonome = $path . $source . '_' . ((isset($_REQUEST['n'])) ? preg_replace('/[^0-9a-zA-Z\-\_]/','',$_REQUEST['n']) : uniqid(time())) . '.' . ((isset($_REQUEST['e'])) ? preg_replace('/[^0-9a-z]/','',$_REQUEST['e']) : '.txt'))) @unlink($novonome);
    if(@copy($_REQUEST['file'],$novonome)) exitcod($novonome); else exitcod('');
  } else
    if((!isset($_REQUEST['base64'])) && (!isset($_REQUEST['getfile']))) {
        //if(!(isset($_FILES['file']['name']) && $_FILES['file']['error'] == 0)) exit;
        if((!isset($_FILES['file'])) || (empty(@$_FILES['file']))) exit; //var_dump($_FILES['file']);
        $arquivo_tmp = $_FILES['file']['tmp_name'];
        $nome = $_FILES['file']['name'];
        $mime_type = $_FILES['file']['type'];
    } else
      if(!empty($imagem = $_POST['file']))
        if(isset($_REQUEST['base64'])) {
          list($tipo, $dados) = explode(';', $imagem);
          list(, $tipo) = explode(':', $tipo);
          list(, $dados) = explode(',', $dados);
          $arquivo_tmp = base64_decode($dados);
          $nome = md5(uniqid(time())).$extensao;
          $mime_type = $tipo;
          upstoreinnercheckupload($uploadsecuritylevel,$arquivo_tmp,$nome,$mime_type); }
        else
          if(isset($_REQUEST['getfile'])) {
            $arquivo_tmp = $imagem;
            $mime_type = 'text/plain';
            $nome = md5(uniqid(time())).'.txt';
            upstoreinnercheckupload($uploadsecuritylevel,$arquivo_tmp,$nome,$mime_type); }
      
if(isset($_REQUEST['mime'])) $mime_type = preg_replace('/[^0-9a-z\-\.\/]/','',$_REQUEST['mime']);

switch ($mime_type) {
    case "text/html":         $taok = true; $extensao = 'html'; $tipo = 'document'; break;
    case "text/css":          $taok = true; $extensao = 'css'; $tipo = 'document'; break;
    case "text/xml":          $taok = true; $extensao = 'xml'; $tipo = 'document'; break;
    case "text/plain":        $taok = true; $extensao = 'txt'; $tipo = 'document'; break;
    case "text/richtext":     $taok = true; $extensao = 'rtx'; $tipo = 'document'; break;
    case "application/pdf":   $taok = true; $extensao = 'pdf'; $tipo = 'document'; break;
    case "image/photoshop":   $taok = true; $extensao = 'psd'; $tipo = 'document'; break;
    case "image/x-photoshop": $taok = true; $extensao = 'psd'; $tipo = 'document'; break;
    
    case "application/msword":            $taok = true; $extensao = 'doc'; $tipo = 'document'; break;
    case "text/xls":                      $taok = true; $extensao = 'xls'; $tipo = 'document'; break;
    case "application/vnd.ms-excel":      $taok = true; $extensao = 'xla'; $tipo = 'document'; break;
    case "application/vnd.ms-powerpoint": $taok = true; $extensao = 'ppa'; $tipo = 'document'; break;

    case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":   $taok = true; $extensao = 'docx'; $tipo = 'document'; break;
    case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":         $taok = true; $extensao = 'xlsx'; $tipo = 'document'; break;
    case "application/vnd.openxmlformats-officedocument.presentationml.presentation": $taok = true; $extensao = 'pptx'; $tipo = 'document'; break;
    
    case "application/vnd.android.package-archive": $taok = true; $extensao = 'apk'; $tipo = 'document'; break;
    case "application/octet-stream":                $taok = true; $extensao = 'apk'; $tipo = 'document'; break;
    case "application/x-msdownload":                $taok = true; $extensao = 'exe'; $tipo = 'document'; break;
    
    case "application/zip":               $taok = true; $extensao = 'zip'; $tipo = 'document'; break;
    case "application/xml":               $taok = true; $extensao = 'xml'; $tipo = 'document'; break;
    case "application/x-apple-diskimage": $taok = true; $extensao = 'dmg'; $tipo = 'document'; break;
    case "application/x-gzip":            $taok = true; $extensao = 'gz'; $tipo = 'document'; break;
    case "application/x-tar":             $taok = true; $extensao = 'tar'; $tipo = 'document'; break;
    case "application/x-gtar":            $taok = true; $extensao = 'gtar'; $tipo = 'document'; break;

    case "application/x-pkcs12":       $taok = true; $extensao = 'p12'; $tipo = 'document'; break;
    case "application/x-x509-ca-cert": $taok = true; $extensao = 'crt'; $tipo = 'document'; break;
    
    case "video/x-msvideo":   $taok = true; $extensao = 'avi'; $tipo = 'video'; break;
    case "video/quicktime":   $taok = true; $extensao = 'mov'; $tipo = 'video'; break;
    case "video/mp4":         $taok = true; $extensao = 'mp4'; $tipo = 'video'; break;
    case "video/x-flv":       $taok = true; $extensao = 'flv'; $tipo = 'video'; break;
    case "video/x-matroska":  $taok = true; $extensao = 'mkv'; $tipo = 'video'; break;
    case "video/mpeg":        $taok = true; $extensao = 'mpg'; $tipo = 'video'; break;
    case "video/ogg":         $taok = true; $extensao = 'ogv'; $tipo = 'video'; break;
    case "video/3gpp":        $taok = true; $extensao = '3gp'; $tipo = 'video'; break;
    
    case "audio/basic":     $taok = true; $extensao = 'au';  $tipo = 'audio'; break;
    case "audio/mid":       $taok = true; $extensao = 'mid'; $tipo = 'audio'; break;
    case "audio/mpeg":      $taok = true; $extensao = 'mp3'; $tipo = 'audio'; break;
    case "audio/mp3":       $taok = true; $extensao = 'mp3'; $tipo = 'audio'; break;
    case "audio/x-aiff":    $taok = true; $extensao = 'aif'; $tipo = 'audio'; break;
    case "audio/x-mpegurl": $taok = true; $extensao = 'm3u'; $tipo = 'audio'; break;
    case "audio/x-wav":     $taok = true; $extensao = 'wav'; $tipo = 'audio'; break;
    case "audio/wav":       $taok = true; $extensao = 'wav'; $tipo = 'audio'; break;
    case "audio/gsm":       $taok = true; $extensao = 'gsm'; $tipo = 'audio'; break;
    
    case "image/jpeg":  $taok = true; $extensao = 'jpg'; $tipo = 'image'; break;
    case "image/jpg":   $taok = true; $extensao = 'jpg'; $tipo = 'image'; break;
    case "image/gif":   $taok = true; $extensao = 'gif'; $tipo = 'image'; break;
    case "image/png":   $taok = true; $extensao = 'png'; $tipo = 'image'; break;
    
    default: $taok = false;
}

if(!$taok) exitcod('','mime:'.$mime_type);
  
$novonome = $path . $source . '_' . ((isset($_REQUEST['n'])) ? preg_replace('/[^0-9a-zA-Z\-\_]/','',$_REQUEST['n']) : uniqid(time())) . '.' . ((isset($_REQUEST['e'])) ? preg_replace('/[^0-9a-z]/','',$_REQUEST['e']) : $extensao);

if(file_exists($novonome)) @unlink($novonome);

$taok = false;
if((!isset($_REQUEST['base64'])) && (!isset($_REQUEST['getfile'])))
    $taok = @move_uploaded_file($arquivo_tmp, $novonome);
else 
    $taok = ((file_put_contents($novonome, $arquivo_tmp) === strlen($arquivo_tmp)) ? true : false);

if(!$taok) exitcod('','leased');


$quality = (empty(@$_REQUEST['quality'] ?? "")) ? '30' : $_REQUEST['quality'];
$size = (empty(@$_REQUEST['size'] ?? "")) ? '1024' : $_REQUEST['size'];


if($tipo != 'image') exitcod($novonome);
exitcod( resizeCompressSize($novonome, $novonome, $size, $quality, $extensao) );


function resizeCompressSize($filename, $filedestination, $newwidth, $quality, $extensao) {

    $hasrequiredfc = true;
    $requirefunctions = ['imagecreatetruecolor', 'imagecreatefromjpeg', 'imagecreatefrompng', 'imagealphablending', 'imagesavealpha', 'imagecopyresampled', 'imagepng', 'imagecopyresized', 'imagejpeg', 'imagecreatefromgif'];
    foreach($requirefunctions as $rf) if(!function_exists($rf)) $hasrequiredfc = false;
    if(!$hasrequiredfc) return $filename;

    list($width, $height, $type, $attr) = getimagesize($filename);

    if ($width > $newwidth) {
        $newheight = ($newwidth * $height) / $width;
    } else {
        $newwidth = $width;
        $newheight = $height;
    }

    $thumb = imagecreatetruecolor($newwidth, $newheight);

    if (preg_match('/jpg|jpeg/i', $extensao))
        $source = imagecreatefromjpeg($filename);
    else if (preg_match('/png/i', $extensao)) {
        $source = imagecreatefrompng($filename);

        if (hasAlpha($source)) {
            imagealphablending($thumb, false);

            imagesavealpha($thumb, true);

            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            $quality = 9 - (int) ((0.9 * $quality) / 10.0);
            imagepng($thumb, $filedestination, 9);
        } else {
            imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            imagejpeg($thumb, $filedestination, $quality);
        }
    } else if (preg_match('/gif/i', $extensao))
        $source = imagecreatefromgif($filename);
    else
        return "";

    if (!preg_match('/png/i', $extensao)) {
        imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

        imagejpeg($thumb, $filedestination, $quality);
    }

    return $filedestination;
}

function hasAlpha($imgdata) {

    $hasrequiredfc = true;
    $requirefunctions = ['imagecreatetruecolor', 'imagealphablending', 'imagecopyresized', 'imagesx', 'imagesy'];
    foreach($requirefunctions as $rf) if(!function_exists($rf)) $hasrequiredfc = false;
    if(!$hasrequiredfc) return false;
    
    $w = imagesx($imgdata);
    $h = imagesy($imgdata);

    if ($w > 50 || $h > 50) { //resize the image to save processing if larger than 50px:
        $thumb = imagecreatetruecolor(10, 10);
        imagealphablending($thumb, FALSE);
        imagecopyresized($thumb, $imgdata, 0, 0, 0, 0, 10, 10, $w, $h);
        $imgdata = $thumb;
        $w = imagesx($imgdata);
        $h = imagesy($imgdata);
    }
    //run through pixels until transparent pixel is found:
    for ($i = 0; $i < $w; $i++) {
        for ($j = 0; $j < $h; $j++) {
            $rgba = imagecolorat($imgdata, $i, $j);
            if (($rgba & 0x7F000000) >> 24)
                return true;
        }
    }
    return false;
}

?>