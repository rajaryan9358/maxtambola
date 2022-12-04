var app = require('express')();
const https = require('https');
var bodyParser = require('body-parser')
const request = require('request-promise');
const fs = require('fs');
const successlog = require('./logger');

const privateKey = fs.readFileSync('/etc/letsencrypt/live/maxtambola.com/privkey.pem', 'utf8');
const certificate = fs.readFileSync('/etc/letsencrypt/live/maxtambola.com/cert.pem', 'utf8');
const ca = fs.readFileSync('/etc/letsencrypt/live/maxtambola.com/chain.pem', 'utf8');

const credentials = {
	key: privateKey,
	cert: certificate,
	ca: ca
};

const httpsServer = https.createServer(credentials, app);

httpsServer.listen(8080, function() {
    successlog.info(`Socket server is running 8080.`);
});

process.on('uncaughtException', (ex) => {
    successlog.info(`Error crash ${ex}`);
    process.exit(1);
});

var io = require('socket.io')(httpsServer);

app.use(bodyParser.json());

app.get('/testserver', (req, res) => res.json({ status: "SUCCESS",code:"SC_01",message:"Server running" }));


app.post('/sendCurrentGame',function(req,res){
    var content = JSON.parse(JSON.stringify(req.body));
    successlog.info(`Send Game ${JSON.stringify(req.body)}`);
    if(content!=null){
        io.sockets.in("Users").emit("games", content);
    }
    res.end();
});

app.post('/sendWalletBalance',function(req,res){
    var content = JSON.parse(JSON.stringify(req.body));
    successlog.info(`Send Wallet Balance ${JSON.stringify(req.body)}`);

    if(content!=null){
        io.sockets.in(onlineUsers.get("UID-"+content.user_id)).emit("walletBalance", content);
        io.sockets.in(onlineUsers.get("UID-W-"+content.user_id)).emit("walletBalance", content);
    }
    res.end();
});


app.post('/sendNumber', function(req, res) {
    var content = JSON.parse(JSON.stringify(req.body));
    successlog.info(`Send Number ${JSON.stringify(req.body)}`);
    if(content!=null){
        io.sockets.in("Users").emit("callNumber", content);
        io.sockets.in("Admins").emit("callNumber", content);
    
        setTimeout(startTime, content.duration*1000);
    }else{
        setTimeout(startTime, 6000);
    }

    res.end();
});

app.post('/sendTicketSale', function(req, res) {
    var content = JSON.parse(JSON.stringify(req.body));
    successlog.info(`Send Ticket Sale ${JSON.stringify(req.body)}`);

    if(content!=null){
        io.sockets.in("Users").emit("ticketSale", content);
        io.sockets.in("Admins").emit("ticketSale", content);
    }

    res.end();
})

app.post('/sendJoinData', function(req, res) {
    var content = JSON.parse(JSON.stringify(req.body));
    successlog.info(`Send Join Data ${JSON.stringify(req.body)}`);

    if(content!=null){
        io.sockets.in("Users").emit("joinData", content);
        io.sockets.in("Admins").emit("joinData", content);
    }

    res.end();
})

app.post('/sendBumperSale', function(req, res) {
    var content = JSON.parse(JSON.stringify(req.body));
    successlog.info(`Send Bumper Sale ${JSON.stringify(req.body)}`);

    if(content!=null){
        io.sockets.in("Users").emit("bumperSale", content);
        io.sockets.in("Admins").emit("bumperSale", content);
    }

    res.end();
})

app.post('/sendPrizeClaim', function(req, res) {
    var content = JSON.parse(JSON.stringify(req.body));
    successlog.info(`Send Prize Claim ${JSON.stringify(req.body)}`);

    if(content!=null){
        io.sockets.in("Users").emit("prizeClaim", content);
        io.sockets.in("Admins").emit("prizeClaim", content);
    }

    res.end();
})


var onlineUsers=new Map();

io.on('connection', function(socket) {
    var handshakeData = socket.request;
    if (handshakeData._query['type'] == "user") {
        socket.join("Users");
    } else if (handshakeData._query['type'] == "admin") {
        socket.join("Admins");
    }

    socket.on("addUser",(userId)=>{
        successlog.info(`User Connected ${userId} ${socket.id}`);
        onlineUsers.set("UID-"+userId,socket.id);
    });

    socket.on("addUserWallet",(userId)=>{
        onlineUsers.set("UID-W-"+userId,socket.id);
    })

    socket.on('disconnect',(reason)=>{
        successlog.info(`User Disconnected ${socket.id}`);
        socket.leave("Users");
    })
})

function startTime() {
    request(options).then(function(response) {
        })
        .catch(function(err) {
            console.log(err);
        })
}

const options = {
    method: 'GET',
    uri: 'https://maxtambola.com/api/call_number/HSU82347HSBQ344HH',
    body: "",
    json: true,
    maxAttempts: 5,
    retryDelay: 6000,
    headers: {
        'Content-Type': 'application/json'
            // 'Authorization': 'bwejjr33333333333'
    }
}