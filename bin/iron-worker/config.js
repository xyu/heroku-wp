var config = {
	timeout: 750,
	request: {
		hostname: '{HEROKU_SLUG}.herokuapp.com',
		port: 443,
		path: '/wp-cron.php?doing_wp_cron',
		method: 'GET'
	}
};

module.exports = config;
