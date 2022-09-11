<?php
use \Bitrix\Main\Application;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;  
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Entity\Base;
use \ITtower\Develop\Auth;
use \ITtower\Develop\File;
use \ITtower\Develop\Settings;

 
Class ittower_develop extends CModule
{
    private $exclusionAdminFiles;

    public $MODULE_ID = "ittower.develop";

    const AUTH = "auth";
    function __construct()
    {
        $this->exclusionAdminFiles=array(
            '..',
            '.',
            'menu.php',
        );

    	$arModuleVersion = array();
        include(__DIR__."/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_DESCRIPTION = Loc::getMessage("ITTOWER_DEVELOP_MODULE_DESC");
        $this->MODULE_NAME = Loc::getMessage("ITTOWER_DEVELOP_MODULE_NAME");

        $this->PARTNER_NAME = Loc::getMessage("ITTOWER_DEVELOP_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("ITTOWER_DEVELOP_PARTNER_URI");

        $this->MODULE_SORT = 500;
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS='Y'; // ?
        $this->MODULE_GROUP_RIGHTS = "Y"; // ?

        CModule::AddAutoloadClasses(
            '',
            array(
                'ITtower\Develop\File' => $this->GetPath(true).'/lib/Install/File.php',
                'ITtower\Develop\Auth' => $this->GetPath(true).'/lib/Install/Auth.php',
                'ITtower\Develop\Settings' => $this->GetPath(true).'/lib/Install/Settings.php',
                'ITtower\Develop\Config' => $this->GetPath(true).'/lib/config.php',
            )
        );

    }



    //defining path to module
    public function GetPath($notDocumentRoot=false)
    {
        if($notDocumentRoot)
            return str_ireplace(Application::getDocumentRoot(),'',dirname(__DIR__));
        else
            return dirname(__DIR__);
    }

    //checking if d7 supporting
    public function isVersionD7()
    {
        return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
    }

    protected function checkAuthData($request)
    {
        if($request["savedata"] == "Y"){
            if(!empty($request["login"]) && !empty($request["password"]) && !empty($request["password_confirm"])){
                return $request["password"] === $request["password_confirm"];
            }
            return false;
        }

        return true;
    }

    protected function checkLogData($request)
    {
        if($request["logger"] == "Y"){
            $logPath = trim($request["log_path"]);
            if(!empty($logPath)){
                return preg_match('/^([a-zA-Z0-9\/._-])+$/', $logPath) === 1;
            }
            return true;
        }

        return true;
    }


    function DoInstall()
    {
    	global $APPLICATION;
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        if($this->isVersionD7())
        {
            if($request["step"]<2){
                $APPLICATION->IncludeAdminFile(Loc::getMessage("ITTOWER_DEVELOP_INSTALL_TITLE"), $this->GetPath()."/install/step.php");
            } else if($request["step"] == 2){
                $errors = false;
                if(!$this->checkAuthData($request)){
                    $APPLICATION->ThrowException(Loc::getMessage("ITTOWER_DEVELOP_WRONG_AUTH_DATA"));
                    $errors = true;
                } else if(!$this->checkLogData($request)){
                    $APPLICATION->ThrowException(Loc::getMessage("ITTOWER_DEVELOP_WRONG_LOG_ADDRESS"));
                    $errors = true;
                }
                $file = new ITtower\Develop\File();
                if($errors === false){
                    try {
                        if($request["savedata"] == "Y"){
                            $file->writeToFile($_SERVER["DOCUMENT_ROOT"]."/.htaccess", Auth::getComposedHtaccessString());
                            $file->writeToFile($_SERVER["DOCUMENT_ROOT"]."/.htpasswd", Auth::getComposedAuth($request["login"], $request["password"]), "replace");
                            \Ittower\Develop\Config::setOption(self::AUTH, "Y");
                        }

                        if($request["logger"] == "Y"){
                            $settings = new Settings($file);
                            $settings->setLog(trim($request["log_path"]));
                        }
                    } catch(Bitrix\Main\SystemException $e){
                        $errors = true;
                        $APPLICATION->ThrowException(Loc::getMessage("ITTOWER_DEVELOP_ERROR_FILE_WRITE", Array("#FILENAME#" => $e->getPath())));
                    }
                }

                if($errors === false){
                    if(isset($_FILES["file_php"])){
                        try{
                            $file->addPhpFunctions($_FILES["file_php"], $this->GetPath(true));
                        } catch(Exception $e){
                            $errors = true;
                            if(method_exists($e, 'getPath')){
                                $APPLICATION->ThrowException(Loc::getMessage("ITTOWER_DEVELOP_ERROR_FILE_WRITE", Array("#FILENAME#" => $e->getPath())));
                            } else {
                                $APPLICATION->ThrowException(Loc::getMessage("ITTOWER_DEVELOP_WRONG_PHP_VALIDATION"));
                            }
                        }
                    }
                    if(isset($_FILES["file_js"])){
                        try{
                            $file->addJsFunctions($_FILES["file_js"], $this->GetPath(true));
                        } catch (Bitrix\Main\SystemException $e) {
                            $errors = true;
                            $APPLICATION->ThrowException(Loc::getMessage("ITTOWER_DEVELOP_ERROR_FILE_WRITE", Array("#FILENAME#" => $e->getPath())));
                        }
                    }
                }

                if($errors === false){
                    ModuleManager::registerModule($this->MODULE_ID);
                    $this->InstallFiles();
                    $this->InstallEvents();
                } else {
                    $this->uninstallAction(false);
                }
                $APPLICATION->IncludeAdminFile(Loc::getMessage("ITTOWER_DEVELOP_INSTALL_TITLE"), $this->GetPath()."/install/step2.php");
            }
        }
        else
        {
            $APPLICATION->ThrowException(Loc::getMessage("ITTOWER_DEVELOP_INSTALL_ERROR_VERSION"));
        }

    }

    function DoUninstall()
    {
        if(\ITtower\Develop\Config::getOption(self::AUTH) === "Y"){
            if($_SERVER["REMOTE_USER"] != ""){
                $this->uninstallAction();
            } else {
                header('WWW-Authenticate: Basic realm="'.$this->MODULE_ID.'"');
                header('HTTP/1.0 401 Unauthorized');
                echo Loc::getMessage("ITTOWER_DEVELOP_WRONG_AUTH_MESSAGE");
                exit;
            }
        } else {
            $this->uninstallAction();
        }
    }

    protected function uninstallAction($showUninstallPage = true)
    {
        global $APPLICATION;
        $errors = false;
        $file = new ITtower\Develop\File();
        try{
            $file->removeModuleRecords($_SERVER["DOCUMENT_ROOT"]."/.htaccess");
            $file->deleteFile($_SERVER["DOCUMENT_ROOT"]."/.htpasswd");
            $file->clearUserData($this->GetPath(true));
            \ITtower\Develop\Config::setOption(self::AUTH);
        } catch (Bitrix\Main\SystemException $e) {
            $errors = true;
            $APPLICATION->ThrowException(Loc::getMessage("ITTOWER_DEVELOP_ERROR_FILE_WRITE", Array("#FILENAME#" => $e->getPath())));
        }

        if(!$errors){
            $this->UnInstallFiles();
            $this->UnInstallEvents();
            UnRegisterModule($this->MODULE_ID);
        }
        if($showUninstallPage === true){
            $APPLICATION->IncludeAdminFile(Loc::getMessage("ITTOWER_DEVELOP_UNINSTALL_TITLE"), $this->GetPath()."/install/unstep.php");
        }

    }

    function InstallEvents()
    {
        \Bitrix\Main\EventManager::getInstance()->registerEventHandler("main", 'OnPageStart', $this->MODULE_ID, '\Ittower\Develop\Events', 'register');
    }

    function UnInstallEvents()
    {
        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler("main", 'OnPageStart', $this->MODULE_ID, '\Ittower\Develop\Events', 'register');
    }
 
    function InstallFiles()
    {

        if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/admin'))
        {
            //CopyDirFiles($this->GetPath() . "/install/admin/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin/"); //if files exists to copy
            if ($dir = opendir($path))
            {
                while (false !== $item = readdir($dir))
                {
                    if (in_array($item,$this->exclusionAdminFiles))
                        continue;
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$this->MODULE_ID."_".$item,
                        '<'.'? require($_SERVER["DOCUMENT_ROOT"]."'.$this->GetPath(true).'/admin/'.$item.'");?'.'>');
                }
                closedir($dir);
            }
        }

        if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/functions/js'))
        {
            CopyDirFiles($this->GetPath() . "/functions/js/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/");
        }

        return true;
    }
 
    function UnInstallFiles()
    {
        if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/admin')) {
           // DeleteDirFiles($this->GetPath() . '/install/admin/', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/admin');
            if ($dir = opendir($path)) {
                while (false !== $item = readdir($dir)) {
                    if (in_array($item, $this->exclusionAdminFiles))
                        continue;
                    \Bitrix\Main\IO\File::deleteFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/'.$this->MODULE_ID."_".$item);
                }
                closedir($dir);
            }
        }
        if (\Bitrix\Main\IO\Directory::isDirectoryExists($path = $this->GetPath() . '/functions/js'))
        {
            DeleteDirFiles($this->GetPath() . '/functions/js', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/js');
        }

        return true;
    }
} 
?>