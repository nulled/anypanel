window.uMessagebox = new Class({
    version: '1.1',
    Implements: [Events, Options],
    options: {
        title: "",
        message: "",
        seconds: 5,
        zIndex: 8600,
        opacity: 20,
        duration: 400,
        character: "'",
        type: "info",
        countdown: false,
        auto: false,
        escClose: true,
        clickClose: true,
        mainclass: null,
        classes: {
            "alert": "alert",
            "info": "info",
            "error": "error",
            "success": "success"
        }
    },
    interval: null,
    countdown: 0,
    sShow: false,
    sHide: false,
    initialize: function(options) {
        this.setOptions(options);

        this.mainclass = this.options.mainclass || 'umessagebox';
        this.container = new Element("div").setStyles({
            "zIndex": this.options.zIndex + 1,
            "display": "none"
        }).addClass(this.getStyleClass("container", true)).inject(document.body);

        this.overlay = new Element("div").setStyles({
            "zIndex": this.options.zIndex,
            "opacity": 0,
            "display": "none",
            "position": "fixed",
            "top": 0,
            "left": 0,
            "width": "100%",
            "height": "100%"
        }).addClass(this.getStyleClass("overlay", true)).inject(document.body);

        var tweens = {
            duration: this.options.duration,
            transition: 'linear',
            link: 'cancel'
        };

        this.container.set('tween', tweens);
        this.overlay.set('tween', tweens);

        this.counter = new Element("span").addClass(this.getStyleClass("countdown")).inject(this.container);

        var content = new Element("div").addClass(this.getStyleClass("content")).inject(this.container);
        new Element("span").addClass(this.getStyleClass("icon")).inject(content);

        this.msg = new Element("span").addClass(this.getStyleClass("message")).inject(content);
        this.msg_title = new Element("div").addClass(this.getStyleClass("title")).set("html", this.options.title).inject(this.msg);
        this.msg_description = new Element("div").addClass(this.getStyleClass("description")).set("html", this.options.message).inject(this.msg);

        if (this.options.auto) {
            this.show();
        }
    },
    getStyleClass: function(element_name, root) {
        return (root ? this.mainclass + " " : "") + this.mainclass + "-" + element_name;
    },
    show: function() {
        if (!this.sShow) {
            this.sShow = true;
            this.setDefaults();
            this.bindEvents();

            if (this.options.countdown) {
                this.setAutoClose();
            }

            this.resetAnimation();
            this.resetPosition();

            this.container.tween("opacity", 1);
            this.overlay.tween("opacity", this.options.opacity / 100);

            (function() {
                this.sHide = true;
                this.fireEvent("show");
            }.bind(this)).delay(this.options.duration);
        }
    },
    hide: function() {
        clearInterval(this.interval);

        if (this.sHide) {
            this.sShow = false;
            this.sHide = false;
            this.unbindEvents();

            if (this.countdown) {
                this.interval = null;
                this.countdown = 0;
            }

            this.container.tween("opacity", 0);
            this.overlay.tween("opacity", 0);

            (function() {
                this.container.setStyle("display", "none");
                this.overlay.setStyle("display", "none");
                this.fireEvent("hide");
            }.bind(this)).delay(this.options.duration);
        }
    },
    setTitle: function(title) {
        this.options.title = title;
    },
    setMessage: function(message) {
        this.options.message = message;
    },
    setType: function(type) {
        this.options.type = type;
    },
    setSeconds: function(seconds) {
        this.options.seconds = seconds;
    },
    getType: function() {
        return this.options.type;
    },
    setDefaults: function() {
        if (this.options) {
            this.container.set("class", "").addClass(this.getStyleClass("container", true) + " " + this.options.classes[this.options.type]);
            this.msg_title.set("html", this.options.title);
            this.msg_description.set("html", this.options.message);

            if (this.options.countdown) {
                this.setCounterText(this.options.seconds);
            }
        } else {
            alert("Option 'classes' shouldn't null");
        }
    },
    updateCounter: function() {
        this.countdown--;
        this.setCounterText(this.countdown);

        if (this.countdown < 1) {
            this.hide();
        }
    },
    resetAnimation: function() {
        var styles = {
            "display": "block",
            "opacity": "0"
        };

        this.container.setStyles(styles);
        this.overlay.setStyles(styles);
    },
    setCounterText: function(text) {
        this.counter.set("text", text.toString() + this.options.character.toString());
    },
    setAutoClose: function() {
        this.countdown = this.options.seconds;
        this.interval = setInterval(this.updateCounter.bind(this), this.countdown === 0 ? 0 : 1 * 1000);
    },
    bindEvents: function() {
        document.addEvent("keydown", function(e) {
            e.preventDefault();

            if (e.code === 27 && this.options.escClose) {
                this.hide();
            }
        }.bind(this));

        if (this.options.clickClose) {
            this.overlay.addEvent("click", function(e) {
                e.preventDefault();
                this.hide();
            }.bind(this));
        }

        window.addEvent("resize", this.resetPosition.bind(this));
    },
    unbindEvents: function() {
        window.removeEvents("resize");

        if (this.options.clickClose) {
            this.overlay.removeEvents("click");
        }

        document.removeEvents("keydown");
    },
    resetPosition: function() {
        this.container.setStyles({
            "left": (window.getSize().x - this.container.getSize().x) / 2,
            "top": (window.getSize().y - this.container.getSize().y) / 2
        });
    }
});