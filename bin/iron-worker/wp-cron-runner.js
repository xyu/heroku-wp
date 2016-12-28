var log = function(state, message) {
	var prefix = '[' + (new Date()).toISOString() + '] ';

	switch(state) {
		case 'done':
			prefix += 'DONE : ';
			break;
		case 'error':
			prefix += 'ERROR: ';
			break;
		case 'info':
		default:
			prefix += 'INFO : ';
			break;
	}

	console.log(prefix + message);
}

log(`info`, `Starting WP Cron Runner`);

const https = require('https');
const config = require('./config');

log(`info`, `Configs loaded`);

var req = https.request(config.request, (res) => {
	if (200 === res.statusCode) {
		log(`done`, `wp-cron.php executed`);
		process.exit(0);
	} else {
		log(`error`, `wp-cron.php returned status code ${res.statusCode}`);
		process.exit(1);
	}
});

req.on('socket', (soc) => {
	log(`info`, `Connecting to '${config.request.hostname}'`);
	soc.on('connect', () => {
		log(`info`, `Connected`);
	});
});

req.on('error', (err) => {
	if ('ECONNRESET' === err.code) {
		log(`done`,`Connection closed`);
		process.exit(0);
	} else {
		log(`error`, `${err.code} - ${err.message}`);
		process.exit(1);
	}
});

req.setTimeout(config.timeout, () => {
	log(`info`, `Aborting request after ${config.timeout}ms`);
	req.abort();
});

req.end();
