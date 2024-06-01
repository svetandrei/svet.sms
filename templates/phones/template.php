<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @param array $arParams
 * @param array $arResult
 * @param CBitrixComponentTemplate $this
 */

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

$APPLICATION->SetAdditionalCSS($templateFolder . '/style.css', true);
$this->addExternalJs($templateFolder . '/script.js');

?>
<div class="ord__content">
    <?php if (count($arResult["GRID"]['PHONES']) <= (int)$arParams['MAX_COUNT_PHONES']) :?>
        <div class="ord__title">Добавить номер телефона</div>
        <div class="ord__tel" id="add-phone-block">
            <input class="phone-mask" type="text" name="phone" id="field-phone" placeholder="Телефон">
            <button class="button button-red" id="add-phone" type="submit">Добавить ещё</button>
        </div>
    <?php endif;?>
    <?php if ($arResult["GRID"]['PHONES']) :?>
    <div class="ord__table ord__table-on">
        <table>
            <thead>
            <tr>
                <?php foreach ($arResult["GRID"]["HEADERS"] as $id => $arHeader) :
                    $arHeaders[] = $arHeader["id"];

                    if ($arHeader["id"] == "DELETE") {
                        $bDeleteColumn = true;
                        continue;
                    }

                    if ($arHeader["id"]) :
                        ?>
                <th>
                        <?php
                    else :
                        ?>
                    <?php endif;
                    if ($arHeader["name"] == '') {
                        $arHeader["name"] = GetMessage("SMS_" . $arHeader["id"]);
                    }
                    echo $arHeader["name"];
                    ?>
                </th>
                    <?php
                endforeach;

                if ($bDeleteColumn) :
                    ?>
                <th></th>
                    <?php
                endif;?>
            </tr>
            </thead>
            <tbody>
            <?php if ($arResult['GRID']['PHONES']) :
                foreach ($arResult['GRID']['PHONES'] as $rKey => $arItem) :?>
                <tr class="item-phone" data-entity="item-phone" data-id="<?=$arItem['ID']?>"
                    data-phone="<?=$arItem['UF_NAME']?>" id="phone-<?=$arItem['ID']?>">
                    <?php foreach ($arResult["GRID"]["HEADERS"] as $hKey => $arHeader) :
                        if (in_array($arHeader["id"], array("DELETE"))) {
                            continue;
                        }
                        if ($arHeader['id'] === 'PHONE') :?>
                            <td><a class="ord__table-tel" href="tel:+<?=$arItem['UF_NAME']?>"><?=$arItem['UF_NAME']?></a></td>
                        <?php endif;?>

                        <?php if ($arHeader['id'] === 'ACTIVATE') :
                            $actClass = ($arItem['UF_ACTIVATE']) ? 'sc' : 'er';?>
                            <td class="confirm-status">
                                <?php if (!$arItem['UF_ACTIVATE']) :?>
                                    <div class="ord__table-form" id="item-confirm-<?=$arItem['ID']?>" style="display:none;">
                                        <div class="tooltip-error error-<?=$arItem['ID']?>" style="display: none"></div>
                                        <input type="text" class="code-phone" placeholder="Код из SMS">
                                        <button class="button button-primary" type="submit"><?=Loc::getMessage('CONFIRM');?></button>
                                        <div class="ord__table-form-tx">
                                            <?=Loc::getMessage('SEND_REPEAT_SMS_CODE', array("#ID#" => $arItem['ID'], "#TIME#" => Loc::getMessage('TIMER')));?></div>
                                        <div class="ord__table-form-sc" data-repeat="<?=$arItem['ID'] + 1?>" style="display: none"><?=Loc::getMessage('SEND_SMS_CODE_REPEAT')?></div><!--div.ord__table-form-sc Прислать код повторно-->
                                    </div>
                                <?php endif;?>
                                <div class="ord__table-status ord__table-status-<?=$actClass?>">
                                    <?=Loc::getMessage('ACTIVATE_' . $arItem['UF_ACTIVATE'])?>

                                    <?=(!$arItem['UF_ACTIVATE']) ? '<div class="send-sms" data-entity="send-sms"><svg><use xlink:href="#mp"></use></svg>
                                    <div class="tooltip-notify-center">' . Loc::getMessage('SEND_SMS_CODE') . '</div></div>' : ''?>
                                </div>
                            </td>
                        <?php endif;?>
                        <?php if (array_key_exists('UF_SMS_' . $arHeader['id'], $arItem)) :?>
                        <td>
                            <div class="ord__table-togl">
                                <label class="toggle">
                                    <input class="toggle-input" data-status="<?=mb_strtolower($arHeader['id'])?>"
                                           data-entity="uf-sms-<?=mb_strtolower($arHeader['id'])?>"
                                           name="<?=mb_strtolower($arHeader['id'])?>"
                                           value="<?=$arItem['UF_SMS_' . $arHeader['id']]?>"
                                       type="checkbox" <?=($arItem['UF_SMS_' . $arHeader['id']]) ? 'checked=""' : ''?>>
                                    <div class="toggle-controller default-success"></div>
                                </label>
                            </div>
                        </td>
                        <?php endif?>
                    <?php endforeach;?>
                    <?php if ($bDeleteColumn) :?>
                        <td>
                            <div class="ord__table-delete"
                                 data-entity="phone-item-delete"><svg><use xlink:href="#delete"></use></svg>
                                <div class="tooltip-notify-center">Удалить</div>
                            </div>
                        </td>
                    <?php endif?>
                </tr>
                <?php endforeach;?>
            <?php endif;?>
            </tbody>
        </table>
    </div>
    <?php endif;?>
</div>
<?php
$signer = new Main\Security\Sign\Signer();
$signedTemplate = $signer->sign($templateName, 'sms.notifications');
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sms.notifications');
$messages = Loc::loadLanguageFile(__FILE__);
?>
<script type="text/javascript">
    BX.message(<?=CUtil::PhpToJSObject($messages)?>);
    BX.SMS.Phones.init({
        result: <?=CUtil::PhpToJSObject($arResult, false, false, true)?>,
        params: <?=CUtil::PhpToJSObject($arParams)?>,
        signedParamsString: '<?=CUtil::JSEscape($signedParams)?>',
        siteTemplateId: '<?=CUtil::JSEscape($component->getSiteTemplateId())?>',
        siteId: '<?=CUtil::JSEscape($component->getSiteId())?>',
        ajaxUrl: '<?=CUtil::JSEscape($component->getPath() . '/ajax.php')?>',
        template: '<?=CUtil::JSEscape($signedTemplate)?>',
        addPhoneBtnId: 'add-phone',
        addPhoneBlockId: 'add-phone-block',
        fieldPhone: 'field-phone'
    });
    $(".phone-mask").mask("+7 (999) 999-9999", { "clearIncomplete": true });
</script>
