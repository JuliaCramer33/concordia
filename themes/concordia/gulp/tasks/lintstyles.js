const { exec } = require('child_process');

function lintstyles(cb) {
  exec('npm run lint:styles', (error, stdout, stderr) => {
    if (stdout) console.log(stdout);
    if (stderr) console.error(stderr);
    // Do not stop watch on lint errors; CI will fail on prebuild
    cb();
  });
}

exports.lintstyles = lintstyles;


