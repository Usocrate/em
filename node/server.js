var http = require('http');

http.createServer(function(request, response){
	response.writeHead(200);
	response.write('Server actif !');
	response.end();
}
).listen(8080);
//console.log('Ecoute du port 8080...');