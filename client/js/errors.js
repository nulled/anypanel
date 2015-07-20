function getParam(name)
{
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regexS = "[\\?&]"+name+"=([^&#]*)";
  var regex = new RegExp( regexS );
  var results = regex.exec(window.location.href);
  if(results == null)
    return '';
  else
    return results[1];
}

window.addEvent('domready', function() {

    var errorCodes = {'100':'0',
                      '101':'1',
                      '200':'2',
                      '201':'3'
                      };

    var mainMenu = new Accordion($('menu'), 'h3.toggler', 'div.element', {
        alwaysHide: true,
        show: -1
    });

    var param = getParam('errnum');

    if (param) mainMenu.display(errorCodes[param]);
});