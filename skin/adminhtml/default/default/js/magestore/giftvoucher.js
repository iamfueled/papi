/*Giftvoucher JS*/

// Show edit amount for existed Gift Card
function showGiftCardAmountInput(el) {
    var parent = Element.extend(el.parentNode);
    el.hide();
    parent.down('input').show();
    parent.down('input').disabled = false;
}

// Remove Gift Card from quote
function removeGiftVoucher(url){
	new Ajax.Request(url, {
		method:'post',
		postBody: '',
		onException: '',
		onComplete: function (response){
			if (response.responseText.isJSON()){
                if (order) {
                    order.loadArea(['items', 'shipping_method', 'totals', 'billing_method'], true, {reset_shipping: true});
                }
			}
		}
	});
}

// Change use a existed or another Gift Card
function useExistedGiftcard(el) {
    if (el.value) {
        $('giftvoucher-custom-code').hide();
    } else {
        $('giftvoucher-custom-code').show();
    }
}

// Apply Gift Card Form
function applyGiftCardForm(url) {
    var elements = $('giftvoucher_container').select('input', 'select', 'textarea');
    elements.push($$('[name="form_key"]')[0]);
    var params = Form.serializeElements(elements);
    new Ajax.Request(url, {
        method:'post',
        postBody: params,
        parameters: params,
        onException: '',
        onComplete: function (response) {
            if (response.responseText.isJSON()) {
                if (order) {
                    order.loadArea(['items', 'shipping_method', 'totals', 'billing_method'], true, {reset_shipping: true});
                }
            }
        }
    });
}

function showCartCreditInput(el) {
    var parent = Element.extend(el.parentNode.parentNode);
    if (el.checked) {
        parent.down('dd.giftvoucher_credit').show();
    } else {
        parent.down('dd.giftvoucher_credit').hide();
    }
}

function showCartGiftCardInput(el) {
    var parent = Element.extend(el.parentNode.parentNode);
    if (el.checked) {
        parent.down('dd.giftvoucher').show();
    } else {
        parent.down('dd.giftvoucher').hide();
    }
}
