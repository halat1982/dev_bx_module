<?php
namespace ITtower\Develop;

use Bitrix\Main\IO\FileOpenException;
use Bitrix\Main\IO\FileNotOpenedException;
use Bitrix\Security\LogicException;
use ITtower\Develop\Auth;

class File
{

    protected $actions = Array(
        "end" => "a+",
        "replace" => "w+",
        "create" => "x"
    );

    protected $arHandledFiles = Array();
    protected $arRemovedData = Array();

    const DEFAULT_LOG_FILE_NAME = "errors.log";

    public function writeToFile($path, $data, $mode="end")
    {
        if(file_exists($path)){
            if(is_writable($path)){
                $file = $this->getFile($path, $this->actions[$mode]);
                fwrite($file, $data);
                $this->arHandledFiles[$path] = $mode;
                fclose($file);
            } else {
                $this->removeHandledFilesData();
                throw new FileNotOpenedException($path);
            }
        } else {
            if($file = $this->getFile($path, $this->actions["create"])){
                fwrite($file, $data);
                $this->arHandledFiles[$path] = "create";
                fclose($file);
            } else {
                $this->removeHandledFilesData();
                throw new FileOpenException($path);
            }
        }
    }

    public function deleteFile($path)
    {
        if(file_exists($path)){
            $this->arRemovedData[$path] = file_get_contents($path);
            if(!unlink($path)){
                throw new FileOpenException($path);
            }
        }
    }

    public function removeModuleRecords($path)
    {
        $data = file_get_contents($path);
        $newData = preg_replace('#\#'.Auth::MODULE_SIGNATURE.'.*CLOSE_'.Auth::MODULE_SIGNATURE.'\##sUi', '', $data);
        $this->arRemovedData[$path] = $data;
        $this->writeToFile($path, $newData, "replace");

    }

    public function makeDir($path)
    {
        $arPathes = $this->getPathes($path);
        $dirPath = $arPathes["dir"];
        $fullPath = $dirPath.$arPathes["file"];
        if($dirPath){
            $dirPath = $_SERVER["DOCUMENT_ROOT"]."/".$dirPath;
            if(!is_dir($dirPath)){
                if(!mkdir($dirPath, 0755, true)){
                    throw new FileOpenException($path);
                }
            }
        }

        return $fullPath;
    }

    public function addPhpFunctions($arElemFile, $rootModulePath)
    {
        if(is_uploaded_file($arElemFile["tmp_name"])){

            if(!$this->validPhpSyntax($arElemFile["tmp_name"])){
                throw new \Exception("Wrong php file");
            }

            $fileData = file_get_contents($arElemFile["tmp_name"]);
            $this->writeToFile($_SERVER["DOCUMENT_ROOT"].$rootModulePath."/functions/script.php", $fileData, "replace");
        }

    }

    public function addJsFunctions($arElemFile, $rootModulePath)
    {
        if(is_uploaded_file($arElemFile["tmp_name"])){
            $fileData = file_get_contents($arElemFile["tmp_name"]);
            $this->writeToFile($_SERVER["DOCUMENT_ROOT"].$rootModulePath."/functions/js/ittower.develop_script.js", $fileData, "replace");
        }
    }

    public function clearUserData($rootModulePath)
    {
        $this->writeToFile($_SERVER["DOCUMENT_ROOT"].$rootModulePath."/functions/script.php", "", "replace");
        $this->writeToFile($_SERVER["DOCUMENT_ROOT"].$rootModulePath."/functions/js/ittower.develop_script.js", "", "replace");
    }

    protected function validPhpSyntax($filePath)
    {
        $output=null;
        $retval=null;
        exec("php ".$filePath, $output, $retval);
        return $retval === 0;
    }

    protected function getPathes($pathToFile): array
    {
        if(mb_substr($pathToFile, 0, 1) == "/"){
            $pathToFile = mb_substr($pathToFile, 1);
        }
        if(mb_substr($pathToFile, -1) == "/"){
            $pathToFile = mb_substr($pathToFile, 0, -1);
        }

        if(empty($pathToFile)){
            return Array("dir" => "", "file"=>self::DEFAULT_LOG_FILE_NAME);
        } else {
            if(strpos($pathToFile, "/") !== false){
                $file = substr(strrchr($pathToFile, "/"),1);
                return Array(
                    "dir" => str_replace($file,"", $pathToFile ),
                    "file" => $file
                );
            } else {
                return Array(
                    "dir" => "",
                    "file" => $pathToFile
                );
            }
        }
    }

    protected function removeHandledFilesData()
    {
        foreach ($this->arHandledFiles as $path => $mode) {
            if ($mode === 'end') {
                $this->removeModuleRecords($path);
            } else {
                $this->deleteFile($path);
            }
        }
        $this->arHandledFiles = Array();
    }

    protected function restoreHandledFilesData()
    {
        foreach ($this->arRemovedData as $path => $data) {
            $this->writeToFile($path, $data, $this->actions["replace"]);
        }
        $this->arRemovedData = Array();
    }

    protected function getFile($path, $mode = "a+")
    {
       return fopen($path, $mode);
    }



}