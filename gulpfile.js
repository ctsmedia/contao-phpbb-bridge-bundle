// Dependencies
var gulp = require('gulp'),
    gsftp = require('gulp-sftp'),
    watch = require('gulp-watch');

var exec = require('child_process').execSync;
var fs = require('fs');

// Config
// Overwrite settings in local config file if needed: gulp.config.js
var docker = {
    domain: 'phpbbbridge.contao.local',
    container: 'phpbb_bridge',
    remotePath: '/var/www/share/phpbbbridge.contao.local/contao',
    user: 'root'
};
docker.cmd = {
    run: 'docker run -it --name '+docker.container+' phpbb_bridge:latest /sbin/my_init --enable-insecure-key',
    start: 'docker start '+docker.container,
    getIp: 'docker inspect --format  \'{{ .NetworkSettings.IPAddress }}\' '+docker.container
};
docker.sftp = {
    host:docker.domain,
    user:docker.user,
    remotePath: docker.remotePath,
    remotePathBridge: docker.remotePath + '/vendor/ctsmedia/contao-phpbb-bridge-bundle',
    remotePathVendors: docker.remotePath + '/vendor',
    passphrase: null
};

// src dirs
var srcVendors = [
        'vendor/contao/core-bundle/src/**/*', // Some vendors, for debugging purposes
    ],
    srcBridge = ['src/**/*','!src/**/*___jb_tmp___'];


// Run this before all other tasks
gulp.task('init', function (cb) {
    // Load local Settings if exist
    try {
        if(fs.statSync('./gulp.config.js').isFile()){
            console.log("Local Config File found.");
            eval(fs.readFileSync('./gulp.config.js').toString());
            console.log("File parsed. Overwriting Defaults.");
        }
    } catch(error){
        if(error.code == 'ENOENT') {
            console.log("No Local config file found.");
        } else {
            return cb(error);
        }
    }
    cb();
});

// Look for docker container and start it
gulp.task('docker:init', ['init'], function (cb) {

    var containerRunning = false,  containerRunningTries = 0;

    // Find Container
    console.log('Looking for Container '+docker.container);
    try {
        exec('docker ps -a | grep -wc ' + docker.container);
    } catch(error) {
        console.log(error);
        console.log("No Docker Container found. (was looking for "+docker.container+"). You have to run it by yourself:");
        console.log(docker.cmd.run);
        return cb(new Error("No Docker Container found. (was looking for "+docker.container+")."));
    }
    console.log("Container found. Testing if it started...");

    // Start Container
    while (containerRunning == false && containerRunningTries < 3) {
        try {
            exec('docker ps | grep -wc ' + docker.container);
            console.log("Container is running");
            containerRunning = true;
        } catch(error) {
            console.log("Container is not running. Trying to start. Try: #"+(containerRunningTries+1));
            exec(docker.cmd.start);
        }

        containerRunningTries++;
    }

    // Setting hosts entry
    console.log("Looking for IP Address: ");
    var ip = exec(docker.cmd.getIp).toString('utf-8').replace(/(\r\n|\n|\r)/gm,"");
    console.log(ip);
    var isHostWritable = true;
    fs.access('/etc/hosts', fs.W_OK, function(err){
        if(err) isHostWritable = false;

        if(isHostWritable == true) {
            console.log("Adding to /etc/hosts");
            exec('cp /etc/hosts ~/.hosts');
            exec("sed -i '/"+docker.domain+"/d' ~/.hosts");
            exec('echo "'+ip+' '+docker.domain+'" >> ~/.hosts');
            exec('cat ~/.hosts > /etc/hosts');
            exec('rm ~/.hosts');
            console.log("host entry added");
            console.log("You can now acces your container via: http://"+docker.domain);
            console.log("On first run you've to install contao: http://"+docker.domain+"/install.php");

        } else {
            console.log("Hosts cannot be written. Do it by yourseld:");
            console.log(ip + " " +docker.domain);
        }
    });

    // Cleanup
    if(containerRunning == false )
        return cb(new Error("Container could not be started. Tried it "+containerRunningTries+" times"));

    cb();

});


/*************************************************
 * Watch tasks
 ************************************************/
// Docker Watcher
// Uploads files automatically
gulp.task('docker:watch', ['docker:init'], function () {

    // Watch the Module dir and upload on changes to docker
    watch(srcBridge, function(vynil){
        var ftpData = docker.sftp;
        ftpData.remotePath = ftpData.remotePathBridge;
        console.log('Module File ' + vynil.path + ' was ' + vynil.event + ', running docker sftp...');
        console.log(docker.sftp);
        gulp.src(vynil.path, {base: './'})
            .pipe(gsftp(ftpData));
    });

    // Watch the Vendors dir and upload on changes to docker
    watch(srcVendors, function(vynil){
        console.log('Module File ' + vynil.path + ' was ' + vynil.event + ', running docker sftp...');
        var ftpData = docker.sftp;
        ftpData.remotePath = ftpData.remotePathVendors;
        gulp.src(vynil.path, {base: './vendor/'})
            .pipe(gsftp(ftpData));
    });

});




