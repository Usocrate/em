const config = require('./config.js');
var http = require('http');
var url = require('url');
var querystring = require('querystring');
var mysql = require('mysql');
var fs = require('fs');
var webshot = require('webshot');
var imagemagick = require('imagemagick');

http.createServer(function(request, response) {
	/**
	 * Demande de snapshot
	 */
	if (url.parse(request.url).pathname == '/snapshot/bookmark') {
		var params = querystring.parse(url.parse(request.url).query);
		/**
		 * L'identifiant du bookmark est passé en paramètre
		 */
		if (params['id']!=undefined) {
			console.log(config.getMySqlConnectionParams());
			var connection = mysql.createConnection(config.getMySqlConnectionParams());
			connection.query('SELECT bookmark_id AS id, bookmark_title AS title, bookmark_url AS url FROM bookmark WHERE bookmark_id=?',params['id']).on('result', function(row){
				//console.log(row.url,'(',row.id,') existe');
				//console.log(response);
				var options = {
					screenSize: {
						width: 1024,
						height: 768
					},
					shotSize: {
						width: 1024,
						height: 768
					}
				};
				var zoom_ratio = 0.3125; // pour passer de 1024*768 à 320*240
				//console.log('L\'endroit où enregistrer le snapshot : ',config.data_dir_path);
				var filename = row.id+'.png';
				var filepath = config.data_dir_path+'/snapshots/'+filename;
				webshot(row.url, filepath, options, function(err) {
					if (err) throw err;
					//console.log(filepath,' ok');
					imagemagick.resize({
						srcPath: filepath,
						dstPath: filepath,
						width: 320,
						height: 240
					}, function(err, stdout, stderr){
						if (err) throw err;
						//console.log('redimensionnement ',filepath,' à 320x240px');
						response.writeHead(200,{"Content-Type": "image/png"});
						response.write(fs.readFileSync(filepath),'binary');
						response.end();
					});
				});
			});
		} else {
			//console.log('Il manque un id');
			response.writeHead(200,{"Content-Type": "text/plain"});
			response.write(url.parse(request.url).query);
			response.end();
		}
	} else {
		response.writeHead(200,{"Content-Type": "text/plain"});
		var mySqlConnectionParams = config.getMySqlConnectionParams();
		response.write('Bienvenue ' + mySqlConnectionParams.user + ' !');
		response.end();
	}
}).listen(8080);
console.log('Ecoute du port 8080...');