uMessagebox
===========

Simple mootools modal messagebox


How to use
----------

The js code :

        #js
        var msg = new uMessagebox({
            title: 'My title',
            message: 'Some description',
            seconds: 5,
            opacity: 20,
            duration: 300,
            type: 'alert',
            countdown: true
        });


Live Demo
-----------

* uMessagebox live demo [here](http://goo.gl/14y2S)


Screenshots
-----------
![Screenshot](http://goo.gl/pey0c)


Base Doc
-----------

Options :

  * title: "Title",
  * message: "Message",
  * seconds: 5,
  * zIndex: 8600,
  * opacity: 50,
  * duration: 300,
  * character: "'",
  * type: "alert",
  * countdown: true,
  * auto: false,
  * escClose: true,
  * clickClose: true,
  * mainclass: null,
  * classes: {
       "alert": "alert",
       "info": "info",
       "error": "error",
       "success": "success"
    }

Public Methods :

  * uMessagebox.show()
  * uMessagebox.hide()
  * uMessagebox.setTitle(str)
  * uMessagebox.setMessage(str)
  * uMessagebox.setType(str)
  * uMessagebox.setSeconds(n)
  * uMessagebox.getType()

Events :

  * uMessagebox.show()
  * uMessagebox.hide()
