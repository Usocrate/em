/**
 * @since 11/06/2016
 */
var system = require('system');
var page = require('webpage').create();

console.log('capture attendue : ',system.args[1]);
console.log('fichier de destination : ',system.args[2]);

page.settings.resourceTimeout = 9000; // 9 seconds

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

page.onError = function(msg, trace) {
	var msgStack = ['Erreur: ' + msg];
	if (trace && trace.length) {
		msgStack.push('Trace:');
		trace.forEach(function(t) {
			msgStack.push(' -> ' + t.file + ': ' + t.line + (t.function ? ' (in function "' + t.function +'")' : ''));
		});
	}
	console.error(msgStack.join('\n'));
};

page.open(system.args[1], function() {
	window.setTimeout(page.render(system.args[2],{format: 'jpeg', quality: '100'}),5000); // appel avec temporisation pour laisser le temps au javascript de construire le document
	phantom.exit();
});