<?php
use \Bitrix\Main\Localization\Loc;
use \ITtower\Develop\Auth;
use \Ittower\Develop\File;

if (!check_bitrix_sessid())
    return;

Loc::loadMessages(__FILE__);
CJSCore::Init(Array('jquery'));
?>
<style>
    .auth {
        display: none;
    }

    .alert {
        color: red;
    }

    .logger {
        display: none;
    }
</style>
<form action="<?echo $APPLICATION->GetCurPage()?>" name="install" enctype="multipart/form-data" method="post">
<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?echo LANGUAGE_ID?>">
	<input type="hidden" name="id" value="ittower.develop">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="step" value="2">

	<p><?echo Loc::getMessage("MOD_INST_SAVE")?></p>
	<p><input type="checkbox" name="savedata" id="savedata" value="Y"><label for="savedata"><?echo Loc::getMessage("MOD_INST_SAVE_DATA")?></label></p>
    <div class="auth">
        <?echo CAdminMessage::ShowMessage(Loc::getMessage("MOD_INST_WARNING_AUTH"))?>
        <input type="text" name="login" id="login"> <label for="login"><?=Loc::getMessage("MOD_INST_LOGIN")?></label><br><br>
        <input type="password" name="password" id="password"> <label for="password"><?=Loc::getMessage("MOD_INST_PASS")?></label><br><br>
        <input type="password" name="password_confirm" id="password_confirm"> <label for="password_confirm"><?=Loc::getMessage("MOD_INST_PASS_CONFIRM")?></label><br><br>

    </div>

    <p><?echo Loc::getMessage("MOD_INST_LOGGER_DESC")?></p>
    <p><input type="checkbox" name="logger" id="logger"  value="Y"><label for="logger"><? echo Loc::getMessage("MOD_INST_LOGGER")?></label></p>
    <div class="logger">
        <?CAdminMessage::ShowNote(Loc::getMessage("MOD_INST_LOG_PATH", Array("#DEFAULT_LOG_FILE_NAME#" => File::DEFAULT_LOG_FILE_NAME)))?>
        <input type="text" placeholder="<? echo Loc::getMessage("MOD_INST_LOGGER_PLACEHOLDER")?>" size="50" name="log_path" id="log_path">
    </div>

    <h2><?echo Loc::getMessage("MOD_INST_FILE_DESC")?></h2>
    <p><?echo Loc::getMessage("MOD_INST_FILE_EXT_PHP")?></p>
    <p><input type="file" name="file_php" id="file_php"></p>

    <p><?echo Loc::getMessage("MOD_INST_FILE_EXT_JS")?></p>
    <p><input type="file" name="file_js" id="file_js"></p>

    <div class="alert"></div><br>
	<input type="submit" name="install" value="<?echo Loc::getMessage("MOD_INST")?>">
</form>
<script>
    var module = {
        "required_fields" : ["password", "password_confirm", "login"],
        "border_color" : "#87919c #959ea9 #9ea7b1 #959ea9",
        "border_color_alert" : "red",
        "empty_fields_message_const" : "FILL_EMPTY_FIELDS",
        "not_match_pass" : "PASSWORDS_NOT_MATCH"
    }

    function checkData(){
        clearErrors();
        let arInputJQ = [];

        if($("input[name='savedata']").prop("checked") === true){
            let name = $("input[name='login']");
            let pass = $("input[name='password']");
            let passConfirm = $("input[name='password_confirm']");
            arInputJQ.push(name);
            arInputJQ.push(pass);
            arInputJQ.push(passConfirm);
        }

        let ids = checkFieldsEmpty(arInputJQ);

        if(ids.length > 0){
            showError("<?=Loc::getMessage("ITTOWER_FILL_EMPTY_FIELDS")?>", ids);
            return false;
        }

        if($("input[name='savedata']").prop("checked") === true){
            return checkPassword();
        }

        return true;
    }

    function clearErrors(){
        $("input").css("border-color", module.border_color);
    }

    function checkFieldsEmpty(arFieldsName){
        let ids = [];

        ids = checkEmpty(module.required_fields, arFieldsName)

        return ids;
    }

    function checkPassword(){
        if($("input[name='savedata']").prop("checked") === true){
            let pass = $("input[name='password']");
            let passConfirm = $("input[name='password_confirm']");

            if(pass.val() !== passConfirm.val()){
                showError("<?=Loc::getMessage("ITTOWER_PASSWORDS_NOT_MATCH")?>", [pass.attr('id'), passConfirm.attr('id')]);
                return false;
            }
        }

        return true;
    }

    function showError(errorText, arInputID = null){
        $(".alert").html(errorText).show();
        if(arInputID.length > 0){
            $(arInputID).each(function(index, el){
                $("#"+el).css("border-color", module.border_color_alert);
            });
        }
    }

    function checkEmpty(reqFields, arFieldsJQ){
        let ids = [];
        $(reqFields).each(function(indexR, elR){
            $(arFieldsJQ).each(function(indF, elF){
                if(elF.attr('name') == elR && elF.val() == ""){
                    ids.push(elF.attr('id'));
                }
            });
        });

        return ids;
    }

    function clearError()
    {
        $(".alert").hide();
    }

    $('document').ready(function(){
        $("input[name='savedata']").on("click", function(){
            if($("input[name='savedata']").prop("checked") === true){
                $(".auth").show();
            } else{
                $(".auth").hide();
                $(".alert").hide();
            }
        });

        $("input[name='logger']").on("click", function(){
            if($("input[name='logger']").prop("checked") === true){
                $(".logger").show();
            } else{
                $(".logger").hide();
            }
        });



        $("input[name='install']").on("click", function(e){
            e.preventDefault();
            if(checkData()){
                //alert("OK");
                $("form[name='install']").submit();
            }
        });

        $("input").on("focus", function(){
            $(this).css("border-color", module.border_color);
        });
    });
</script>