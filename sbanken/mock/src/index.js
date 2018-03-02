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
app.get('/bank/api/v2/Transactions/:clientId/:accountNumber', function(req, res) {
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
            'accountingDate': '2018-01-01',
            'interestDate': '2018-01-01'

        };
        if ((numberOfTransactions - transactionId) < 5) {
            item.cardDetailsSpecified = false;
        }
        else {
            item.cardDetailsSpecified = true;
            item.cardDetails = {
                "cardNumber": "*1234",
                "currencyAmount": 100,
                "currencyRate": 1,
                "merchantCategoryCode": "5411",
                "merchantCategoryDescription": "Dagligvarer",
                "merchantCity": "Hello",
                "merchantName": "Good day",
                "originalCurrencyCode": "NOK",
                "purchaseDate": "2018-01-01T00:00:00+01:00",
                "transactionId": "1234567890"
            };
        }
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