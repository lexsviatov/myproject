const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');
const chokidar = require('chokidar');

const repoPath = path.resolve(__dirname, '..');
const headFile = path.join(repoPath, '.git', 'HEAD');
const lastBranchFile = path.join(__dirname, '.last_branch');

function getCurrentBranch() {
  try {
    const headContent = fs.readFileSync(headFile, 'utf-8').trim();
    if (headContent.startsWith('ref:')) {
      return headContent.split('/').pop();
    }
  } catch (e) {
    console.error('Error reading HEAD:', e);
  }
  return null;
}

function getLastBranch() {
  try {
    return fs.readFileSync(lastBranchFile, 'utf-8').trim();
  } catch {
    return null;
  }
}

function setLastBranch(branch) {
  fs.writeFileSync(lastBranchFile, branch, 'utf-8');
}

function onBranchChange(newBranch, oldBranch) {
  console.log(`Ветка изменилась: ${oldBranch} -> ${newBranch}`);

  // Запускаем авто-коммит, передавая старую и новую ветки как аргументы
  exec(`node auto-commit.js ${oldBranch || ''} ${newBranch}`, { cwd: __dirname }, (err, stdout, stderr) => {
    if (err) console.error('Ошибка при запуске auto-commit:', err);
    if (stdout) console.log(stdout);
    if (stderr) console.error(stderr);

    // Обновляем файл после выполнения авто-коммита
    setLastBranch(newBranch);
  });
}

let lastBranch = getLastBranch();
console.log(`Текущая сохранённая ветка: ${lastBranch}`);

const watcher = chokidar.watch(headFile, {
  persistent: true,
  usePolling: true,
  interval: 1000,
});

watcher.on('change', () => {
  const currentBranch = getCurrentBranch();
  if (currentBranch && currentBranch !== lastBranch) {
    onBranchChange(currentBranch, lastBranch);
    lastBranch = currentBranch;
  } else {
    console.log(`Ветка не изменилась: ${currentBranch}`);
  }
});

console.log('Наблюдаем за сменой ветки...');
