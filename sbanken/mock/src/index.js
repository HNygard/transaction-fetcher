const express = require('express');
const app = express();

function log(title, contents) {
    console.log(new Date(), title + ' ------ ' + contents);
}

app.use(require('morgan')('dev'));

app.get('/', function(req, res) {
    res.send('Hello World!')
});

app.post('/identityserver/connect/token', function(req, res) {
    res.send('{\"expires_in\": 100, \"access_token\": \"mytoken\"}');
});

var getTransactionRequests = 0;
app.get('/bank/api/v1/Transactions/:clientId/:accountNumber', function(req, res) {
    getTransactionRequests++;
    var numberOfTransactions = parseInt(getTransactionRequests * 0.3) + 1;
    var items = [];
    var transactionId = 1;
    for (var i = 0; i <= numberOfTransactions; i++) {
        transactionId = transactionId + 1;
        console.log(numberOfTransactions, transactionId, (numberOfTransactions - transactionId));
        var item = {
            'transactionId': (
                // Simulate that the bank sends 0 as transaction id
                ((numberOfTransactions - transactionId) < 3)
                ? 0
                : transactionId
            ),
            'otherAccountNumber': '',
            'amount': 100,
            'text': (
                // Simulate that the bank sends temp description first
                ((numberOfTransactions - transactionId) < 5)
                ? 'PURCHARSE'
                : 'Company ASA'
            ),
            'transactionType': '?',
            'registrationDate': '2018-01-01',
            'accountingDate': '2018-01-01',
            'interestDate': '2018-01-01'

        };
        items.push(item);
    }
    res.send(JSON.stringify({
        items: items,
        availableItems: items.length
    }));
});

app.listen(8000, function() {
    log('sbanken-mock rebooted', 'Started sbanken-mock on local port 3000!')
});