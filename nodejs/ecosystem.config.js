module.exports = {
  apps: [
      {
      name: 'alls',
      script: 'D:/Gitlab/my-crm/nodejs/alls.js',
      watch: true
    },
    {
      name: 'start',
      script: 'D:/Gitlab/my-crm/nodejs/index.js',
      watch: true
    },
    {
  name: 'auto-commits',
  script: 'D:/Projects/Work/myproject/nodejs/auto-commit.js',
  watch: false,
  autorestart: false,
  cron_restart: '*/5 * * * *'
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