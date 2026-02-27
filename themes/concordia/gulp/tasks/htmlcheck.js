const { exec } = require('child_process');

function htmlcheck(cb) {
  // Only run if HINT_URL is provided to avoid noisy output during normal dev.
  if (!process.env.HINT_URL) {
    return cb();
  }

  exec('npm run check:html', (error, stdout, stderr) => {
    if (stdout) console.log(stdout);
    if (stderr) console.error(stderr);
    // Do not break the watcher; CI will handle gating separately.
    cb();
  });
}

exports.htmlcheck = htmlcheck;


