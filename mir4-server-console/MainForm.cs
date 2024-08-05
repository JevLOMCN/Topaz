using Server_Console.Config;
using Server_Console.Database;
using Server_Console.Database_Tool;
using Server_Console.Logs;
using System.Data;
using System.Diagnostics;

namespace Server_Console
{
    public partial class MainForm : Form
    {
        public MainForm()
        {
            InitializeComponent();

            // Start a timer to periodically check the status every 5 seconds
            ExeCheckTimer.Interval = 5000; // 5 seconds
            ExeCheckTimer.Tick += ExeCheckTimer_Tick;
            ExeCheckTimer.Start();

            //CheckServerStatus();
        }

        #region Server Checking
        private void ExeCheckTimer_Tick(object sender, EventArgs e)
        {
            //CheckServerStatus();
        }
        private void CheckServerStatus()
        {
            CheckExecutableStatus("Servers", "World", "WorldServer", WorldButton, Properties.Resources.WorldOn, Properties.Resources.WorldOff);
            CheckExecutableStatus("Servers", "Gateway", "GatewayServer", GatewayButton, Properties.Resources.GatewayOn, Properties.Resources.GatewayOff);
            CheckExecutableStatus("Servers", "Game", "GameServer", GameButton, Properties.Resources.GameOn, Properties.Resources.GameOff);
            CheckExecutableStatus("Servers", "Front", "FrontServer", FrontButton, Properties.Resources.FrontOn, Properties.Resources.FrontOff);
            CheckExecutableStatus("Servers", "Chatting", "ChattingServer", ChattingButton, Properties.Resources.ChatOn, Properties.Resources.ChatOff);
        }

        private void CheckExecutableStatus(string serversBasePath, string serverFolder, string executableName, PictureBox pictureBox, Image runningImage, Image stoppedImage)
        {
            string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
            string serverPath = Path.Combine(baseDirectory, serversBasePath, serverFolder, $"{executableName}.exe");

            if (File.Exists(serverPath))
            {
                Process[] processes = Process.GetProcessesByName(executableName);

                if (processes.Length > 0)
                {
                    pictureBox.Image = runningImage;
                }
                else
                {
                    pictureBox.Image = stoppedImage;
                }
            }
            else
            {
                MessageBox.Show($"{executableName}.exe not found.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #region Launching
        private void LaunchServer(string serverExeName)
        {
            // Determine the server folder based on the serverExeName
            string serverFolder = "";

            switch (serverExeName)
            {
                case "WorldServer":
                    serverFolder = "World";
                    break;
                case "GatewayServer":
                    serverFolder = "Gateway";
                    break;
                case "GameServer":
                    serverFolder = "Game";
                    break;
                case "FrontServer":
                    serverFolder = "Front";
                    break;
                case "ChattingServer":
                    serverFolder = "Chatting";
                    break;
                default:
                    MessageBox.Show("Invalid server name.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
            }

            string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
            string serverPath = Path.Combine(baseDirectory, "Servers", serverFolder, $"{serverExeName}.exe");

            if (File.Exists(serverPath))
            {
                Process[] processes = Process.GetProcessesByName(serverExeName);

                if (processes.Length > 0)
                {
                    // Server is already running, stop it
                    foreach (Process process in processes)
                    {
                        process.Kill(); // Terminate the process
                        process.WaitForExit(); // Wait for the process to exit
                    }
                    MessageBox.Show($"{serverExeName} server stopped.", "Server Stopped", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                else
                {
                    // Server is not running, start it
                    LaunchExecutable(serverPath);
                }
            }
            else
            {
                MessageBox.Show($"{serverExeName}.exe not found.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }

            // Update server status after launching or stopping the server
            CheckServerStatus();
        }
        private void LaunchExecutable(string exePath)
        {
            string exeDirectory = Path.GetDirectoryName(exePath);
            string exeFileName = Path.GetFileName(exePath);

            ProcessStartInfo startInfo = new ProcessStartInfo
            {
                FileName = exePath,
                WorkingDirectory = exeDirectory,
                WindowStyle = ProcessWindowStyle.Minimized
            };

            try
            {
                Process.Start(startInfo);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error launching {exeFileName}: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #region CloseButton
        private void CloseButton_Click(object sender, EventArgs e)
        {
            this.Close(); // Close the form
        }
        private void CloseButton_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;

            // Create a tooltip instance
            ToolTip toolTip = new ToolTip();

            // Set up tooltip text for CloseButton
            toolTip.SetToolTip(CloseButton, "Close");

            Cursor = Cursors.Hand;
        }
        #endregion

        #region Server Launch Buttons

        private void StartAllButton_Click(object sender, EventArgs e)
        {
            string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
            string batchFilePath = Path.Combine(baseDirectory, "Servers", "start_servers.bat");

            if (File.Exists(batchFilePath))
            {
                try
                {
                    ProcessStartInfo startInfo = new ProcessStartInfo(batchFilePath)
                    {
                        UseShellExecute = true,
                        WorkingDirectory = Path.GetDirectoryName(batchFilePath)
                    };
                    Process.Start(startInfo);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Failed to start batch file. Error: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
            else
            {
                MessageBox.Show($"Batch file not found at: {batchFilePath}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void WorldButton_Click(object sender, EventArgs e)
        {
            LaunchServer("WorldServer");
        }

        private void GatewayButton_Click(object sender, EventArgs e)
        {
            LaunchServer("GatewayServer");
        }

        private void GameButton_Click(object sender, EventArgs e)
        {
            LaunchServer("GameServer");
        }

        private void FrontButton_Click(object sender, EventArgs e)
        {
            LaunchServer("FrontServer");
        }

        private void ChattingButton_Click(object sender, EventArgs e)
        {
            LaunchServer("ChattingServer");
        }
        #endregion

        #region Forms
        private void JSONImage_Click(object sender, EventArgs e)
        {
            // Show JSONForm when JSONImage is clicked
            JSONForm jsonForm = new JSONForm();
            jsonForm.Show();
        }
        private void LogsImage_Click(object sender, EventArgs e)
        {
            // Show LogForm when LogsImage is clicked
            LogForm logForm = new LogForm();
            logForm.Show();
        }
        private void DatabaseImage_Click(object sender, EventArgs e)
        {
            // Create an instance of DatabaseForm
            DatabaseForm databaseForm = new DatabaseForm();

            // Show the DatabaseForm
            databaseForm.Show();
        }
        private void ConfigImage_Click(object sender, EventArgs e)
        {
            // Create an instance of ConfigForm
            ConfigForm configForm = new ConfigForm();

            // Show the ConfigForm
            configForm.Show();
        }
        private void DatabaseToolImage_Click(object sender, EventArgs e)
        {
            // Create an instance of the DatabaseTool form
            DatabaseTool databaseToolForm = new DatabaseTool();

            // Show the DatabaseTool form
            databaseToolForm.Show();
        }

        #endregion

        #region Logo
        private void Logo_Click(object sender, EventArgs e)
        {
            // Specify the URL you want to open
            string url = "https://thelegendofmir.uk/";

            // Open the URL in the default web browser
            Process.Start(new ProcessStartInfo(url) { UseShellExecute = true });
        }
        #endregion

        #region Cursors

        private void Logo_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void DatabaseImage_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void ConfigImage_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void LogsImage_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void JSONImage_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void StartAllButton_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void WorldButton_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void GatewayButton_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void GameButton_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void FrontButton_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void ChattingButton_MouseHover(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void Logo_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        private void CloseButton_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Hand;
        }
        private void StartAllButton_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        private void DatabaseImage_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        private void WorldButton_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        private void GatewayButton_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        private void GameButton_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        private void FrontButton_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        private void ChattingButton_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        private void ConfigImage_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        private void LogsImage_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        private void JSONImage_MouseLeave(object sender, EventArgs e)
        {
            Cursor = Cursors.Default;
        }
        #endregion
    }
}