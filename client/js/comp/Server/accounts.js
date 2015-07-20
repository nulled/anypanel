function manageServer(host, submitted)
{
    var doPo = function(){ if (! req) { clearInterval(inPo); ajaxGET('index.php?core=headhtml', 0, 0, 0, 'head'); }};
    ajaxGET('index.php?module=Server&method=accounts&submitted=' + submitted + '&host=' + host, 0, 'Server/accounts.js');
    var inPo = doPo.periodical(250);
}

function Main()
{
    if (! mainContentSlide.open)
    {
        setTimeout(Main, 250);
        return;
    }
    else
    {
        new FloatingTips('#tip input', {
            content: 'rel',
            position: 'bottom',
            showOn: 'focus',
            hideOn: 'blur',
            discrete: true,
            hideDelay: 0,
            hideOnTipOutsideClick: true,
            distance: 6
        }).addEvents({
            'show': function(tip, element) {
            },
            'hide': function(tip, element) {
            }
        });

        if ($('authselect'))
        {
            $('authselect').addEvent('click', function(e) {
                e.preventDefault();
                $('authtype').value = $('myForm').getElement('input[name=auth]:checked').value;
                ajaxPOST(this.form, 'index.php?module=Server&method=accounts','Server/accounts.js');
            });
        }

        if ($('authsave'))
        {
            $('authsave').addEvent('click', function(e) {
                e.preventDefault();
                ajaxPOST(this.form, 'index.php?module=Server&method=accounts','Server/accounts.js');
            });

            $('authcancel').addEvent('click', function(e) {
                e.preventDefault();
                ajaxGET('index.php?module=Server&method=accounts',0,'Server/accounts.js');
            });
        }

        if ($('hosts'))
        {
            $('hosts').getElements('input[type=radio]').addEvent('click', function() {
                var host = this.id;
                var submitted = 'select';
                manageServer(host, submitted);
            });
        }
    }
}

function InitJS()
{
    new MultiSelect('.MultiSelect');

    mainContentSlide.slideIn();
    Main();
}

InitJS();