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
	  
	  if (process.argv.length == 3) {
		  console.log('Identifiant signet : ', process.argv[2]);
		  var id = process.argv[2];
		  var query = connection.query('SELECT bookmark_title AS title, bookmark_url AS url,bookmark_thumbnail_filename AS filename FROM bookmark WHERE bookmark_id=?',id);
		  query.on('result', function(row){
			  console.log(row.url,'(',row.title,') : ',row.filename);
		  });
	  } else {
		  connection.query('SELECT COUNT(*) AS nb FROM bookmark WHERE bookmark_thumbnail_filename IS NULL', function(err, rows, fields) {
			  if (err) throw err;
			  console.log('Sans aperçu : ', rows[0].nb, 'signet(s)');
		  });
	  }
	  connection.end();
});