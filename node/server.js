const config = require('./config.js');
var fs = require('fs');
var http = require('http');
var imagemagick = require('imagemagick');
var mysql = require('mysql');
var path = require('path');
var querystring = require('querystring');
var url = require('url');
var webshot = require('webshot');


http.createServer(function(request, response) {
	/**
	 * Demande de snapshot
	 */
	if (url.parse(request.url).pathname == '/bookmark/snapshot') {
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
					},
					renderDelay:2000
				};
				var zoom_ratio = 0.3125; // pour passer de 1024*768 à 320*240
				var filename = row.id+'.png';
				var filepath = path.resolve(config.data_dir_path,'snapshots',filename);
				
				console.log('url demandée ',row.url);
				
				var renderStream = webshot(row.url);
				var file = fs.createWriteStream(filepath, {encoding: 'binary'});
				
				//console.log('enregistrement de la capture ici : ',file.path);
				
				fs.access(file.path, fs.W_OK, (err) => {
				  console.log(err ? 'écriture impossible sur ',file.path : 'écriture possible sur ',file.path);
				});
				
				renderStream.on('data', function(data) {
					console.log('data reçus ! ',data);
					//file.write(data.toString('binary'), 'binary');
				});
				
				/*
				webshot(row.url, filepath, options, function(err) {
					if (err) throw err;
					imagemagick.resize({
						srcPath: filepath,
						dstPath: filepath,
						width: 320,
						height: 240
					}, function(err, stdout, stderr){
						if (err) throw err;
						console.log('redimensionnement ',filepath,' à 320x240px : OK');
						connection.query('UPDATE bookmark SET bookmark_thumbnail_filename=? WHERE bookmark_id=?', [filename,row.id]).on('result', function(result){
							connection.end();
							response.writeHead(200,{"Content-Type": "image/png"});
							response.write(fs.readFileSync(filepath),'binary');
							response.end();
						});
					});
				});
				*/
			});
		} else {
			response.writeHead(200,{"Content-Type": "text/plain"});
			response.write('De quel site veux-tu obtenir une image ?');
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