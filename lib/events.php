<?php
namespace Ittower\Develop;
use Bitrix\Main\Loader;
class Events
{
    public function register()
    {
        if (!Loader::includeModule('ittower.develop'))
        {
            $message = Loc::getMessage('ITTOWER_DEVELOP_NEED_MODULE_INSTALLED');
            throw new \Main\SystemException($message);
        }
    }
}