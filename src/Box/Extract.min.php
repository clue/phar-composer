<?php
namespace Herrera\Box;
use InvalidArgumentException;
use LengthException;
use RuntimeException;
use UnexpectedValueException;
define('BOX_EXTRACT_PATTERN_DEFAULT','__HALT'.'_COMPILER(); ?>');
define('BOX_EXTRACT_PATTERN_OPEN',"__HALT"."_COMPILER(); ?>\r\n");
class Extract
{
const PATTERN_DEFAULT=BOX_EXTRACT_PATTERN_DEFAULT;
const PATTERN_OPEN=BOX_EXTRACT_PATTERN_OPEN;
const GZ=4096;
const BZ2=8192;
const MASK=12288;
private$file;
private$handle;
private$stub;
function __construct($file,$stub){
if(!is_file($file)){
throw new InvalidArgumentException(sprintf('The path "%s" is not a file or does not exist.',$file
));
}
$this->file=$file;
$this->stub=$stub;
}
static function findStubLength($file,$pattern=self::PATTERN_OPEN
){
if(!($fp=fopen($file,'rb'))){
throw new RuntimeException(sprintf('The phar "%s" could not be opened for reading.',$file
));
}
$stub=null;
$offset=0;
$combo=str_split($pattern);
while(!feof($fp)){
if(fgetc($fp)===$combo[$offset]){
$offset++;
if(!isset($combo[$offset])){
$stub=ftell($fp);
break;
}
}else{
$offset=0;
}
}
fclose($fp);
if(null===$stub){
throw new InvalidArgumentException(sprintf('The pattern could not be found in "%s".',$file
));
}
return$stub;
}
function go($dir=null){
if(null===$dir){
$dir=rtrim(sys_get_temp_dir(),'\\/').DIRECTORY_SEPARATOR
.'pharextract'.DIRECTORY_SEPARATOR
.basename($this->file,'.phar');
}else{
$dir=realpath($dir);
}
$md5=$dir.DIRECTORY_SEPARATOR.md5_file($this->file);
if(file_exists($md5)){
return$dir;
}
if(!is_dir($dir)){
$this->createDir($dir);
}
$this->open();
if(-1===fseek($this->handle,$this->stub)){
throw new RuntimeException(sprintf('Could not seek to %d in the file "%s".',$this->stub,$this->file
));
}
$info=$this->readManifest();
if($info['flags']&self::GZ){
if(!function_exists('gzinflate')){
throw new RuntimeException('The zlib extension is (gzinflate()) is required for "%s.',$this->file
);
}
}
if($info['flags']&self::BZ2){
if(!function_exists('bzdecompress')){
throw new RuntimeException('The bzip2 extension (bzdecompress()) is required for "%s".',$this->file
);
}
}
self::purge($dir);
$this->createDir($dir);
$this->createFile($md5);
foreach($info['files']as$info){
$path=$dir.DIRECTORY_SEPARATOR.$info['path'];
$parent=dirname($path);
if(!is_dir($parent)){
$this->createDir($parent);
}
if(preg_match('{/$}',$info['path'])){
$this->createDir($path,511,false);
}else{
$this->createFile($path,$this->extractFile($info));
}
}
return$dir;
}
static function purge($path){
if(is_dir($path)){
foreach(scandir($path)as$item){
if(('.'===$item)||('..'===$item)){
continue;
}
self::purge($path.DIRECTORY_SEPARATOR.$item);
}
if(!rmdir($path)){
throw new RuntimeException(sprintf('The directory "%s" could not be deleted.',$path
));
}
}else{
if(!unlink($path)){
throw new RuntimeException(sprintf('The file "%s" could not be deleted.',$path
));
}
}
}
private function createDir($path,$chmod=511,$recursive=true){
if(!mkdir($path,$chmod,$recursive)){
throw new RuntimeException(sprintf('The directory path "%s" could not be created.',$path
));
}
}
private function createFile($path,$contents='',$mode=438){
if(false===file_put_contents($path,$contents)){
throw new RuntimeException(sprintf('The file "%s" could not be written.',$path
));
}
if(!chmod($path,$mode)){
throw new RuntimeException(sprintf('The file "%s" could not be chmodded to %o.',$path,$mode
));
}
}
private function extractFile($info){
if(0===$info['size']){
return'';
}
$data=$this->read($info['compressed_size']);
if($info['flags']&self::GZ){
if(false===($data=gzinflate($data))){
throw new RuntimeException(sprintf('The "%s" file could not be inflated (gzip) from "%s".',$info['path'],$this->file
));
}
}elseif($info['flags']&self::BZ2){
if(false===($data=bzdecompress($data))){
throw new RuntimeException(sprintf('The "%s" file could not be inflated (bzip2) from "%s".',$info['path'],$this->file
));
}
}
if(($actual=strlen($data))!==$info['size']){
throw new UnexpectedValueException(sprintf('The size of "%s" (%d) did not match what was expected (%d) in "%s".',$info['path'],$actual,$info['size'],$this->file
));
}
$crc32=sprintf('%u',crc32($data)&4294967295);
if($info['crc32']!=$crc32){
throw new UnexpectedValueException(sprintf('The crc32 checksum (%s) for "%s" did not match what was expected (%s) in "%s".',$crc32,$info['path'],$info['crc32'],$this->file
));
}
return$data;
}
private function open(){
if(null===($this->handle=fopen($this->file,'rb'))){
$this->handle=null;
throw new RuntimeException(sprintf('The file "%s" could not be opened for reading.',$this->file
));
}
}
private function read($bytes){
$read='';
$total=$bytes;
while(!feof($this->handle)&&$bytes){
if(false===($chunk=fread($this->handle,$bytes))){
throw new RuntimeException(sprintf('Could not read %d bytes from "%s".',$bytes,$this->file
));
}
$read.=$chunk;
$bytes-=strlen($chunk);
}
if(($actual=strlen($read))!==$total){
throw new RuntimeException(sprintf('Only read %d of %d in "%s".',$actual,$total,$this->file
));
}
return$read;
}
private function readManifest(){
$size=unpack('V',$this->read(4));
$size=$size[1];
$raw=$this->read($size);
$count=unpack('V',substr($raw,0,4));
$count=$count[1];
$aliasSize=unpack('V',substr($raw,10,4));
$aliasSize=$aliasSize[1];
$raw=substr($raw,14+$aliasSize);
$metaSize=unpack('V',substr($raw,0,4));
$metaSize=$metaSize[1];
$offset=0;
$start=4+$metaSize;
$manifest=array('files'=>array(),'flags'=>0,);
for($i=0;$i<$count;$i++){
$length=unpack('V',substr($raw,$start,4));
$length=$length[1];
$start+=4;
$path=substr($raw,$start,$length);
$start+=$length;
$file=unpack('Vsize/Vtimestamp/Vcompressed_size/Vcrc32/Vflags/Vmetadata_length',substr($raw,$start,24));
$file['path']=$path;
$file['crc32']=sprintf('%u',$file['crc32']&4294967295);
$file['offset']=$offset;
$offset+=$file['compressed_size'];
$start+=24+$file['metadata_length'];
$manifest['flags']|=$file['flags']&self::MASK;
$manifest['files'][]=$file;
}
return$manifest;
}
}
