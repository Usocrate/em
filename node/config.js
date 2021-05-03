var fs = require('fs');
var path = require('path');

fs.readFile('../config/host.json', (err, file_content) => {
	console.log('Lecture de la configuration...');
	if (err) throw err;  
	var data = JSON.parse(file_content);
	//console.log(data);
	//console.log(path.sep);
	exports.data_dir_path = data.data_dir_path;
	exports.getMySqlConnectionParams = () => {
		return JSON.parse('{"host":"'+data.db_host+'","database":"'+data.db_name+'","user":"'+data.db_user+'","password":"'+data.db_password+'"}');
	}
});