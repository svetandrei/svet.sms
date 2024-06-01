BX.namespace('BX.SMS.Phones');

BX.SMS.Phones = {

    init: function (parameters) {
        this.firstLoad = true;
        this.params = parameters.params || {};
        this.template = parameters.template || '';
        this.signedParamsString = parameters.signedParamsString || '';
        this.siteId = parameters.siteId || '';
        this.siteTemplateId = parameters.siteTemplateId || '';
        this.ajaxUrl = this.params.AJAX_PATH || '';
        this.templateFolder = parameters.templateFolder || '';
        this.result = parameters.result;
        this.btnPhoneNode = BX(parameters.addPhoneBtnId);
        this.addPhoneBlockId = BX(parameters.addPhoneBlockId);
        this.fieldPhone = BX(parameters.fieldPhone);
        this.time = BX.message('TIMER');

        this.initializePhonesItems();

        if (this.btnPhoneNode) {
            BX.bind(this.btnPhoneNode, 'click', BX.proxy(this.addPhone, this));
        }

        if (this.fieldPhone) {
            BX.bind(this.fieldPhone, 'keyup', BX.delegate(function(e){this.validField(e);}, this));
        }
    },

    validField: function (event) {
        var input = (event.target || event.srcElement) ? event.target || event.srcElement : event,
            error = null;

        if (!input) return;

        if (!input.value) {
            BX.addClass(input, 'error-input');
            error = true;
        } else {
            BX.removeClass(input, 'error-input');
            error = false;
        }
        return error;
    },
    initializePhonesItems: function () {
        var i, itemNode;

        if (!this.result.GRID.PHONES)
            return;

        for (i = 0; i < this.result.GRID.PHONES.length; i++) {
            itemNode = BX('phone-' + this.result.GRID.PHONES[i].ID);
            if (BX.type.isDomNode(itemNode)) {
                this.bindActionEvents(itemNode, this.result.GRID.PHONES[i]);
            }
        }
    },

    isNotActivate: function (item) {
        if (!item) return;
        return !parseInt(item.UF_ACTIVATE);
    },

    bindActionEvents: function(node, data) {
        if (!node || !data)
            return;

        var entity, st, i;

        for (i = 0; i < this.params.COLUMNS_LIST.length; i++) {
            status = this.params.COLUMNS_LIST[i].toLowerCase();
            entity = this.getEntity(node, 'uf-sms-' + status);
            if (entity !== null) {
                BX.bind(entity, 'change', BX.proxy(this.statusPhone, this));
            }
        }
        if (BX.util.in_array('DELETE', this.params.COLUMNS_LIST))
        {
            entity = this.getEntity(node, 'phone-item-delete');
            BX.bind(entity, 'click', BX.proxy(this.deletePhone, this));
        }

        if (this.isNotActivate(data)) {
            entity = this.getEntity(node, 'send-sms');
            BX.bind(entity, 'click', BX.delegate(function (){
                this.showConfirmBlock();
                var repeat = (parseInt(this.getItemDataByTarget(BX.proxy_context)) + 1);
                this.countDown(this.getItemDataByTarget(BX.proxy_context), this.time,
                    repeat);
            }, this));
        }
    },
    getEntity: function(parent, entity, additionalFilter)
    {
        if (!parent || !entity)
            return null;

        additionalFilter = additionalFilter || '';

        return parent.querySelector(additionalFilter + '[data-entity="' + entity + '"]');
    },
    statusPhone: function () {
        var data = {};

        var itemStatus = BX.proxy_context;

        data['status'] = itemStatus.name;
        data['checked'] = (!!itemStatus.checked) ? itemStatus.checked : '';
        data['id'] = parseInt(this.getItemDataByTarget(BX.proxy_context));
        this.sendRequest('statusNotifications', data);
    },
    addPhone: function () {
        var data = {};
        if (this.result.GRID.PHONES.length >= this.params.MAX_COUNT_PHONES) return;

        if (!!this.validField(this.fieldPhone)) return;

        data[this.fieldPhone.name] = this.fieldPhone.value;

        this.sendRequest('addPhone', data);
    },
    deletePhone: function () {
        var data = {};

        if (this.getItemDataByTarget(BX.proxy_context) <= 0) return;

        data['id'] = parseInt(this.getItemDataByTarget(BX.proxy_context));

        this.sendRequest('deletePhone', data);
    },
    sendSMSCode: function (phoneID) {
        var data = {}, phone, id, sendBtn;
        id = (phoneID) ? phoneID : parseInt(this.getItemDataByTarget(BX.proxy_context));

        phone = BX.data(BX('phone-' + id), 'phone');

        data['id'] = id;
        data['type'] = 'json';
        data['phone'] = phone.replace(/[^+\d;]/g, '');

        this.sendRequest('sendSMSCode', data);
    },
    countDown: function (id, time, repeat, cancel = false) {
        var repeatBtn = BX.findChild(BX('item-confirm-' + id),
            {tag: 'div', className: 'ord__table-form-sc' }
        ), newRepeat = BX.data(repeatBtn, 'repeat'),
            timer = repeat + newRepeat;
        if (cancel) {
            BX('timer-' + id).innerHTML = 0;
            repeatBtn.style.display = 'block';
        }
        timer = setInterval(function() {
            if(time === -1) {
                repeatBtn.style.display = 'block';
                BX.data(repeatBtn, 'repeat', repeat + 1);
                clearInterval(timer);
            } else {
                repeatBtn.style.display = 'none';
                BX('timer-' + id).innerHTML = time--;
            }
        }, 1000);
    },
    showConfirmBlock: function () {
        if (!BX.proxy_context) return;
        var itemConfirm = BX('item-confirm-' + this.getItemDataByTarget(BX.proxy_context));

        this.sendSMSCode(this.getItemDataByTarget(BX.proxy_context));

        this.reapeatBtn = BX.findChild(itemConfirm,
            {tag: 'div', className: 'ord__table-form-sc' }
        );
        var repeat = (parseInt(this.getItemDataByTarget(BX.proxy_context)) + 1);
        BX.bind(this.reapeatBtn, 'click', BX.delegate(function (){
            this.sendSMSCode(this.getItemDataByTarget(BX.proxy_context));
            repeat++;
            this.countDown(this.getItemDataByTarget(BX.proxy_context),
                this.time, repeat);
        }, this));

        var btnConfirm = BX.findChild(itemConfirm, {
                tag: 'button',
            }
        );
        BX.bind(btnConfirm, 'click', BX.delegate(function (){
            this.confirmSMSCode();
        }, this));

        BX.proxy_context.parentElement.style.display = 'none';
        itemConfirm.style.display = 'block';

    },

    confirmSMSCode: function () {
        var data = {}, id = this.getItemDataByTarget(BX.proxy_context);
        var itemConfirm = BX('item-confirm-' + id);
        var time = parseInt(BX('timer-' + id).textContent);
        var code = BX.findChild(itemConfirm, {
            tag: 'input', attribute: {type:'text'}
        });
        if (!!this.validField(code)) return;
        if (!this.validField(code) && time > 0) {
            data['id'] = id;
            data['phone'] = BX.data(BX('phone-' + id), 'phone').replace(/[^+\d;]/g, '');
            data['type'] = 'json';
            data['code'] = code.value.trim();
            this.sendRequest('confirmSMSCode', data);
        }
    },
    showResult: function (result) {
        if (result.error) {
            this.showError(result.id, this.mainErrorsNode, result.error);
            if (result.status.send) {
                this.countDown(result.id, 0, parseInt(result.id + 1), true);
            }
        } else {
            if (result.status.confirm && result.success) {
                BX('item-confirm-' + result.id).style.display = 'none';
                var statusNode = BX.create('DIV', {
                    props: {className: 'ord__table-status ord__table-status-sc'},
                    text: BX.message('ACTIVATE_1')
                });
                var phoneNode = BX.findChild(BX('phone-' + result.id), {
                    tag: 'td', className: 'confirm-status'
                });
                phoneNode.append(statusNode);
                this.countDown(result.id, 0, 0, true);
            }
        }
    },
    getItemDataByTarget: function(target)
    {
        var id, itemNode = BX.findParent(target, {attrs: {'data-entity': 'item-phone'}});
        if (itemNode)
        {
            id = itemNode.getAttribute('data-id');
        }
        return id;
    },
    getData: function(data)
    {
        data = data || {};

        data.via_ajax = 'Y';
        data.site_id = this.siteId;
        data.site_template_id = this.siteTemplateId;
        data.sessid = BX.bitrix_sessid();
        data.template = this.template;
        data.signedParamsString = this.signedParamsString;

        return data;
    },
    sendRequest: function(action, data)
    {
        if (!this.startLoader())
            return;

        data['action'] = action;

        var type = (data['type'] === 'json') ? data['type']: data['type'] = 'html';

        var eventArgs = {
            action: action,
            actionData: data
        };

        BX.ajax({
            method: 'POST',
            dataType: type,
            url: this.ajaxUrl,
            data: this.getData(data),
            onsuccess: BX.delegate(function(result) {
                var ob = BX.processHTML(result);
                if (eventArgs.actionData['type'] === 'html') {
                    BX.remove(document.querySelector('div.ord__right .ord__content'));
                    document.querySelector('div.ord__right .rek').insertAdjacentHTML('afterEnd', ob.HTML)
                    BX.ajax.processScripts(ob.SCRIPT);
                    return;
                }
                else {
                    switch (eventArgs.action) {
                        case 'confirmSMSCode':
                            this.showResult(result);
                            break;
                        case 'sendSMSCode':
                            this.showResult(result);
                            break;
                        //default:
                    }
                }
            }, this),
            onfailure: BX.delegate(function(error) {
                console.log(error);
            }, this)
        });
        this.endLoader();
    },
    startLoader: function()
    {
        if (!this.loadingScreen) {
            this.loadingScreen = new BX.PopupWindow('loading_screen', null, {
                overlay: {backgroundColor: 'white', opacity: 1},
                events: {
                    onAfterPopupShow: BX.delegate(function(){
                        BX.cleanNode(this.loadingScreen.popupContainer);
                        BX.removeClass(this.loadingScreen.popupContainer, 'popup-window');
                        this.loadingScreen.popupContainer.removeAttribute('style');
                        this.loadingScreen.popupContainer.style.display = 'block';
                    }, this)
                }
            });
        }

        this.loadingScreen.overlay.element.style.opacity = '0';
        this.loadingScreen.show();
        this.loadingScreen.overlay.element.style.opacity = '0.6';

        return true;
    },

    endLoader: function()
    {
        if (this.loadingScreen && this.loadingScreen.isShown()) {
            this.loadingScreen.close();
        }
    },
}

