var fs = require('fs');
var mysql = require('mysql');
var webshot = require('webshot');
// var Pageres = require('pageres');

fs.readFile('../config/host.json', (err, data) => {
	  if (err) throw err;
	  
	  var config = JSON.parse(data);

	  var connection = mysql.createConnection({
	    host : config.db_host,
	    user : config.db_user,
	    password : config.db_password,
	    database : config.db_name
	  });
	  
	  // const pageres = new Pageres({delay:2, crop:true});
	  // pageres.dest(config.data_dir_path+'/snapshots');
	  
	  var options = {
		  screenSize: {
		    width: 1024,
		    height: 768
		  },
		  shotSize: {
		    width: 320,
		    height: 240
		  }
	  };
	  
	  connection.connect();
	  
	  if (process.argv.length == 3) {
		  console.log('Identifiant signet : ', process.argv[2]);
		  var id = process.argv[2];
		  var query = connection.query('SELECT bookmark_title AS title, bookmark_url AS url,bookmark_thumbnail_filename AS filename FROM bookmark WHERE bookmark_id=?',id);
		  query.on('result', function(row){
			  console.log(row.url,'(',row.title,') : ',row.filename);
		  });
	  } else {
		  var query = connection.query('SELECT bookmark_id as id, bookmark_title AS title, bookmark_url AS url FROM bookmark WHERE bookmark_thumbnail_filename IS NULL');
		  query.on('result', function(row){
			  // pageres.src(row.url, ['1024X768'], {"filename":row.id});
			  webshot(row.url, config.data_dir_path+'/snapshots/'+row.id+'.png', function(err) {
				  /*
				  connection.query('UPDATE bookmark SET bookmark_thumbnail_filename=? WHERE bookmark_id=?',[row.id+'.png',row.id]).on('result', function(row){
					  console.log(row.title,' : ',row.url);
				  });
				  */
			  });
		  });
	  }
	  
	  connection.end();
});