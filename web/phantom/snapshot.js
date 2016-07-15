/**
 * @since 11/06/2016
 */
var system = require('system');
var page = require('webpage').create();

console.log('capture attendue : ',system.args[1]);
console.log('fichier de destination : ',system.args[2]);

page.settings.resourceTimeout = 7000; // 7 seconds
page.viewportSize = {
	width : 1024,
	height : 768
};
page.clipRect = {
	top : 0,
	left : 0,
	width : 1024,
	height : 768
};
page.onResourceTimeout = function(e) {
  console.log(e.errorCode);   // it'll probably be 408 
  console.log(e.errorString); // it'll probably be 'Network timeout on resource'
  console.log(e.url);         // the url whose request timed out
  phantom.exit(1);
};
page.open(system.args[1], function() {
	page.render(system.args[2],{format: 'jpeg', quality: '100'});
	phantom.exit();
});