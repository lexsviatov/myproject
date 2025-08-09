const { exec } = require('child_process');
const path = require('path');

// Путь к репозиторию — укажи свой путь
const REPO_DIR = path.resolve(__dirname, '..'); // если скрипт в nodejs, а репо на уровень выше

// Функция для выполнения команды git и возврата промиса
function execGitCmd(cmd) {
  return new Promise((resolve, reject) => {
    exec(cmd, { cwd: REPO_DIR }, (error, stdout, stderr) => {
      if (error) {
        reject(stderr || error.message);
      } else {
        resolve(stdout.trim());
      }
    });
  });
}

// Основная логика
async function autoCommit() {
  try {
    // Проверяем есть ли изменения (unstaged + staged)
    const unstaged = await execGitCmd('git diff --quiet || echo "changed"');
    const staged = await execGitCmd('git diff --cached --quiet || echo "changed"');

    if (unstaged === 'changed' || staged === 'changed') {
      console.log('Изменения найдены, коммитим и пушим...');

      await execGitCmd('git add .');

      const dateStr = new Date().toISOString().replace('T', ' ').slice(0, 19);
      await execGitCmd(`git commit -m "Авто-коммит: ${dateStr}"`);

      const branch = await execGitCmd('git branch --show-current');
      await execGitCmd(`git push origin ${branch}`);

      console.log('Коммит и пуш выполнены успешно.');
    } else {
      console.log('Изменений нет');
    }
  } catch (err) {
    console.error('Ошибка:', err);
  }
}

// Запуск
autoCommit();
