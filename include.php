<?php
use \Bitrix\Main\Application;
include_once(__DIR__."/functions/script.php");

CJSCore::RegisterExt("dev_js_lib", array(
    "js" => "/bitrix/js/ittower.develop_script.js", ///
));

CJSCore::Init(array("dev_js_lib"));