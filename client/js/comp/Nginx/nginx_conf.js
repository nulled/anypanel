function Main()
{
    if (! mainContentSlide.open)
    {
        setTimeout(Main, 250);
        return;
    }
    else
    {
        jsm.text2input();
        jsm.input2text(selects);
        jsm.form2string('Nginx', 'nginx_conf', 1, 1); // module, method, js=true, css=true
        jsm.dragndrop(selects, 'nginx_conf', 'nginx.conf'); // select objects, classname, mode
        jsm.removeParam();
    }
}

function InitJS()
{
    mainContentSlide.slideIn();
    Main();
}

InitJS();