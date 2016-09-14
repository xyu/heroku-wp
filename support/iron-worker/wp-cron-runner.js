const https = require('https');
const iron_worker = require('iron_worker');

var timeout = 750;
var options = {
	hostname: iron_worker.params()['heroku_slug'] + '.herokuapp.com',
	port: 443,
	path: '/wp-cron.php?doing_wp_cron',
	method: 'GET'
};

var req = https.request(options, (res) => {
	if (200 === res.statusCode) {
		console.log(`OK   : wp-cron.php executed`);
		process.exit(0);
	} else {
		console.log(`ERROR: wp-cron.php returned status code ${res.statusCode}`);
		process.exit(1);
	}
});

req.on('socket', (soc) => {
	console.log(`INFO : Connecting to '${options.hostname}'`);
	soc.on('connect', () => {
		console.log(`INFO : Connected`);
	});
});

req.on('error', (err) => {
	if ('ECONNRESET' === err.code) {
		console.log(`OK   : Connection closed`);
		process.exit(0);
	} else {
		console.log(`ERROR: ${err.code} - ${err.message}`);
		process.exit(1);
	}
});

req.setTimeout(timeout, () => {
	console.log(`INFO : Aborting request after ${timeout}ms`);
	req.abort();
});

req.end();
