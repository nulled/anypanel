<!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<title>Test</title>
<script src="client/js/MooTools-Core-1.5.1.js"></script>
<script src="client/js/MooTools-More-1.5.1.js"></script>
<script>
window.addEvent('domready', function() {

    $('ctest').addEvent('click', function(e) {
        //$('rtest').fireEvent('click');
        $('test').getElements('input[type=radio]:checked').fireEvent('click');
    });

    //$('rtest').addEvent('click', function(e) {
    $('test').getElements('input[type=radio]').addEvent('click', function(e) {
        console.log('rclicked');
    });

});
</script>
</head>
<body>
<input id="ctest" type="checkbox" name="ncheck" value="vcheck" /> Check
<br />
<div id="test">
    <input id="rtest" type="radio" name="nradio" value="vradio" /> Radio
</div>
</body>
</html>
