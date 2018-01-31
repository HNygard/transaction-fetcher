const express = require('express');
const app = express();

function log(title, contents) {
    console.log(new Date(), title + ' ------ ' + contents);
}

app.use(require('morgan')('dev'));

app.get('/', function(req, res) {
    res.send('Hello World!')
});

app.listen(8000, function() {
    log('sbanken-mock rebooted', 'Started sbanken-mock on local port 3000!')
});