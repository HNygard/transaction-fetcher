const express = require('express');
const app = express();
const PushBullet = require('pushbullet');

// :: Read config file
// See config.sample.json for example. Add your Pushbullet access token
var fs = require('fs');
var config = JSON.parse(fs.readFileSync('config.json', 'utf8'));

// :: Setup
const pusher = new PushBullet(config.pushbullet_access_token);

function sendPushMessage(title, contents) {
    console.log(new Date(), title + ' ------ ' + contents);
    pusher.note('', title, contents, function(err) {
        if (err) {
            console.error(new Date(), 'Error while pushing.', err);
        }
    });
}

app.use(require('morgan')('dev'));

app.get('/', function(req, res) {
    res.send('Hello World!')
});

app.listen(8000, function() {
    sendPushMessage('push-msg rebooted', 'Started push-msg on local port 3000!')
});