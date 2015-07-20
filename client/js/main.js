var jsm = {
    DEBUG: 4,
    current_element: null,
    comment_click: function(e, _this) {
    	if (jsm.DEBUG == 1) console.log('text2input:' + ' comment');
        if (e) e.stop();
        if (jsm.DEBUG == 1) console.log('click: ' + jsm.current_element);
        if (_this.getChildren('input')[0] === undefined)
        {
            var txt = _this.get('text');
            _this.empty();
            _this.adopt(Element('input', {'type': 'text', 'value': txt}));
            var span = $('cart_main').getElements('span');
            for (var i=0; i < count(span); i++)
            {
                if (_this === span[i])
                {
                    jsm.current_element = _this;
                    return;
                }
            }
        }
    },
    comment_mouseout: function(e, _this) {
    	//if (jsm.DEBUG == 2) console.log('input2text:' + ' comment');
        if (e) e.stop();
        //if (jsm.DEBUG == 2) console.log('mouseout: ' + jsm.current_element);
        var i, j = 0;
        var result = '';
        var span = $('cart_main').getElements('span');
        for (i=0; i < count(span); i++)
        {
            if (jsm.current_element === span[i])
            {
                jsm.current_element = null;
                return;
            }
        }
        var input = _this.getChildren('input')[0];
        if (input !== undefined && input.get('tag') == 'input')
        {
            var txt = input.get('value').trim();

            if (txt == '')
            {
                input.getParent().getNext().dispose(); // br
                input.dispose();                       // span input
            }
            else
            {
            	if (jsm.DEBUG == 2) console.log('here1: ' + txt);

            	// see if param value is present, if so insert select and input
                if (txt.substring(0, 1) != '#')
                {
                	var param = txt.split(' ', 2);

                	if (jsm.DEBUG == 2) console.log('here2: ' + selects);

                	// 'div[id^=cart_main], div[id^=cart_events], div[id^=cart_http]'
		            for (i=0; i < selects.length; i++)
		            {
		                if (jsm.DEBUG == 2) console.log('here3:' + selects[i+1]);

		                for (j=0; j < selects[i+1].options.length; j++)
		                {
		                	if (selects[i+1].options[j].text == param[0])
		                	{
		                		if (jsm.DEBUG == 2) console.log('here4:' + param[0]);

		                		input.value = param[1];

		                		selects[i+1].options.selectedIndex = j;

								var span1 = Element('span', {'class': 'param'});
					            var span2 = Element('span', {'class': 'param'});
					            var img   = Element('img', {'src': 'client/img/negative_sign.png', 'onclick': 'var p=this.getParent();p.getPrevious().dispose();p.getNext().dispose();p.dispose();'});

            					span1.adopt(selects[i+1]);
            					span2.adopt(input);
                				span2.adopt(img);

                				span2.clone().inject(_this, 'after');
                				span1.clone().inject(_this, 'after');

                				_this.destroy();

                				return;
		                	}
		               	}

		               	i++;
		            }

		            txt = '#' + txt;
                }

            	input.dispose();
                _this.set('text', txt);
            }
        }
    },
    text2input: function() {
        $$('.comment').addEvent('click', function(e) {
            jsm.comment_click(e, this);
        });
    },
    input2text: function(selects) {
        $$('.comment').addEvent('mouseout', function(e) {
			jsm.comment_mouseout(e, this);
        });
    },
    form2string: function(module, method, js, css) {
        // collects all span select, input, txt node values from div parent
        // concatinates collected string places into hidden input value to be post
        // current span classnames (param curly_end curly_begin comment empty)

        if (! $('authsave') || ! $('authcancel'))
        	return;

        $('authsave').addEvent('click', function(e) {
            e.stop();
            var span = $('cart_main').getElements('span');
            var str = cn = indent = tmp = inx = c = i = j = '';

            for (i=0; i < count(span); i++)
            {
                if (! span[i]) continue;

                c = span[i].getChildren();

                // possible classnames (param location curly_begin curly_location curly_end comment empty)
                cn = span[i].get('class');

                if (cn == 'param' || cn == 'param error')
                {
                    for (j=0; j < count(c); j++)
                    {
                        if (! c[j]) continue;

                        if (c[j].get('tag') == 'select')
                            str += indent + c[j].getSelected().get('value') + ' ';
                        else if (c[j].get('tag') == 'input')
                        {
                            tmp = c[j].get('value');

                            if (tmp)
                            {
                                inx = tmp.search(';');

                                //console.log('tag: ' + c[j].get('tag'));

								if (c[j].getParent().getNext().get('class') == 'curly_location')
								{
									str += tmp + ' ';
									continue;
								}
                                else if (inx == -1)
                                    tmp += ';';
                                else if (inx < (tmp.length - 1))
                                {
                                    alert('parameter value semicolon only at end allowed');
                                    return false;
                                }
                            }
                            else
                            {
                                alert('parameter value is empty');
                                return false;
                            }

                            str += tmp + "\n";
                        }
                    }
                }
                else if (cn == 'curly_location')
                {
                    str += span[i].get('text') + "\n";
                    indent = "\t\t";
                }
                else if (cn == 'curly_begin')
                {
                    str += span[i].get('text') + "\n";
                    indent = "\t";
                }
                else if (cn == 'curly_end')
                {
                	if (span[i].getPrevious().get('id') == 'cart_location')
                		str += "\t" + span[i].get('text') + "\n";
                	else
                    	str += span[i].get('text') + "\n";

                    indent = '';
                }
                else if (cn == 'location')
                {
                    str += "\t" + span[i].get('text') + ' ';
                    indent = '';
                }
                else if (cn == 'comment')
                    str += indent + span[i].get('text') + "\n";
                else if (cn == 'empty')
                    str += span[i].get('text') + "\n";
            }

            if (str == "Empty\n")
            	str = "server {\n\t# empty\n\t# empty\n}\n";

            $('config_result').set('value', str);

            js  = (js)  ? module + '/' + method + '.js'  : '';
            css = (css) ? module + '/' + method + '.css' : '';

            ajaxPOST($('myform'), 'index.php?module=' + module + '&method=' + method, js, css);

            if (jsm.DEBUG == 3) console.log(str);
        });

        $('authcancel').addEvent('click', function(e) {
            e.stopPropagation();
            e.stop();
            js  = (js)  ? module + '/' + method + '.js'  : '';
            css = (css) ? module + '/' + method + '.css' : '';

            ajaxGET('index.php?module=' + module + '&method=' + method, 0, js, css);
        });

        $$('select').addEvent('mouseover', function(e) {
            e.stop();
            this.set('title', this.getSelected().get('title'));
        });
    },
    dragndrop: function(selects, input_class, mode) { // mode = (nginx.conf, nginx_sites ...)
        // Drag.Move() used to inject span select and input into 'carts'.
        // each 'cart' is a sub div of the parent div to define inner carts.
        // The div main container is concidered the main cart.

        $$('.item').addEvent('mousedown', function(e) {
            e.stop();

            if (jsm.DEBUG == 4) console.log('in dragndrop()');

            // `this` refers to the element with the .item class
            var clone = this.clone().setStyles(this.getCoordinates()).setStyles({
              opacity: 0.7,
              position: 'absolute',
              margin: '5px',
              padding: '5px'
            }).inject(document.body);

			var prev_tmp   = '';
			var str = tmp  = '';
            var i          = 0;
            var id         = this.get('id');
            var context    = id.split('_'); // server_select, location_select, ...
            context 	   = context[0];

            if (jsm.DEBUG == 4) console.log('context: ' + context);

            // location_select is placed in server context, assert cart_server
            var cart_id = 'cart_' + context;

            // 'div[id^=cart_main], div[id^=cart_events], div[id^=cart_http]'
            switch (cart_id)
            {
            	case 'cart_main':   str = 'div[id^=' + cart_id + ']'; break;
            	case 'cart_events': str = 'div[id^=' + cart_id + ']'; break;
            	case 'cart_http':   str = 'div[id^=' + cart_id + ']'; break;
            	case 'cart_server':
            	{
            		str = 'div[id^=' + cart_id + ']';
            		if (mode == 'nginx.sites')
            			str += ',div[id^=cart_main]';
            		break;
            	}
            	case 'cart_location':
            	{
            		str  = 'div[id^=cart_server]';
            		str += ',div[id^=' + cart_id + ']';
            		break;
            	}
            	default: alert('in Drag and Drop() no case for switch');
            }

            if (jsm.DEBUG == 4) console.log(str);

            var drag = new Drag.Move(clone, {
              precalculate: false,
              droppables: $$(str),

              onDrop: function(dragging, cart) {

                dragging.destroy();

                if (cart)
                {
                	if (jsm.DEBUG == 4) console.log('context: ' + context);

                	if (context == 'location')
                	{
                		console.log('cart: ' + cart.get('id'));

                		if (cart.get('id') == 'cart_server')
                		{
		                	// location [ = | ~ | ~* | ^~ ] dir {
		                	// =   exact match
		                	// ~   case sensitive regex
		                	// ~*  case insensitive regex
		                	// ^~  regular expressions are not checked
		                	// text select input text br

		                	// insert into server context as location container
					    	if (jsm.DEBUG == 4) console.log('in Drag.Move() server context as location container');

					    	var div = Element('div', {'id': 'cart_location'});

		                	var span2 = Element('span', {'class': 'param'});
		                	var span3 = Element('span', {'class': 'param'});

		                	var regex_select = new Element('select', {'name': 'location', 'class': 'nginx_conf', 'title': 'No Modifier', 'onchange': 'this.title=this.options[this.options.selectedIndex].title'});
							regex_select.adopt(Element('option', {'data-syntax': '', 'value': '-', 'text': ''}));
		                    regex_select.adopt(Element('option', {'value': '=', 'text': '=', 'data-syntax': '', 'label': '', 'title': 'Exact Match'}));
		                    regex_select.adopt(Element('option', {'value': '~', 'text': '~', 'data-syntax': '', 'label': '', 'title': '(regex) Case Sensitive'}));
		                    regex_select.adopt(Element('option', {'value': '~*', 'text': '~*', 'data-syntax': '', 'label': '', 'title': '(regex) Case Insensitive'}));
		                    regex_select.adopt(Element('option', {'value': '^~', 'text': '^~', 'data-syntax': '', 'label': '', 'title': '(regex) are Ignore'}));

							span2.adopt(regex_select);
							span3.adopt(Element('input', {'class': 'nginx_conf', 'type': 'text', 'value': '', 'title': ''}));

							div.adopt(Element('span', {'class': 'location', 'text': 'location'})); // location
							div.adopt(span2); // regex
							div.adopt(span3); // directory
							div.adopt(Element('span', {'class': 'curly_location', 'text': '{'}));
							div.adopt(Element('img', {'src': 'client/img/negative_sign.png', 'onclick': 'this.getParent().getNext().destroy();this.getParent().getNext().destroy();this.getParent().destroy();'}));
							div.adopt(Element('br'));

							var div_clone = div.clone(true, true);

							div_clone.inject(cart, 'bottom');

							Element('br').inject(div_clone, 'after');
							Element('span', {'class': 'curly_end', 'text': '}'}).inject(div_clone, 'after');
					    }
					    else if (cart.get('id') == 'cart_location')
					    {
					    	// insert into location context as parameter
					    	if (jsm.DEBUG == 4) console.log('in Drag.Move() location context as parameter');

					    	var span1 = Element('span', {'class': 'param'});
			                var span2 = Element('span', {'class': 'param'});
			                var input = Element('input', {'class': input_class, 'type': 'text', 'value': '', 'title': ''});
			                var img   = Element('img', {'src': 'client/img/negative_sign.png', 'onclick': 'var p=this.getParent();p.getPrevious().dispose();p.getNext().dispose();p.dispose();'});
			                var br    = Element('br');

		                    for (i=0; i < selects.length; i++)
		                    {
		                        if (id == selects[i])
		                        {
		                            span1.adopt(selects[i+1]);
		                            break;
		                        }
		                        i++;
		                    }

		                    if (jsm.DEBUG == 4) console.log('selects: ' + selects[i]);

		                    span2.adopt(input, img);

		                    var br = cart.getLast();

							br.clone().inject(br, 'after');
		                    span2.clone().inject(br, 'after');
		                    span1.clone().inject(br, 'after');
					    }
					}
					else
					{
						// insert into main, events, http, server context
						if (jsm.DEBUG == 4) console.log('in Drag.Move() main, events, http, server mode: ' + mode + ', ' + 'cart: ' + cart.get('id'));

						if (mode == 'nginx.conf' || (mode == 'nginx.sites' && cart.get('id') == 'cart_server'))
						{
							var span1 = Element('span', {'class': 'param'});

							for (i=0; i < selects.length; i++)
		                    {
		                        if (id == selects[i])
		                        {
		                            span1.adopt(selects[i+1]);
		                            break;
		                        }
		                        i++;
		                    }

		                    var d = selects[i+1].get('Default');
		                    var s = selects[i+1].get('Summary');
		                    var x = selects[i+1].get('Syntax');

		                    if (jsm.DEBUG == 4) console.log('d: ' + d + ' s: ' + s + ' x: ' + x);

			                var span2 = Element('span', {'class': 'param'});
			                var input = Element('input', {'class': input_class, 'type': 'text', 'value': d, 'title': s, 'label': x});
			                var img   = Element('img', {'src': 'client/img/negative_sign.png', 'onclick': 'var p=this.getParent();p.getPrevious().dispose();p.getNext().dispose();p.dispose();'});
			                var br    = Element('br');

		                    if (jsm.DEBUG == 4) console.log('selects: ' + selects[i]);

		                    span2.adopt(input, img);

		                    br.clone().inject(cart, 'top');
		                    span2.clone().inject(cart, 'top');
		                    span1.clone().inject(cart, 'top');
						}
						else if (mode == 'nginx.sites')
						{
							// enter server { } within nginx.sites main context
							// SPAN BR DIV SPAN BR SPAN BR

							if (! $('cart_main').getElements('span')[1])
								$('cart_main').empty();

							Element('br').inject(cart, 'top');
							Element('span', {'class': 'empty'}).inject(cart, 'top');
							Element('br').inject(cart, 'top');
							Element('span', {'class': 'curly_end', 'text': '}'}).inject(cart, 'top');

							var div = Element('div', {'id': 'cart_server'});
							div.adopt(Element('span', {'class': 'comment', 'text': '# empty', 'onmouseout': 'jsm.comment_mouseout(null, this)', 'onclick': 'jsm.comment_click(null, this)'}));
							div.clone(true, true).inject(cart, 'top');

							Element('br').inject(cart, 'top');

							var span = Element('span', {'class': 'curly_begin', 'text': 'server {'})
							var onclick = "var p=this.getParent(); p.getNext().destroy(); p.getNext().destroy(); p.getNext().destroy(); p.getNext().destroy(); if (p.getNext()) p.getNext().destroy(); if (p.getNext()) p.getNext().destroy(); if (! $('cart_main').getElements('span')[1]) p.getParent().adopt(Element('span', {'class': 'comment', 'text': 'Empty'})); p.destroy()";
							span.adopt(Element('img', {'src': 'client/img/negative_sign.png', 'onclick': '' + onclick + ''}).inject(cart, 'top'));
							span.clone(true, true).inject(cart, 'top');
						}
	                }

                    cart.highlight('#7389AE', '#DDD');
                }
              },
              onEnter: function(dragging, cart) {
	            cart.setStyle('background-color', '#98B5C1');
              },
              onLeave: function(dragging, cart){
                cart.setStyle('background-color', '#DDD');
              },
              onCancel: function(dragging, cart){
                dragging.destroy();
                cart.setStyle('background-color', '#DDD');
              }
            });
            drag.start(e);
        });
    },
    removeParam: function() {
        // mainly a png like a negative sign.
        // removes a double span, its img and br
        if (! $('cart_main')) return;
        $('cart_main').getElements('a').addEvent('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            this.getParent().getPrevious().dispose();
            this.getParent().dispose();
        });
    }
}

function pollEnd(str)
{
    clearInterval(intervalPollTimer);
    clearInterval(intervalTimer);
    polling = 0;
    pollReq = null;
    pollCounter = 0;
    pollCountPrompt = 0;
    pollScroll.toBottom();
    $('clickshield_msg').set('html', '<p class="clickshield_title"><a href="javascript:polling=0;clickShield(0)">' + str +' Click to Close</a></p>');
    if (str === 'Prompt Reached' || str === 'Successful Screen Exit')
    {
        setTimeout(function(){polling=1;ajaxGET('index.php?core=headhtml', 0, 0, 0, 'head');}, 1);
        setTimeout(function(){polling=0;}, 5000);
    }
}

function poll(action, item)
{
    if (polling) return;

    polling = 1;
    pollCounter = pollCountPrompt = 0;

    rand = randomString(16);

    ajaxGET('index.php?core=pollaction&action=' + action + '&item=' + item + '&rand=' + rand);

    pollReq = new Request.HTML({
        url: 'index.php?core=poll&rand=' + rand,
        method: 'get',
        onSuccess: function(/*obj*/responseTree,/*arr*/responseElements,/*txt*/responseHTML,/*txt*/responseJavaScript) {
            if (responseHTML == 'Poll_finished_Poll')
                pollEnd('Successful Poll');
            else if (responseHTML == 'Pollpscreen_finished_Pollpscreen')
                pollEnd('Successful Screen Exit');
            else if (responseHTML == 'Poll_no_rand_file_Poll')
                pollEnd('ERROR: Missing rand file.');
            else if (responseHTML == 'Poll_rand_file_not_numeric_Poll')
                pollEnd('ERROR: Rand file not alpha-numeric.');
            else if (responseHTML == 'Poll_rand_file_pid_invalid_Poll')
                pollEnd('ERROR: Rand file present with no PID.');
            else
            {
                pollCounter = 0;
                $('poll_msg').set('html', '<pre class="poll_title">' + responseHTML + '</pre>');
                pollScroll.toBottom();

                pollResult = explode("\n", responseHTML);
                pollResult = pollResult[count(pollResult)-1];

                if (strstr(pollResult, '@') && strstr(pollResult, ':') && (trim(pollResult.slice(-1)) === '#' || trim(pollResult.slice(-1)) === '$'))
                {
                    pollCountPrompt++;
                    if (pollCountPrompt > 1)
                        pollEnd('Prompt Reached');
                }
            }
        },
        onFailure: function() {
            pollEnd('Request Failed.');
        }
    });

    setTimeout(function(){intervalPollTimer=pollPulse.periodical(1500);}, 3000);
    $('poll_msg').setStyle('display', 'block');
    if (action == 'pscreen') $('cmd_msg').setStyle('display', 'block');
}

function randomString(length)
{
    var result = '';
    var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for (var i = length; i > 0; --i) result += chars[Math.round(Math.random() * (chars.length - 1))];
    return result;
}

function animateShield()
{
    $('clickshield_msg').set('html', '<p class="clickshield_title">Loading for ' + numSeconds + ' seconds.</p>');
    numSeconds++;
}

function clickShieldMsg(up)
{
    if (shieldMsgFx)
    {
        if (up)
        {
            shieldMsgFx.cancel();
            shieldMsgFx.start(0, 1);
        }
        else
        {
            shieldMsgFx.cancel();
            shieldMsgFx.start(1, 0);
        }
    }
}

function clickShield(up)
{
    if (up)
    {
        numSeconds = 0;
        clickShieldMsg(up);
        $('clickshield_msg').set('html', '<p class="clickshield_title">Loading for ' + numSeconds + ' seconds.</p>');
        $('clickshield').addClass('clickshield_up');
        $('clickshield_msg').setStyle('display', 'block');
        intervalTimer = animateShield.periodical(1000);
    }
    else
    {
        if (! polling)
        {
            clearInterval(intervalTimer);
            $('clickshield').removeClass('clickshield_up');
            $('clickshield_msg').setStyle('display', 'none');
            $('poll_msg').setStyle('display', 'none');
            $('poll_msg').set('html', '<pre class="poll">Polling...</pre>');
            $('cmd_msg').setStyle('display', 'none');
            numSeconds = 0;
        }
    }
}

function manageExternAssets(jsUrl, cssUrl)
{
    var rand = randomString(8);

    //console.log('manageExternAssets(' + jsUrl + ' ' + cssUrl + ' ' + rand + ')');

    var prevJs = $('dyna_loaded_js');
    if (prevJs) prevJs.dispose();

    var prevCss = $('dyna_loaded_css');
    if (prevCss) prevCss.dispose();

    if (jsUrl)
    {
        var js = Element('script', { 'id': 'dyna_loaded_js', 'type': 'text/javascript', 'src': 'client/js/comp/' + jsUrl + '?' + rand });
        document.getElementsByTagName('head')[0].appendChild(js);
    }

    if (cssUrl)
    {
        var css = Element('link', { 'id': 'dyna_loaded_css', 'rel': 'stylesheet', 'href': 'client/css/comp/' + cssUrl + '?' + rand });
        document.getElementsByTagName('head')[0].appendChild(css);
    }
}

function ajaxPOST(myForm, getUrl, jsUrl, cssUrl)
{
    if (req) return;

    req = myForm.set('send', {
        url: getUrl, method: 'post', link: 'ignore',
        onSuccess: function(responseHTML) {
            req = null;
            $('main_content').set('html', responseHTML);
            //console.log('ajaxPOST ' + jsUrl + ' ' + cssUrl);
            if (jsUrl || cssUrl)
                manageExternAssets(jsUrl, cssUrl);
            else
            {
                manageExternAssets();
                if (mainContentSlide) mainContentSlide.slideIn();
            }
        },
        onFailure: function() {
            req = null;
            $('main_content').set('text', 'The request failed.');
            if (mainContentSlide) mainContentSlide.slideIn();
        }
    });
    if (mainContentSlide) mainContentSlide.slideOut();
    clickShield(1);
}

function ajaxGET(getUrl, showPopup, jsUrl, cssUrl, container)
{
    //console.log('before req ' + getUrl);
    //if (req) return;
    //console.log('after req ' + getUrl);

    container_id = (container) ? container : 'main_content';

    headmenuHTML = (getUrl === 'index.php?core=headhtml' || getUrl === 'index.php?core=menuhtml') ? 1 : 0;

    req = new Request.HTML({
        url: getUrl, method: 'get', link: 'ignore',
        onSuccess: function(/*obj*/responseTree,/*arr*/responseElements,/*txt*/responseHTML,/*txt*/responseJavaScript) {

            //console.log('onSuccess');

            req = null;

            if (responseHTML == 'Panel_Session_Timedout_Panel')
                location.href = 'index.php?core=logout';

            if (showPopup)
            {
                popupmsg.setMessage(responseHTML);
                popupmsg.show();
            }
            else if (skipItems.indexOf(container_id) == -1) // skipItems = head, html, main_container
            {
                console.log(responseHTML);
                if (container_id != 'null')
                    $(container_id).set('html', responseHTML);
            }
            else
            {
                $(container_id).set('html', responseHTML);

                if (jsUrl || cssUrl)
                    manageExternAssets(jsUrl, cssUrl);
                else
                {
                    manageExternAssets();
                    if (mainContentSlide) mainContentSlide.slideIn();
                }

                if (container_id == 'head')
                    setTimeout(function(){ajaxGET('index.php?core=menuhtml', 0, 0, 0, 'menu');}, 10);

                if (container_id == 'menu')
                    new Fx.Accordion($$('.toggler'), $$('.element'), { alwaysHide: true, show: -1 });
            }
        },
        onFailure: function() {
            console.log('onFailure');
            req = null;
            if (showPopup)
            {
                popupmsg.setMessage('Failed Request');
                popupmsg.show();
            }
            else
            {
                $(container_id).set('text', 'Request Failed');
                if (mainContentSlide) mainContentSlide.slideIn();
            }
        }
    });

    //console.log('after new');

    if (! headmenuHTML && (skipItems.indexOf(container_id) != -1 || container_id != 'null')) clickShield(1);

    if (! showPopup && ! headmenuHTML && (skipItems.indexOf(container_id) != -1 || container_id != 'null'))
        mainContentSlide.slideOut();
    else if (req)
        req.send();
}

var skipItems = ['menu','head','main_content'];
var headmenuHTML = false;
var mainContentSlide = null;
var container_id = null;
var drag = null;
var req = null;
var pollReq = null;
var polling = 0;
var popupmsg = null;
var shieldMsgFx = null;
var notValidFx = null;
var pollScroll = null;
var pollResult = null;
var windowScroll = null;
var intervalTimer = null;
var intervalPollTimer = null;
var pollCounter = 0;
var pollCountPrompt = 0;
var numSeconds = 0;
var rand = 0;
var firedEvent = 0;


var pollPulse = function() {
    pollCounter++;
    if (pollCounter > 60)
        pollEnd('Poll Cycle Reached:' + pollCounter);
    else if (pollReq)
        pollReq.send();
};

window.addEvent('domready', function() {

    if ($('clickshield_msg')) shieldMsgFx = new Fx.Tween($('clickshield_msg'), {fps: 30, duration: 250, property: 'opacity'});

    mainContentSlide = new Fx.Slide('main_content', {resetHeight: true, fps: 30, duration: 250, transition: Fx.Transitions.Expo.easeInOut});

    if (mainContentSlide)
    {
        mainContentSlide.addEvents({
            'start': function() {
                if (! mainContentSlide.open) { // start sliding down
                    if (! polling) clickShieldMsg(0);
                    if ($('notValid'))
                    {
                        notValidFx = new Fx.Tween($('notValid'), {fps: 30, duration: 750, property: 'opacity'});
                        if (notValidFx)
                        {
                            notValidFx.cancel();
                            notValidFx.start(0, 1);
                        }
                    }
                }
                else { // start sliding up
                }
            },
            'complete': function() {
                if (! mainContentSlide.open) { // slide is up
                    if (req) req.send();
                }
                else { // slide is down
                    clickShield(0);
                    if (windowScroll) windowScroll.toTop();
                    console.log('firedEvent: ' + firedEvent);
                    if ($('hosts')) // $('fire_event'))
                    {
                        // fireEvent for: module=Server&method=accounts
                        var test = $('hosts').getElement('input[type=radio]:checked');
                        if (test)
                        {
                            if (firedEvent)
                                firedEvent = 0;
                            else if (! firedEvent)
                            {
                                console.log('in test');
                                firedEvent = 1;
                                ajaxGET('index.php?core=menuhtml', 0, 0, 0, 'menu');

                                //test.fireEvent.delay(500, test, 'click'); // either works
                                //test.fireEvent('click', test, 500);
                            }
                        }
                    }
                }
            }
        });

        mainContentSlide.slideIn();
    }

    pollScroll   = new Fx.Scroll($('poll_msg'));
    windowScroll = new Fx.Scroll($(document.body));

    popupmsg = new uMessagebox({duration: 500, title: 'ESC Key or Click outside Popup to Exit'});

    if (popupmsg)
    {
        popupmsg.addEvents({
            'show': function() {
                clickShield(0);
            },
            'hide': function() {
            }
        });
    }

    ajaxGET('index.php?core=headhtml', 0, 0, 0, 'head');
});