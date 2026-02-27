const { exec } = require('child_process');

function lintjs(cb) {
  exec('npm run lint:js', (error, stdout, stderr) => {
    if (stdout) console.log(stdout);
    if (stderr) console.error(stderr);
    cb();
  });
}

exports.lintjs = lintjs;


