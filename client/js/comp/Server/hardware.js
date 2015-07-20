function Main()
{
    // do not start code execution until the slide Fx is finished
    // retry ever 250 milliseconds
    if (! mainContentSlide.open)
    {
        setTimeout(Main, 250);
        return;
    }
    else
    {
        // main body of code here
    }
}

function InitJS()
{
    new Fx.Accordion($('accordion'), '#accordion h2', '#accordion #content', {
        duration: 'short',
        alwaysHide: true,
        show: -1
    });

    mainContentSlide.slideIn();
    Main();
}

// javascript will execute as the page is downloaded
// so we use the function below to execute, which happens
// do be the last line in the javascript file...
InitJS();