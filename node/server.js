var http = require('http');
const config = require('./config.js');

http.createServer(function(request, response){
	response.writeHead(200);
	var mySqlConnectionParams = config.getMySqlConnectionParams();
	response.write('Bienvenue '+ mySqlConnectionParams.user+' !');
	response.end();
}
).listen(8080);
//console.log('Ecoute du port 8080...');