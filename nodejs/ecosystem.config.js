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
      autorestart: true
    },
    {
      name: 'branch-watcher',
      script: 'D:/Projects/Work/myproject-scripts/branch-watcher.js',
      cwd: 'D:/Projects/Work/myproject',
      watch: false,
      autorestart: true
    },
    {
      name: 'auto-commit-watcher',
      script: 'D:/Projects/Work/myproject-scripts/auto-commit-watcher.js',
      cwd: 'D:/Projects/Work/myproject-scripts',
      watch: false,
      autorestart: true
    },
    {
      name: 'myproject-app',
      script: 'index.js',
      cwd: 'D:/Projects/Work/myproject/nodejs',
      watch: true,
      autorestart: true
    }
  ]
}
