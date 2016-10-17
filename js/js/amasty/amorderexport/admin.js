/**
* Handles select changing.
*/
OrderExport = 
{
    csvConfigLabel : "", // "CSV Configuration" translation
    fieldCnt       : 0, // count for mapped fields
    dbString       : "", // "Field in Database" translation
    exString       : "", // "Field in Export" translation
    hlString       : "", // "Handler" translation
    fieldSel       : "", // Fields select HTML
    handlerOptions : null,
};


function checkMapping(select)
{
    if (0 == select.value) // export specified fields only
    {
        $('add_field_mapping').style.display = 'block';
        $$('tr.mappingrow').each(function(row) {
            row.show();
        });
    } else 
    {
        $('add_field_mapping').style.display = 'none';
        $$('tr.mappingrow').each(function(row) {
            row.hide();
        });
    }
}

function addFieldMapping()
{
    tbl  = $$('#mapping_fieldset table tbody')[0];
    row  = tbl.appendChild(document.createElement('tr'));
    row.addClassName('mappingrow');
    tdDb = row.appendChild(document.createElement('td'));
    tdEx = row.appendChild(document.createElement('td'));
    tdEx.addClassName('value');
    tdEx.style.maxWidth = '800px';
    tdEx.style.minWidth = '600px';
    tdEx.style.width    = '780px';
    
    select = OrderExport.fieldSel.replace('FIELDS_SELECT_NAME', 'fielddb[' + OrderExport.fieldCnt + ']').replace('FIELDS_SELECT_ID', 'fielddb_' + OrderExport.fieldCnt);
    tdEx.innerHTML = OrderExport.dbString + "&nbsp;" + select + "&nbsp;&nbsp;&nbsp;" + 
                            OrderExport.exString + "&nbsp;" + 
                               '<input name="fieldex[' + OrderExport.fieldCnt + ']" class="input-text" type="text" id="fieldex_' + OrderExport.fieldCnt + '" style="width: 155px;" />' +
                                   "&nbsp;&nbsp;&nbsp;" + 
                                   OrderExport.orString + "&nbsp;" + 
                                  '<input name="fieldorder[' + OrderExport.fieldCnt + ']" class="input-text" type="text" id="fieldorder_' + OrderExport.fieldCnt + '" style="width: 30px;" />'
    
    OrderExport.fieldCnt++;
}

function onSelectMapping(select, selectedHandler)
{
    field = $(select.id.replace('fielddb', 'fieldex'));
    fieldName = select.value.replace('.', '_');
    field.value = fieldName;
    
    var fieldCnt = select.id.replace(/[a-z_]*/, '');
    
    // check if we should add handlers
    if (OrderExport.handlerOptions[select.value])
    {
        span = document.createElement('span');
        span.addClassName('handlers');
        handlersSelect = document.createElement('select');
        handlersSelect.addClassName('select');
        handlersSelect.style.width = '120px';
        handlersSelect.name = "handler[" + fieldCnt + "]";
        span.appendChild(handlersSelect);
        Object.keys(OrderExport.handlerOptions[select.value]).each(function(key){
            var option = document.createElement('option');
            option.value = key;
            option.text  = OrderExport.handlerOptions[select.value][key];
            option.label = OrderExport.handlerOptions[select.value][key];
            if (selectedHandler && selectedHandler == key)
            {
                option.selected = true;
            }
            handlersSelect.appendChild(option);
        });
        descSpan = document.createElement('span');
        descSpan.addClassName('handlers');
        descSpan.innerHTML = "&nbsp;&nbsp;&nbsp;" + OrderExport.hlString;
        select.parentNode.appendChild(descSpan);
        select.parentNode.appendChild(span);
    } else 
    {
        select.parentNode.select('span.handlers').each(function(span){
            select.parentNode.removeChild(span);
        });
    }
}

function initMappingOnLoad(event)
{
    checkMapping($('export_allfields'));
}

function profileCheckFormat()
{
    if ('csv' == $('format').value)
    {
        $('csv_fieldset').show();
        $$('h4.fieldset-legend').each(function(header) {
            if (OrderExport.csvConfigLabel == header.innerHTML)
            {
                header.parentNode.show();
            }
        });
    } else 
    {
        $('csv_fieldset').hide();
        $$('h4.fieldset-legend').each(function(header) {
            if (OrderExport.csvConfigLabel == header.innerHTML)
            {
                header.parentNode.hide();
            }
        });
    }
}

function profileCheckFtp()
{
    if (1 == $('ftp_use').value) // yes
    {
        $$('#ftp_fieldset tr').each(function(row, i) {
            if (0 != i) // hide all except first (the question itself)
            {
                row.show();
            }
        });
    } else // no
    {
        $$('#ftp_fieldset tr').each(function(row, i) {
            if (0 != i)
            {
                row.hide();
            }
        });
    }
}

function profileCheckEmail()
{
    if (1 == $('email_use').value) // yes
    {
        $$('#email_fieldset tr').each(function(row, i) {
            if (0 != i) // hide all except first (the question itself)
            {
                row.show();
            }
        });
    } else // no
    {
        $$('#email_fieldset tr').each(function(row, i) {
            if (0 != i)
            {
                row.hide();
            }
        });
    }
}

Event.observe(window, 'load', profileCheckFormat);
Event.observe(window, 'load', profileCheckFtp);
Event.observe(window, 'load', profileCheckEmail);