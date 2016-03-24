var fs = require('fs');
var mysql = require('mysql');
var webshot = require('webshot');

fs.readFile('../config/host.json', (err, data) => {
	  if (err) throw err;
	  
	  var config = JSON.parse(data);

	  var connection = mysql.createConnection({
	    host : config.db_host,
	    user : config.db_user,
	    password : config.db_password,
	    database : config.db_name
	  });
	  
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

	  connection.connect();
	  
	  if (process.argv.length == 3) {
		  console.log('Identifiant signet : ', process.argv[2]);
		  var id = process.argv[2];
		  var query = connection.query('SELECT bookmark_id AS id, bookmark_title AS title, bookmark_url AS url FROM bookmark WHERE bookmark_id=?',id);
		  query.on('result', function(row){
			  console.log(row.url,'(',row.id,') existe');
			  webshot(row.url, config.data_dir_path+'/snapshots/'+row.id+'.png', options, function(err) {
				  connection.query('UPDATE bookmark SET bookmark_thumbnail_filename=? WHERE bookmark_id=?',[row.id+'.png',row.id])
				  	.on('result', function(result){
					  console.log('.png enregistré');
				  	});
			  });			  
		  });
	  } else {
		  // pas d'identifiant fourni : liste des signets sans vignette
		  var query = connection.query('SELECT bookmark_id as id, bookmark_title AS title FROM bookmark WHERE bookmark_thumbnail_filename IS NULL');
		  query.on('result', function(row){
			  console.log(row.title,' : vignette ',row.id+'.png manquante');
		  });
	  }
});