<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Highloadblock as HL;
use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main;
use SMSCenter\SMS;

Loc::loadMessages(__FILE__);

class SMSNotifications extends CBitrixComponent
{
    private $initSMS;
    private $userID = null;
    private $rand = [];
    protected $action;
    protected $listPhones = [];
    protected $isRequestViaAjax;
    protected $context;
    public $session;
    protected $checkSession = true;
    protected $hlID;
    public $columns = [];
    public $errors = [];

    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->userID = Bitrix\Main\Engine\CurrentUser::get()->getId();
        if ($this->userID === null) {
            ShowError(Loc::getMessage('SMS_ACCESS_DENIED'));
        }
        $this->initSMS = new SMS(SMSC_LOGIN, SMSC_PASSWORD, true, [
            'charset' => SMS::CHARSET_UTF8,
            'fmt' => SMS::FMT_JSON
        ]);
        $this->session = \Bitrix\Main\Application::getInstance()->getSession();
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     */
    protected function includeModules(): bool
    {
        $success = true;

        if (!Loader::includeModule('highloadblock')) {
            $success = false;
            ShowError(Loc::getMessage('HIGHLOAD_MODULE_NOT_INSTALL'));
        }

        return $success;
    }

    protected function entityDataClass()
    {
        $arHLBlock = HL\HighloadBlockTable::getById($this->arParams["HIGHLOAD_BLOCK_ID"])->fetch();
        return HL\HighloadBlockTable::compileEntity($arHLBlock)->getDataClass();
    }

    public function onPrepareComponentParams($arParams): array
    {
        global $APPLICATION;

        if (isset($arParams['SET_TITLE']) && $arParams['SET_TITLE'] === 'Y') {
            $APPLICATION->SetTitle(Loc::getMessage('SMS_TITLE'));
        }

        $arParams['AJAX_PATH'] = !empty($arParams['AJAX_PATH'])
            ? trim((string)$arParams['AJAX_PATH']) : $this->getPath() . '/ajax.php';

        // default columns
        if (!isset($arParams['COLUMNS_LIST_EXT']) || !is_array($arParams['COLUMNS_LIST_EXT'])) {
            if (!empty($arParams['COLUMNS_LIST'])) {
                $arParams['COLUMNS_LIST_EXT'] = $arParams['COLUMNS_LIST'];

                // compatibility
                if (!in_array('PHONE', $arParams['COLUMNS_LIST_EXT'])) {
                    $arParams['COLUMNS_LIST_EXT'][] = 'PHONE';
                }
            } else {
                $arParams['COLUMNS_LIST_EXT'] = [
                    'PHONE', 'ACTIVATE', 'STATUS', 'STOCK', 'NOVELTIES', 'AVAILABILITY', 'DELETE'
                ];
            }
        }

        if (!in_array('PHONE', $arParams['COLUMNS_LIST_EXT'], true)) {
            $arParams['COLUMNS_LIST_EXT'] = array_merge(['PHONE'], $arParams['COLUMNS_LIST_EXT']);
        }

        if (!in_array('DELETE', $arParams['COLUMNS_LIST_EXT'], true)) {
            $arParams['COLUMNS_LIST_EXT'][] = 'DELETE';
        }

        $arParams['COLUMNS_LIST'] = $arParams['COLUMNS_LIST_EXT'];
        $this->columns = $arParams['COLUMNS_LIST'];
        $this->hlID = $arParams["HIGHLOAD_BLOCK_ID"];

        return $arParams;
    }

    protected function getGridColumns(): array
    {
        $headers = [];

        if (!empty($this->columns) && is_array($this->columns)) {
            foreach ($this->columns as $value) {
                $name = '';

                if (strncmp($value, 'PROPERTY_', 9) === 0) {
                    $propCode = mb_substr($value, 9);

                    if ($propCode === '') {
                        continue;
                    }

                    $id = $value . '_VALUE';
                    $name = $value;

                    if (isset($this->storage['PROPERTY_CODES'][$propCode])) {
                        $name = $this->storage['PROPERTY_CODES'][$propCode]['NAME'];
                    }
                } else {
                    $id = $value;
                }

                $headers[] = [
                    'id' => $id,
                    'name' => $name,
                ];
            }
        }

        return $headers;
    }

    protected function deletePhoneAction(): void
    {
        if (!$this->request->get('id')) {
            return;
        }

        $result = $this->entityDataClass()::delete($this->request->get('id'));
        if (!$result->isSuccess()) {
            ShowError($result->getErrorMessages());
        }
    }

    protected function addPhoneAction()
    {
        if (!$this->request->get('phone')) {
            return;
        }

        $arData = array(
            'UF_NAME' => $this->request->get('phone'),
            'UF_USER_ID' => (int)$this->userID,
        );

        $result = $this->entityDataClass()::add($arData);
        if (!$result->isSuccess()) {
            ShowError($result->getErrorMessages());
        }
    }

    protected function statusNotificationsAction()
    {
        if (
            !empty(($this->request->get('status') && $this->request->get('value')
            && $this->request->get('id')))
        ) {
            return;
        }

        $arData = array(
            'UF_SMS_' . strtoupper($this->request->get('status')) => $this->request->get('checked'),
            'UF_USER_ID' => (int)$this->userID,
        );

        $result = $this->entityDataClass()::update((int)$this->request->get('id'), $arData);
        if (!$result->isSuccess()) {
            ShowError($result->getErrorMessages());
        }
    }

    protected function sendSMSCodeAction()
    {
        if (!$this->request->get('id') || !$this->request->get('phone')) {
            return;
        }
        $result = [];

        $smsc = $this->initSMS;
        $status = \Bitrix\Main\Web\Json::decode($smsc->send(
            '+37379275203',
            "Ваш код подтверждения: " . $this->randCode($this->request->get('phone')),
            'Atom'
        )); //1 parameter - $this->request->get('phone') номер получателя
        $rsStatus = \Bitrix\Main\Web\Json::decode($smsc->getStatus('+37379275203', $status['id'], SMS::STATUS_INFO_EXT));
		$result['status'] = $rsStatus;
        if ($rsStatus['status'] === 0 || $rsStatus['status'] === 1 || $rsStatus['status'] === -1) {
            $result['success'] = true;
        } else {
            $result['error'] = 'ERROR_SEND';
        }
        $result['id'] = (int)$this->request->get('id');
        $result['status']['send'] = true;

        self::sendJsonAnswer($result);
    }

    /**
     * @throws Exception
     */
    private function randCode($phone): int
    {
        $code[$this->userID]['CODE_' . (int)$phone] = random_int(0, 10000);
        $this->session->set('CODE', $code);
        return $code[$this->userID]['CODE_' . (int)$phone];
    }

    protected function confirmSMSCodeAction()
    {
        if (!$this->request->get('id') || !$this->request->get('phone') || !$this->request->get('code')) {
            return;
        }
        $result = [];
        if ($this->session->get('CODE')[$this->userID]['CODE_' . (int)$this->request->get('phone')]) {
            if ((int)$this->session->get('CODE')[$this->userID]['CODE_' . (int)$this->request->get('phone')] === (int)$this->request->get('code')) {
                $arData = array(
                    'UF_ACTIVATE' => true,
                    'UF_USER_ID' => (int)$this->userID,
                );
                $res = $this->entityDataClass()::update((int)$this->request->get('id'), $arData);
                if ($res->isSuccess()) {
                    unset($this->session->get('CODE')[$this->userID]['CODE_' . (int)$this->request->get('phone')]);
                    $result['success'] = true;
                } else {
                    $result['success'] = false;
                    $result['error'] = 'ERROR_ACTIVATE';
                }
            } else {
                $result['success'] = false;
                $result['error'] = 'ERROR_CODE';
            }
        } else {
            $result['success'] = false;
            $result['error'] = 'ERROR_SESSION';
        }
        $result['id'] = (int)$this->request->get('id');
        $result['status']['confirm'] = true;

        self::sendJsonAnswer($result);
    }

    public static function sendJsonAnswer($result): void
    {
        global $APPLICATION;

        $APPLICATION->RestartBuffer();
        header('Content-Type: application/json');

        echo \Bitrix\Main\Web\Json::encode($result);

        CMain::FinalActions();
        die();
    }

    protected function getPhones(): array
    {
        if ($this->arParams['HIGHLOAD_BLOCK_ID']) {
            $arHL = $this->entityDataClass()::getList(array(
                "filter" => array("UF_USER_ID" => (int)$this->userID),
                "order" => array("ID" => "DESC"),
                "select" => array("*"),
            ))->fetchAll();
            if (!$arHL) {
                ShowError("Записей нет!");
            }
        }
        return $arHL;
    }

    protected function actionExists($action)
    {
        return is_callable([$this, $action . 'Action']);
    }

    protected function doAction($action): void
    {
        if ($this->actionExists($action)) {
            $this->{$action . 'Action'}();
        }
    }

    protected function prepareAction(): string
    {
        $action = $this->request->get('action');

//        if (!$this->arParams['ALLOW_AUTO_REGISTER'] === 'N' && $action !== 'confirmSmsCode') {
//            $action = 'showAuthForm';
//        }

        if (empty($action) || !$this->actionExists($action)) {
            $action = '';
        }

        return $action;
    }

    public function executeComponent()
    {
        global $APPLICATION;

        $this->setFrameMode(false);
        $this->context = Main\Application::getInstance()->getContext();
        $this->checkSession = check_bitrix_sessid();
        $this->isRequestViaAjax = $this->request->isPost() && $this->request->get('via_ajax') == 'Y';
        print_r($this->isRequestViaAjax);
        if ($this->isRequestViaAjax) {
            $APPLICATION->RestartBuffer();
        }

        $this->action = $this->prepareAction();
        $this->doAction($this->action);

        if ($this->includeModules()) {
            $this->arResult['GRID']['HEADERS'] = $this->getGridColumns();
            $this->arResult['GRID']['PHONES'] = $this->getPhones();
        }

        $this->includeComponentTemplate();

        if ($this->isRequestViaAjax) {
            $APPLICATION->FinalActions();
            die();
        }
    }
}
