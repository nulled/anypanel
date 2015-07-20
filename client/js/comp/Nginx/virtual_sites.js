function Main()
{
    if (! mainContentSlide.open)
    {
        setTimeout(Main, 250);
        return;
    }
    else
    {
        $$('.site_enabled, .site_avail').addEvent('click', function(e) {
            var file = this.get('text');
            //console.log(file);
            ajaxGET('index.php?module=Nginx&method=virtual_sites&submitted=load&file=' + file, 0, 'Nginx/virtual_sites.js','Nginx/virtual_sites.css');
        });

        $$('#disabled, #enabled').addEvent('click', function(e) {
            var id   = this.get('id');
            var file = this.get('name');
            //console.log(id + ' ' + file);
            if (id == 'disabled')
            {
                this.set('id', 'enabled');
                this.set('text', 'Disable Site');
                ajaxGET('index.php?module=Nginx&method=virtual_sites&submitted=enable&file=' + file, 0, 'Nginx/virtual_sites.js','Nginx/virtual_sites.css', 'null');
            }
            else if (id == 'enabled')
            {
                this.set('id', 'disabled');
                this.set('text', 'Enable Site');
                ajaxGET('index.php?module=Nginx&method=virtual_sites&submitted=disable&file=' + file, 0, 'Nginx/virtual_sites.js','Nginx/virtual_sites.css', 'null');
            }
        });

        jsm.text2input();
        jsm.input2text(selects);
        jsm.form2string('Nginx', 'virtual_sites', 1, 1); // module, method, js=true, css=true
        jsm.dragndrop(selects, 'nginx_conf', 'nginx.sites'); // select objects, classname, mode
        jsm.removeParam();
    }
}

function InitJS()
{
    mainContentSlide.slideIn();
    Main();
}

InitJS();