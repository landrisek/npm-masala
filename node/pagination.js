/**sudo npm install -g n
sudo n 0.10
sudo npm install socket.io 
npm install --save mysql2
*/
var io = require('socket.io').listen(8000);
io.sockets.on('connection', function (socket) {
    console.log('emit...');
    socket.on('getSum', function (data) {
        var mysql = require('mysql2');
        var connection = mysql.createConnection({
            host: 'localhost',
            user: 'myLogin',
            password: 'myPassword',
            database: 'myDatabase'
        });
        var crypto = require('crypto');
        var decipher=crypto.createDecipheriv('aes-256-cbc','NHz7mK0KtGGow3Khlgvf4qmVE9UAbgqa','1234567890123456');
        decipher.setAutoPadding(false);
        var secret = decipher.update(data.key,'hex','utf8');
        secret += decipher.final('utf8');
        var arguments = secret.split('||||||||||||||||||');
        connection.execute(arguments['sumQuery'], arguments['arguments'].split(','), function (err, results) {
            var sum = results[0].sum;
            socket.emit('setSum', {sum: sum});
        });
        connection.end();
    });
});