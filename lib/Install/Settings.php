<?php
namespace ITtower\Develop;

use Bitrix\Main\Config\Configuration;

class Settings
{
    protected $config;
    protected $file;

    public function __construct(File $file)
    {
        $this->config = Configuration::getInstance();
        $this->file = $file;
    }



    public function setLog($pathToFile, $logsize = 100000)
    {
        $pathToFile = $this->file->makeDir($pathToFile);

        $arConfig = $this->getExceptionHandlingSettings();

        $arConfig["log"]["settings"] = Array(
            "file" => $pathToFile,
            "log_size" => $logsize
        );
        $this->config->add("exception_handling", $arConfig);
        $this->save();
    }


    protected function getExceptionHandlingSettings(){
        return $this->config->get('exception_handling');
    }

    protected function save()
    {
        $this->config->saveConfiguration();
    }
}