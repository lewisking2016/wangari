const { app, BrowserWindow } = require('electron');
const path = require('path');
const { spawn } = require('child_process');
const http = require('http');

let phpServerProcess = null;
let mainWindow = null;
const phpPort = 8080; // We'll run the local PHP server on port 8080

function startPhpServer() {
    return new Promise((resolve, reject) => {
        console.log('Starting PHP built-in server...');
        
        // Find local PHP binary. For development/production, we check environment path first,
        // and fall back to bundled binary paths.
        const phpBin = 'php'; 
        const docRoot = path.join(__dirname, '../Frontend');
        
        console.log(`Document Root: ${docRoot}`);
        
        // Spawn PHP built-in server with WANGARI_MODE env variable
        phpServerProcess = spawn(phpBin, [
            '-S', `127.0.0.1:${phpPort}`,
            '-t', docRoot
        ], {
            env: { ...process.env, WANGARI_MODE: 'desktop' }
        });

        phpServerProcess.stdout.on('data', (data) => {
            console.log(`PHP stdout: ${data}`);
        });

        phpServerProcess.stderr.on('data', (data) => {
            console.log(`PHP stderr: ${data}`);
        });

        phpServerProcess.on('close', (code) => {
            console.log(`PHP server process exited with code ${code}`);
        });

        // Wait and check if the port is open and responsive before returning success
        let checkCount = 0;
        const interval = setInterval(() => {
            checkCount++;
            if (checkCount > 30) {
                clearInterval(interval);
                reject(new Error('PHP server failed to start within 3 seconds.'));
            }

            const req = http.request({
                host: '127.0.0.1',
                port: phpPort,
                path: '/',
                method: 'GET',
                timeout: 500
            }, (res) => {
                clearInterval(interval);
                resolve();
            });

            req.on('error', () => {
                // Keep trying
            });

            req.end();
        }, 100);
    });
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        title: "Wangari Farm OS",
        icon: path.join(__dirname, '../Frontend/images/wangari-logo.png'),
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true
        }
    });

    mainWindow.loadURL(`http://127.0.0.1:${phpPort}`);

    mainWindow.on('closed', () => {
        mainWindow = null;
    });
}

app.on('ready', () => {
    startPhpServer()
        .then(() => {
            createWindow();
        })
        .catch((err) => {
            console.error('Error starting application:', err);
            app.quit();
        });
});

app.on('window-all-closed', () => {
    // On macOS, apps usually stay active until explicit CMD+Q
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('quit', () => {
    if (phpServerProcess) {
        console.log('Stopping PHP built-in server...');
        phpServerProcess.kill();
    }
});
