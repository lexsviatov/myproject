const { exec } = require("child_process");
const fs = require("fs");
const path = require("path");

// Загружаем конфиг
const configPath = "D:/Projects/config/myproject-config.json";
if (!fs.existsSync(configPath)) {
    console.error(`Config file not found: ${configPath}`);
    process.exit(1);
}
const config = JSON.parse(fs.readFileSync(configPath, "utf-8"));

const TELEGRAM_BOT_TOKEN = config.TELEGRAM_BOT_TOKEN;
const TELEGRAM_CHAT_ID = config.TELEGRAM_CHAT_ID;

function runCommand(command, cwd = process.cwd()) {
    return new Promise((resolve, reject) => {
        exec(command, { cwd }, (error, stdout, stderr) => {
            if (error) {
                console.error(`Ошибка при выполнении команды "${command}":`, error);
                reject(error);
            } else {
                if (stdout) console.log(stdout.trim());
                if (stderr) console.error(stderr.trim());
                resolve(stdout.trim());
            }
        });
    });
}

async function sendTelegramMessage(message) {
    try {
        const fetch = (await import("node-fetch")).default;
        const url = `https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage`;

        const res = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                chat_id: TELEGRAM_CHAT_ID,
                text: message
            })
        });

        const data = await res.json();
        if (!data.ok) {
            throw new Error(`Telegram API error: ${data.error_code} ${data.description}`);
        }
        console.log("Сообщение успешно отправлено в Telegram");
    } catch (error) {
        console.error("Ошибка при отправке сообщения в Telegram:", error);
    }
}

async function autoPushAndNotify() {
    try {
        console.log("=== Запуск auto-commit ===");

        const repoPath = path.resolve(__dirname, "..");
        const branch = await runCommand("git rev-parse --abbrev-ref HEAD", repoPath);
        console.log(`Текущая ветка: ${branch}`);

        await runCommand("git add .", repoPath);

        const date = new Date().toISOString().replace("T", " ").split(".")[0];
        await runCommand(`git commit -m "Auto-commit: ${date}"`, repoPath).catch(() => {
            console.log("Нет изменений для коммита.");
        });

        console.log("Выполняем git push");
        await runCommand("git push", repoPath);

        console.log("Отправляем уведомление в Telegram");
        const repoUrl = "https://github.com/lexsviatov/myproject";
        const message = `В репозиторий myproject (${repoUrl}) были внесены изменения в ветку ${branch}. Пожалуйста, просмотри и прокомментируй.`;

        await sendTelegramMessage(message);

        console.log("Операция завершена.");
    } catch (error) {
        console.error("Произошла ошибка:", error);
    }
}

autoPushAndNotify();
