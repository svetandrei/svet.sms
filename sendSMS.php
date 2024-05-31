<?php

namespace Plastilin;

use Bitrix\Highloadblock as HL;
use SMSCenter\SMS;

$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandler(
    'sale',
    'OnSaleStatusOrderChange',
    '\Plastilin\SmsEvent::statusChangeOrder'
);

$eventManager->addEventHandler(
    'catalog',
    'OnBeforeProductUpdate',
    '\Plastilin\SmsEvent::statusChangeGood'
);

class SmsEvent
{
    private static $oldData;

    public function __construct()
    {
        self::$initSMS = new SMS(SMSC_LOGIN, SMSC_PASSWORD, true, [
            'charset' => SMS::CHARSET_UTF8,
            'fmt' => SMS::FMT_JSON
        ]);
    }

    public static function statusChangeOrder(\Bitrix\Main\Event $event)
    {
        $order = $event->getParameter("ENTITY");

        if (
            !in_array($order->getField('STATUS_ID'), array(
            'N',
            'P',
            'SZ',
            'F',
            ))
        ) {
            return;
        }

        $arHLBlock = HL\HighloadBlockTable::getById(2)->fetch();
        $HL = HL\HighloadBlockTable::compileEntity($arHLBlock)->getDataClass();

        $rsPhone = $HL::getList(array(
            "filter" => array("UF_USER_ID" => (int)$order->getUserId(), 'UF_ACTIVATE' => true, 'UF_SMS_STATUS' => true),
            "order" => array("ID" => "DESC"),
            "select" => array("*"),
        ));
        $phones = [];
        while ($arPhone = $rsPhone->fetch()) {
            $phones = '+' . (int)$arPhone['UF_NAME']; //. (int)$arPhone['UF_NAME'];
        }

        $smsc = new SMS(SMSC_LOGIN, SMSC_PASSWORD, true, [
            'charset' => SMS::CHARSET_UTF8,
            'fmt' => SMS::FMT_JSON
        ]);

        $status = \Bitrix\Main\Web\Json::decode($smsc->send(
            '+37379275203', //$phones
            "Ваш заказ: " . $order->getField('ACCOUNT_NUMBER')
            . '\r\n Статус заказа - ' . $order->getField('STATUS_ID')
            . '\r\n Дата изменения - ' . $order->getField('DATE_STATUS')->format('Y.m.d'),
            'Atom'
        ));
        \Bitrix\Main\Diag\Debug::writeToFile(array($status), "", "sms_order_log.txt");
    }
    public static function dataBeforeUpdate($id, $arFields)
    {
        self::$oldData['ID'] = $id;
        self::$oldData['FIELDS'] = $arFields;
    }
    public static function statusChangeGood($id, $arFields)
    {
        if (
            self::$oldData['ID'] ===  $id && self::$oldData['FIELDS']['AVAILABLE'] === 'N'
            && $arFields['AVAILABLE'] === 'Y'
        ) {
            $smsc = new SMS(SMSC_LOGIN, SMSC_PASSWORD, true, [
                'charset' => SMS::CHARSET_UTF8,
                'fmt' => SMS::FMT_JSON
            ]);

            $arHLBlock = HL\HighloadBlockTable::getById(2)->fetch();
            $HL = HL\HighloadBlockTable::compileEntity($arHLBlock)->getDataClass();

            $rsPhone = $HL::getList(array(
                "filter" => array("UF_USER_ID" => (int)$order->getUserId(), 'UF_ACTIVATE' => true, 'UF_SMS_STATUS' => true),
                "order" => array("ID" => "DESC"),
                "select" => array("*"),
            ));

            $status = \Bitrix\Main\Web\Json::decode($smsc->send(
                '+37379275203', //$phones
                "Товар: " . $arFields['NAME'] . ' Доступен',
                'Atom'
            ));
            \Bitrix\Main\Diag\Debug::writeToFile(array($status), "", "sms_product_log.txt");
        }
    }
}
