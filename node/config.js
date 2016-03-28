var fs = require('fs');

fs.readFile('../config/host.json', (err, file_content) => {
	  if (err) throw err;	  
	  var data = JSON.parse(file_content);
	  exports.data_dir_path = data.data_dir_path;
	  exports.getMySqlConnectionParams = () => JSON.parse('{"host":"'+data.db_host+'","database":"'+data.db_name+'","user":"'+data.db_user+'","password":"'+data.db_password+'"}');
});