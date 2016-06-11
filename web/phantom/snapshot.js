/**
 * @since 11/06/2016
 */
var system = require('system');
var page = require('webpage').create();

console.log('capture demandée ',system.args[1]);
console.log('fichier de destination : ',system.args[2]);

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
page.open(system.args[1], function() {
	page.render(system.args[2]);
	phantom.exit();
});