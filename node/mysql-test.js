var fs = require('fs');
var mysql = require('mysql');

fs.readFile('../config/host.json', (err, data) => {
	  if (err) throw err;
	  
	  var config = JSON.parse(data);

	  var connection = mysql.createConnection({
	    "host" : config.db_host,
	    "user" : config.db_user,
	    "password" : config.db_password,
	    "database" : config.db_name
	  });
	   
	  connection.connect();

	  connection.query('SELECT COUNT(*) AS nb FROM bookmark WHERE bookmark_thumbnail_filename IS NULL', function(err, rows, fields) {
		  if (err) throw err;
		  console.log('Sans aperçu : ', rows[0].nb, 'signet(s)');
	  });
	   
	  connection.end();
});