module.exports = {
  apps: [
    {
      name: 'alls',
      script: 'D:/Gitlab/my-crm/nodejs/alls.js',
      watch: true,
      autorestart: true
    },
    {
      name: 'start',
      script: 'D:/Gitlab/my-crm/nodejs/index.js',
      watch: true,
      autorestart: true
    },
    {
      name: 'auto-commits',
      script: 'D:/Projects/Work/myproject-scripts/auto-commit.js',
      cwd: 'D:/Projects/Work/myproject-scripts',
      watch: false,
      autorestart: true  // включаем авто-перезапуск
      // убрали cron_restart, вместо этого используем setInterval внутри скрипта
    },
    {
      name: 'branch-watcher',
      script: 'D:/Projects/Work/myproject-scripts/branch-watcher.js',
      cwd: 'D:/Projects/Work/myproject',
      watch: false,
      autorestart: true
    }
  ],
  deploy: {
    production: {
      user: 'SSH_USERNAME',
      host: 'SSH_HOSTMACHINE',
      ref: 'origin/master',
      repo: 'GIT_REPOSITORY',
      path: 'DESTINATION_PATH',
      'pre-deploy-local': '',
      'post-deploy': 'npm install && pm2 reload ecosystem.config.js --env production',
      'pre-setup': ''
    }
  }
};
